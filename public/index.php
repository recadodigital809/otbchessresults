<!-- index.php -->
<?php

require_once __DIR__ . '/templates/header.php';

session_start();
?>
<!DOCTYPE html>
<html lang="es">
<title>OTBChessResults.com</title>

<head>
    <style>
        html,
        body {
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

        @media (max-width: 768px) {
            .responsive-img-mobile {
                width: 80% !important;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-5 text-center">

        <h1>Bienvenido a Over The Board Chess Results</h1>
        <p class="lead">Gestiona ligas, jugadores, torneos y partidas fácilmente.</p>
        <!-- <img src="img\shutterstock_22825027.jpg" alt="Chess Piece" class="img-fluid my-3" style="width: 50%;"> -->
        <img src="assets/img/otb_chessresults.png" alt="Chess Piece" class="img-fluid my-3 responsive-img-mobile" style="width: 50%;">
        <div class="gap-2">
            <a href="agregar_liga.php" class="btn btn-success btn-sm text-center" style="min-width: 130px;">Agregar Liga</a>
            <a href="nuevo_torneo.php" class="btn btn-primary btn-sm text-center" style="min-width: 130px;">Nuevo Torneo</a>
        </div>

    </div>
    <?php include __DIR__ . '/templates/footer.php'; ?>
</body>

</html>