<?php
session_start();
session_destroy();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Logout Test</title>
</head>

<body>
    <h2>Sesión destruida (pero cookie remember_token permanece).</h2>
    <p><a href="resultados.php?torneo_id=60">Probar acceso a resultados.php</a></p>
</body>

</html>