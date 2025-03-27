<?php
include __DIR__ . '/templates/header.php';
require_once __DIR__ . '/vendor/autoload.php'; // Autoload para Google Client
require_once __DIR__ . '/database/connection.php';

use Google\Client;
use Google\Service\Oauth2;

// Configuración de cookies antes de session_start()
session_set_cookie_params([
    'lifetime' => 7200,  // Aumenta el tiempo de expiración de la cookie a 2 horas
    'path' => '/',
    'domain' => 'otbchessresults.com',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

// CSRF: Validar existencia y expiración del token
if (!isset($_GET['state'], $_SESSION['csrf_token'], $_SESSION['csrf_expire']) || time() > $_SESSION['csrf_expire']) {
    error_log("CSRF Error: Token inválido o expirado - IP: {$_SERVER['REMOTE_ADDR']}");
    session_destroy();
    header("Location: /error.php?code=csrf_invalid");
    exit();
}

// Comparación segura del token CSRF
if (!hash_equals($_SESSION['csrf_token'], $_GET['state'])) {
    error_log("CSRF Error: Token no coincide - IP: {$_SERVER['REMOTE_ADDR']}");
    session_destroy();
    header("Location: /error.php?code=csrf_mismatch");
    exit();
}

unset($_SESSION['csrf_token'], $_SESSION['csrf_expire']); // Evitar reuso del token

try {
    if (empty($_ENV['GOOGLE_CLIENT_ID']) || empty($_ENV['GOOGLE_CLIENT_SECRET'])) {
        throw new Exception("Credenciales de Google OAuth no configuradas.");
    }

    $client = new Client();
    $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
    $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
    $client->setRedirectUri('https://otbchessresults.com/callback.php');
    $client->addScope(['email', 'profile']);
    $client->setAccessType('offline');
    $client->setPrompt('select_account');

    // Validación de código de autorización
    if (!isset($_GET['code']) || empty($_GET['code'])) {
        throw new Exception("Código de autorización faltante o inválido.");
    }

    // Intercambio de código por token de acceso
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if ($client->isAccessTokenExpired() || isset($token['error'])) {
        throw new Exception("Error en el token: " . ($token['error_description'] ?? 'Token inválido.'));
    }

    $client->setAccessToken($token);
    $oauth = new Oauth2($client);
    $userInfo = $oauth->userinfo->get();

    if (!$userInfo->getVerifiedEmail()) {
        throw new Exception("El correo electrónico no está verificado.");
    }

    // Sanitización de datos
    $googleId = $userInfo->getId();
    $nombre = filter_var($userInfo->getName(), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_var($userInfo->getEmail(), FILTER_SANITIZE_EMAIL);

    // Base de datos: Validación y registro de usuario
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT id, nombre, email FROM db_usuarios WHERE google_id = ?");
    $stmt->execute([$googleId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $insert = $pdo->prepare("INSERT INTO db_usuarios (google_id, nombre, email) VALUES (?, ?, ?)");
        $insert->execute([$googleId, $nombre, $email]);
        $userId = $pdo->lastInsertId();
    } else {
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

        if (!empty($updates)) {
            $params[] = $userId;
            $updateQuery = "UPDATE db_usuarios SET " . implode(', ', $updates) . " WHERE id = ?";
            $pdo->prepare($updateQuery)->execute($params);
        }
    }

    $pdo->commit();

    // Regenerar sesión para seguridad
    session_regenerate_id(true);

    $_SESSION['usuario'] = [
        'id' => $userId,
        'google_id' => $googleId,
        'nombre' => $nombre,
        'email' => $email,
        'last_login' => time(),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];

    // Seguridad en encabezados
    header("Content-Security-Policy: default-src 'self'; img-src 'self' https://*.googleusercontent.com; script-src 'self' 'unsafe-inline' https://accounts.google.com");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");

    // Redirección a dashboard
    header("Location: /dashboard.php");
    exit();
} catch (Exception $e) {
    error_log("ERROR: " . $e->getMessage() . " - IP: {$_SERVER['REMOTE_ADDR']} - UA: {$_SERVER['HTTP_USER_AGENT']}");
    session_destroy();
    header("Location: /error.php?code=auth_failed&t=" . time());
    exit();
}
