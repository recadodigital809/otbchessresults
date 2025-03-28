<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/database/connection.php';


// Cargar desde la raíz del proyecto
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


$client = new Google\Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$client->addScope('email');
$client->addScope('profile');

if (isset($_GET['code'])) {
    header('Location: google_auth_callback.php?code=' . $_GET['code']);
    exit;
}

// Mostrar formulario de login o botón de Google
?>
<!DOCTYPE html>
<html>

<head>
    <title>Login</title>
</head>

<body>
    <a href="<?= $client->createAuthUrl() ?>">Login with Google</a>
</body>

</html>