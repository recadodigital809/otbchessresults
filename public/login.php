<?php
require_once __DIR__ . '/vendor/autoload.php'; // Cargar dependencias
require_once __DIR__ . '/database/connection.php';
include __DIR__ . '/templates/header.php';

use Google\Client as Google_Client;

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..'); // Carga el .env desde el nivel superior
$dotenv->load();

// variables de entorno están cargadas correctamente
// var_dump($_ENV['GOOGLE_CLIENT_ID'], $_ENV['GOOGLE_CLIENT_SECRET']);

session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => 'otbchessresults.com',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

// Verificar si ya está autenticado
if (isset($_SESSION['usuario'])) {
    header('Location: /dashboard.php');
    exit();
}

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    if (empty($_ENV['GOOGLE_CLIENT_ID']) || empty($_ENV['GOOGLE_CLIENT_SECRET'])) {
        throw new Exception('Credenciales de Google OAuth no configuradas.');
    }

    $client = new Google_Client();
    $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
    $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
    $client->setRedirectUri('https://otbchessresults.com/callback.php');
    $client->addScope(['email', 'profile']);
    $client->setIncludeGrantedScopes(true);
    $client->setState($_SESSION['csrf_token']); // Protección CSRF

    $authUrl = $client->createAuthUrl();
} catch (Exception $e) {
    error_log('Error en login: ' . $e->getMessage());
    header('Location: /error.php?msg=' . urlencode($e->getMessage()));
    exit();
}

// Mostrar el enlace de autenticación con Google
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>

<body>
    <h2>Iniciar sesión</h2>
    <p><a href="<?= htmlspecialchars($authUrl) ?>">Iniciar sesión con Google</a></p>
</body>

</html>