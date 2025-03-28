<?php
session_start();

// 1. Limpiar todas las variables de sesión
$_SESSION = [];

// 2. Destruir sesión
session_unset();
session_destroy();

// 3. Eliminar la cookie de sesión
setcookie(session_name(), '', time() - 3600, '/', 'otbchessresults.com', true, true);

// 4. (Opcional) Eliminar cookies de usuario si se usan
if (isset($_COOKIE['google_id'])) {
    setcookie("google_id", "", time() - 3600, "/", "otbchessresults.com", true, true);
}
if (isset($_COOKIE['nombre'])) {
    setcookie("nombre", "", time() - 3600, "/", "otbchessresults.com", true, true);
}
if (isset($_COOKIE['email'])) {
    setcookie("email", "", time() - 3600, "/", "otbchessresults.com", true, true);
}

// 5. Regenerar la sesión para prevenir reutilización de ID
session_regenerate_id(true);

// 6. Redirigir al login
header("Location: login.php");
exit();
