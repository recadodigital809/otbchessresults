<?php

require_once __DIR__ . "/database/connection.php";
require_once __DIR__ . '/templates/header.php';

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpiar token de la base de datos si existe
if (isset($_SESSION['user_id'])) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("
            UPDATE db_usuarios 
            SET remember_token = NULL, 
                token_expiry = NULL 
            WHERE id = :user_id
        ");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
    } catch (PDOException $e) {
        error_log("Error al limpiar token: " . $e->getMessage());
    }
}

// Destruir sesión
$_SESSION = array();
session_destroy();

// Eliminar cookie
setcookie(
    'remember_token',
    '',
    [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => true,
        'httponly' => true
    ]
);

header('Location: login.php');
exit;

require_once __DIR__ . '/templates/footer.php.php';
