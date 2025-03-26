<?php
session_start();

// Si no hay sesión pero hay cookies, restaurarla
if (!isset($_SESSION['usuario']) && isset($_COOKIE['google_id'])) {
    $_SESSION['usuario'] = [
        'id' => $_COOKIE['google_id'],
        'nombre' => $_COOKIE['nombre'],
        'email' => $_COOKIE['email']
    ];
}

// Redirigir al login si no hay sesión ni cookies
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

echo "Bienvenido, " . htmlspecialchars($_SESSION['usuario']['nombre']);
echo "<br><a href='logout.php'>Cerrar sesión</a>";
