<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/database/connection.php';

use Google_Client;
use Google_Service_Oauth2;
use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configuración de cookies de sesión ANTES de session_start()
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => 'otbchessresults.com',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// Conectar a la base de datos
$conn = getDBConnection();

// 1. Validación CSRF mejorada
if (!isset($_GET['state'], $_SESSION['csrf_token'])) {
    session_unset();
    session_destroy();
    header("Location: /error.php?code=csrf_missing");
    exit();
}

// Comparación segura
if (!hash_equals($_SESSION['csrf_token'], $_GET['state'])) {
    session_unset();
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/', 'otbchessresults.com', true, true);
    header("Location: /error.php?code=csrf_invalid");
    exit();
}

// Limpiar CSRF token después de uso válido
unset($_SESSION['csrf_token']);

try {
    // 2. Configurar cliente de Google
    if (empty($_ENV['GOOGLE_CLIENT_ID']) || empty($_ENV['GOOGLE_CLIENT_SECRET'])) {
        throw new Exception("Credenciales de Google no configuradas.");
    }

    $client = new Google_Client();
    $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
    $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
    $client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
    $client->addScope(['email', 'profile']);
    $client->setAccessType('offline');
    $client->setIncludeGrantedScopes(true);

    if (!isset($_GET['code'])) {
        throw new Exception("Falta código de autorización.");
    }

    // 3. Obtener token de acceso
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if ($client->isAccessTokenExpired() || isset($token['error'])) {
        throw new Exception("Error en token: " . ($token['error_description'] ?? 'Token inválido'));
    }

    // 4. Obtener datos del usuario
    $oauth = new Google_Service_Oauth2($client);
    $userInfo = $oauth->userinfo->get();

    if (!$userInfo->getVerifiedEmail()) {
        throw new Exception("Email no verificado.");
    }

    // Sanitización de datos
    $googleId = $userInfo->getId();
    $nombre = filter_var($userInfo->getName(), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_var($userInfo->getEmail(), FILTER_SANITIZE_EMAIL);

    // 5. Verificar si el usuario ya existe en la base de datos
    $stmt = $conn->prepare("SELECT id, name, email FROM db_usuarios WHERE google_id = ?");
    $stmt->execute([$googleId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $conn->beginTransaction();

    if (!$user) {
        // Registro de nuevo usuario
        $insert = $conn->prepare("INSERT INTO db_usuarios (google_id, name, email) VALUES (?, ?, ?)");
        $insert->execute([$googleId, $nombre, $email]);
        $userId = $conn->lastInsertId();
    } else {
        $userId = $user['id'];

        // Actualizar solo si cambian los datos
        $updates = [];
        $params = [];

        if ($user['name'] !== $nombre) {
            $updates[] = "name = ?";
            $params[] = $nombre;
        }
        if ($user['email'] !== $email) {
            $updates[] = "email = ?";
            $params[] = $email;
        }

        if (!empty($updates)) {
            $params[] = $userId;
            $updateQuery = "UPDATE db_usuarios SET " . implode(', ', $updates) . " WHERE id = ?";
            $conn->prepare($updateQuery)->execute($params);
        }
    }

    $conn->commit();

    // 6. Establecer sesión segura
    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id' => $userId,
        'google_id' => $googleId,
        'name' => $nombre,
        'email' => $email,
        'last_login' => time(),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];

    // 7. Redirigir al dashboard
    header("Location: /dashboard.php");
    exit();
} catch (Exception $e) {
    error_log("ERROR: " . $e->getMessage() . " - IP: {$_SERVER['REMOTE_ADDR']}");
    header("Location: /error.php?code=auth_failed");
    exit();
}
