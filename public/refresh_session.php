<?php
require_once __DIR__ . '/database/connection.php';

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar cookie remember_token
if (isset($_COOKIE['remember_token'])) {
    try {
        $db = getDBConnection();
        $token = $_COOKIE['remember_token'];

        // Consulta actualizada para tu estructura de db_usuarios
        $stmt = $db->prepare("
            SELECT id, google_id, nombre, email 
            FROM db_usuarios 
            WHERE remember_token = :token 
            AND token_expiry > NOW()
        ");
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Regenerar ID de sesión por seguridad
            session_regenerate_id(true);

            // Establecer datos de sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_data'] = [
                'google_id' => $user['google_id'],
                'nombre' => $user['nombre'],
                'email' => $user['email']
            ];

            // Opcional: Actualizar el token para prolongar la sesión
            $newToken = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));

            $updateStmt = $db->prepare("
                UPDATE db_usuarios 
                SET remember_token = :new_token, 
                    token_expiry = :expiry 
                WHERE id = :user_id
            ");
            $updateStmt->execute([
                ':new_token' => $newToken,
                ':expiry' => $expiry,
                ':user_id' => $user['id']
            ]);

            // Actualizar la cookie
            setcookie(
                'remember_token',
                $newToken,
                [
                    'expires' => time() + 60 * 60 * 24 * 30,
                    'path' => '/',
                    'domain' => $_SERVER['HTTP_HOST'],
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]
            );

            header('Location: dashboard.php');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error en refresh_session: " . $e->getMessage());
        // Continuar con el redireccionamiento a login
    }
}

// Redireccionar a login si falla la autenticación
header('Location: login.php');
exit;
