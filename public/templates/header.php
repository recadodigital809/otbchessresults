<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTBChessResults.com</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Agregar jQuery (no necesario en este caso, ya que Bootstrap lo incluye en bootstrap.bundle.min.js) -->
    <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->

    <script>
        $(document).ready(function() {
            console.log("jQuery cargado y listo para usar.");
        });
    </script>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">OTBChessResults.com</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>

                    <!-- Dropdown para Torneos -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="dropdownTorneos" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Torneos
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="dropdownTorneos">
                            <li><a class="dropdown-item" href="nuevo_torneo.php">Nuevo Torneo</a></li>
                            <li><a class="dropdown-item" href="agregar_jugadores_torneo.php">Agregar jugador</a></li>
                            <li><a class="dropdown-item" href="partidas.php">Emparejamiento</a></li>
                        </ul>
                    </li>

                    <!-- Dropdown para Jugadores -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="dropdownJugadores" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            jugadores
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="dropdownJugadores">
                            <li><a class="dropdown-item" href="ingresar_jugadores.php">Agregar jugador</a></li>
                            <!-- <li><a class="dropdown-item" href="profile.php">Perfil Jugador</a></li> -->
                            <li><a class="dropdown-item" href="listado_jugadores.php">Lista de jugadores</a></li>
                        </ul>
                    </li>

                    <li class="nav-item"><a class="nav-link" href="resultados.php">Resultados</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Bootstrap JS (incluye Popper.js y los componentes de Bootstrap) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>

</body>

</html>