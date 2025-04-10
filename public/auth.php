<?php
function require_auth($pdo)
{
    session_start();

    if (empty($_SESSION['user_id'])) {
        if (!empty($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];

            $stmt = $pdo->prepare("SELECT id FROM db_Usuarios WHERE remember_token = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch();

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                return;
            } else {
                // Eliminar cookie inválido
                setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            }
        }

        // Redirigir si no hay sesión ni token válido
        header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}
