<?php
include __DIR__ . '/templates/header.php';

require_once __DIR__ . '/database/connection.php';
require 'vendor/autoload.php';
// use Google\Client;
// use Google\Service\Oauth2;

// Configuración de cookies de sesión ANTES de session_start()
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => 'otbchessresults.com',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

require 'config.php';
session_start();

// 1. Validación CSRF mejorada
if (!isset($_GET['state'], $_SESSION['csrf_token'], $_SESSION['csrf_expire'])) {
    error_log("CSRF: Parámetros faltantes - IP: {$_SERVER['REMOTE_ADDR']} UA: {$_SERVER['HTTP_USER_AGENT']}");
    session_unset();
    session_destroy();
    header("Location: /error.php?code=csrf_missing_params");
    exit();
}

// Validar expiración del token (10 minutos)
if (time() > $_SESSION['csrf_expire']) {
    error_log("CSRF: Token expirado - IP: {$_SERVER['REMOTE_ADDR']}");
    session_unset();
    session_destroy();
    header("Location: /error.php?code=csrf_expired");
    exit();
}

// Comparación segura contra timing attacks
if (!hash_equals($_SESSION['csrf_token'], $_GET['state'])) {
    error_log("CSRF: Token inválido - IP: {$_SERVER['REMOTE_ADDR']} | Sesión: {$_SESSION['csrf_token']} | Recibido: {$_GET['state']}");
    session_unset();
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/', 'otbchessresults.com', true, true);
    header("Location: /error.php?code=csrf_invalid");
    exit();
}

// Limpiar token CSRF después de uso válido
unset($_SESSION['csrf_token'], $_SESSION['csrf_expire']);

try {
    // 2. Configuración cliente Google con validación adicional
    $client = new Google_Client();
    $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
    $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
    $client->setRedirectUri('https://otbchessresults.com/callback.php');
    $client->setScopes(['email', 'profile']);
    $client->setAccessType('offline');
    $client->setPrompt('select_account');
    $client->setIncludeGrantedScopes(true);

    $oauth = new Oauth2($client);
    $userInfo = $oauth->userinfo->get();

    // 3. Validación de código de autorización
    if (!isset($_GET['code'])) {
        throw new Exception("Falta parámetro de autorización");
    }

    // 4. Intercambio de código por token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if ($client->isAccessTokenExpired() || isset($token['error'])) {
        throw new Exception("Error de token: " . ($token['error_description'] ?? 'Token inválido'));
    }

    // 5. Obtener y validar datos de usuario
    $oauth = new Oauth2($client);
    $userInfo = $oauth->userinfo->get();

    if (!$userInfo->getVerifiedEmail()) {
        throw new Exception("Email no verificado");
    }

    // Sanitización de datos
    $googleId = $userInfo->getId();
    $nombre = filter_var($userInfo->getName(), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_var($userInfo->getEmail(), FILTER_SANITIZE_EMAIL);
    $locale = filter_var($userInfo->getLocale(), FILTER_SANITIZE_STRING);

    // 6. Transacción de base de datos con prepared statements
    $conn->beginTransaction();
    try {
        $stmt = $conn->prepare("SELECT id, nombre, email FROM db_usuarios WHERE google_id = ?");
        $stmt->execute([$googleId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Registro nuevo usuario
            $insert = $conn->prepare("INSERT INTO db_usuarios (google_id, nombre, email, locale) VALUES (?, ?, ?, ?)");
            $insert->execute([$googleId, $nombre, $email, $locale]);
            $userId = $conn->lastInsertId();
        } else {
            // Actualizar datos existentes
            $userId = $user['id'];
            $updates = [];
            $params = [];

            if ($user['nombre'] !== $nombre) {
                $updates[] = "nombre = ?";
                $params[] = $nombre;
            }
            if ($user['email'] !== $email) {
                $updates[] = "email = ?";
                $params[] = $email;
            }
            if ($user['locale'] !== $locale) {
                $updates[] = "locale = ?";
                $params[] = $locale;
            }

            if (!empty($updates)) {
                $params[] = $userId;
                $updateQuery = "UPDATE db_usuarios SET " . implode(', ', $updates) . " WHERE id = ?";
                $conn->prepare($updateQuery)->execute($params);
            }
        }

        $conn->commit();
    } catch (PDOException $e) {
        $conn->rollBack();
        throw new Exception("Error en base de datos: " . $e->getMessage());
    }

    // 7. Gestión de sesión segura
    session_regenerate_id(true);

    $_SESSION['usuario'] = [
        'id' => $userId,
        'google_id' => $googleId,
        'nombre' => $nombre,
        'email' => $email,
        'locale' => $locale,
        'last_login' => time(),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];

    // 8. Headers de seguridad adicionales
    header("Content-Security-Policy: default-src 'self'");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");

    // 9. Redirección segura
    header("Location: /dashboard.php");
    exit();
} catch (Exception $e) {
    error_log("ERROR: " . $e->getMessage() . " - IP: {$_SERVER['REMOTE_ADDR']} - UA: {$_SERVER['HTTP_USER_AGENT']}");
    header("Location: /error.php?code=auth_failed&t=" . time());
    exit();
}
