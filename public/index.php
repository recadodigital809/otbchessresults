<!-- index.php -->
<?php

require_once __DIR__ . '/templates/header.php';


?>

<div class="container mt-5 text-center">
    <h1>Welcome to Over The Board Chess Results</h1>
    <p class="lead">Manage players, tournaments and matches easily.</p>
    <img src="img\shutterstock_22825027.jpg" alt="Chess Piece" class="img-fluid my-3" style="width: 50%;">
    <div>
        <a href="nuevo_torneo.php" class="btn btn-primary btn-sm">New Tournament</a>
        <a href="agregar_jugadores_torneo.php" class="btn btn-success btn-sm">Add Chess Player</a>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>