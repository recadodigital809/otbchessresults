<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Protección contra secuestro de sesión (opcional pero recomendado)
if ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR'] || $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Regenerar ID de sesión periódicamente (cada 10 minutos)
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 600) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

echo "Bienvenido, " . htmlspecialchars($_SESSION['usuario']['nombre']);
echo "<br><a href='logout.php'>Cerrar sesión</a>";
