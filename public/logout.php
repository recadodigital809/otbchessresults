<?php
session_start();

// Regenerar el ID de sesión antes de destruirla (seguridad extra)
session_regenerate_id(true);

// Eliminar todas las variables de sesión
$_SESSION = [];

// Eliminar las cookies de sesión (si las hay)
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), "", time() - 42000, "/", "otbchessresults.com", true, true);
}

// Destruir la sesión en el servidor
session_destroy();

// Eliminar cookies de usuario
$cookieParams = [
    'expires' => time() - 3600,
    'path' => '/',
    'domain' => 'otbchessresults.com',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
];

setcookie("google_id", "", $cookieParams);
setcookie("nombre", "", $cookieParams);
setcookie("email", "", $cookieParams);

// Redirección segura a login
header("Location: login.php");
exit();
