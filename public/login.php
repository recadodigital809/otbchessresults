<?php
session_start();

// Verificar si ya está autenticado
if (isset($_SESSION['user_id'])) {
    // header('Location: dashboard.php');
    header('Location: google_auth_callback.php');
    exit;
}

// Cargar dependencias
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/database/connection.php';

// Configurar entorno
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    $dotenv->required(['GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET', 'GOOGLE_REDIRECT_URI']);
} catch (Exception $e) {
    die('Error de configuración: ' . $e->getMessage());
}

// Configurar cliente Google
$client = new Google\Client();
$client->setApplicationName('OTBchessresults');
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$client->addScope(['email', 'profile']);
$client->setAccessType('offline');
$client->setPrompt('select_account consent');

// Manejar código de autorización
if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token);

        // Redirigir al callback para procesamiento
        header('Location: google_auth_callback.php');
        exit;
    } catch (Exception $e) {
        error_log('Error en autenticación Google: ' . $e->getMessage());
        $error = "Error en autenticación. Por favor intenta nuevamente.";
    }
}

// Mostrar página de login
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Iniciar Sesión</title>
    <style>
        :root {
            --primary-color: #4285f4;
            --hover-color: #357abd;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }

        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .google-btn {
            background: var(--primary-color);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: background 0.3s ease;
        }

        .google-btn:hover {
            background: var(--hover-color);
        }

        .google-icon {
            width: 20px;
            height: 20px;
            background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTgiIGhlaWdodD0iMTgiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGcgZmlsbD0ibm9uZSIgZmlsbC1ydWxlPSJldmVub2RkIj48cGF0aCBkPSJNMTcuNiA5LjJsLS4xLTEuOEg5djMuNGg0LjhDMTMuNiAxMiAxMyAxMyAxMiAxMy42djIuMmgzYTguOCA4LjggMCAwIDAgMi42LTYuNnoiIGZpbGw9IiM0Mjg1RjQiIGZpbGwtcnVsZT0ibm9uemVybyIvPjxwYXRoIGQ9Ik05IDE4YzIuNCAwIDQuNS0uOCA2LTIuMmwtMy0yLjJhNS40IDUuNCAwIDAgMS04LTIuOUgxVjEzYTkgOSAwIDAgMCA4IDV6IiBmaWxsPSIjMzRBODUzIiBmaWxsLXJ1bGU9Im5vbnplcm8iLz48cGF0aCBkPSJNNCAxMC43YTUuNCA1LjQgMCAwIDEgMC0zLjRWNUgxYTkgOSAwIDAgMCAwIDhsMy0yLjN6IiBmaWxsPSIjRkJCQzAwIiBmaWxsLXJ1bGU9Im5vbnplcm8iLz48cGF0aCBkPSJNOSAzLjZjMS4zIDAgMi41LjQgMy40IDEuM0wxNSAyLjNBOSA5IDAgMCAwIDEgNWwzIDIuNGE1LjQgNS40IDAgMCAxIDUtMS43eiIgZmlsbD0iI0VBNDMzNSIgZmlsbC1ydWxlPSJub256ZXJvIi8+PHBhdGggZD0iTTAgMGgxOHYxOEgweiIvPjwvZz48L3N2Zz4=') center no-repeat;
        }

        .error {
            color: #dc3545;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <h1>Bienvenido</h1>
        <p>Por favor inicia sesión con tu cuenta de Google</p>

        <a href="<?= htmlspecialchars($client->createAuthUrl()) ?>" class="google-btn">
            <span class="google-icon"></span>
            Continuar con Google
        </a>
    </div>
</body>

</html>