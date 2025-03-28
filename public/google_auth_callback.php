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

if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token);

        $google_oauth = new Google\Service\Oauth2($client);
        $google_account = $google_oauth->userinfo->get();

        // Verificar o crear usuario
        $stmt = $pdo->prepare("SELECT * FROM db_usuarios WHERE google_id = ? OR email = ?");
        $stmt->execute([$google_account->id, $google_account->email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Crear nuevo usuario
            $stmt = $pdo->prepare("INSERT INTO db_usuarios (google_id, nombre, email) VALUES (?, ?, ?)");
            $stmt->execute([
                $google_account->id,
                $google_account->name,
                $google_account->email
            ]);
            $user_id = $pdo->lastInsertId();
        } else {
            $user_id = $user['id'];
            // Actualizar google_id si es necesario
            if (empty($user['google_id'])) {
                $stmt = $pdo->prepare("UPDATE db_usuarios SET google_id = ? WHERE id = ?");
                $stmt->execute([$google_account->id, $user_id]);
            }
        }

        // Crear sesión y cookie
        session_start();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['google_token'] = $token;

        // Cookie de 30 días
        setcookie(
            'remember_token',
            bin2hex(random_bytes(32)),
            time() + 3600 * 24 * 30,
            '/',
            '',
            true,
            true
        );

        header('Location: /dashboard.php');
        exit;
    } catch (Exception $e) {
        error_log('Google Auth Error: ' . $e->getMessage());
        header('Location: login.php?error=google_auth_failed');
        exit;
    }
}
