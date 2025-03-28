<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Google_Client;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

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
    header('Location: dashboard.php');
    exit();
}

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    // Validar credenciales de Google
    if (empty($_ENV['GOOGLE_CLIENT_ID']) || empty($_ENV['GOOGLE_CLIENT_SECRET'])) {
        throw new Exception("Las credenciales de Google no están configuradas.");
    }

    $client = new Google_Client();
    $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
    $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
    $client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
    $client->addScope(['email', 'profile']);
    $client->setIncludeGrantedScopes(true);
    $client->setState($_SESSION['csrf_token']); // Protección CSRF

    $authUrl = $client->createAuthUrl();
} catch (Exception $e) {
    error_log('Error en login: ' . $e->getMessage());
    header('Location: error.php');
    exit();
}
