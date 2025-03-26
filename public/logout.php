<?php
session_start();
session_destroy();

// Eliminar cookies
setcookie("google_id", "", time() - 3600, "/", "otbchessresults.com", true, true);
setcookie("nombre", "", time() - 3600, "/", "otbchessresults.com", true, true);
setcookie("email", "", time() - 3600, "/", "otbchessresults.com", true, true);

header("Location: login.php");
exit();
