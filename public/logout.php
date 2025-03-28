<?php
session_start();

// Eliminar datos de sesión
$_SESSION = array();

// Eliminar cookie
setcookie('remember_token', '', time() - 3600, '/');

// Destruir sesión
session_destroy();

header('Location: login.php');
exit;
