<?php
$error_code = $_GET['code'] ?? 'unknown';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
</head>

<body>
    <h2>Error de Autenticación</h2>
    <p>Hubo un problema con la autenticación. Código de error: <?= htmlspecialchars($error_code) ?></p>
    <p><a href="/">Volver al inicio</a></p>
</body>

</html>