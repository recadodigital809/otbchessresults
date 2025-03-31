<!-- index.php -->
<?php

require_once __DIR__ . '/templates/header.php';


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex: 1;
        }
        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 1rem;
            margin-top: auto;
        }
    </style>
</head>
<body>
<div class="container mt-5 text-center">
    <h1>Welcome to Over The Board Chess Results</h1>
    <p class="lead">Manage players, tournaments and matches easily.</p>
    <img src="img\shutterstock_22825027.jpg" alt="Chess Piece" class="img-fluid my-3" style="width: 50%;">
    <div>
        <a href="agregar_liga.php" class="btn btn-success btn-sm">Add Team</a>
        <a href="nuevo_torneo.php" class="btn btn-primary btn-sm">New Tournament</a>
     
    </div>
</div>
<?php include __DIR__ . '/templates/footer.php'; ?>
</body>
</html>
