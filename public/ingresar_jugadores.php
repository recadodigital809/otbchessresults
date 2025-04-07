<?php
require_once __DIR__ . "/database/connection.php";
include __DIR__ . '/templates/header.php';

// Verificar autenticación Google
session_start();
// Autenticación por remember_token si no hay sesión activa
if (empty($_SESSION['user_id'])) {
    if (!empty($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];

        $stmt = $pdo->prepare("SELECT id FROM db_Usuarios WHERE remember_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
        } else {
            // Token inválido → redirigir al login
            header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    } else {
        // No hay sesión ni cookie → redirigir al login
        header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

$mensaje = "";

// Obtener todas las ligas existentes para mostrarlas en el formulario
$sqlLigas = "SELECT id, nombre FROM db_Liga";
$stmtLigas = $pdo->query($sqlLigas);
$ligas = $stmtLigas->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST["nombre"];
    $elo = $_POST["elo"];
    $ligasSeleccionadas = $_POST["ligas"];  // Ligas seleccionadas

    if (!empty($nombre)) {
        // Usar prepared statements con PDO para agregar el jugador
        $sql = "INSERT INTO db_Jugadores (nombre, elo) VALUES (:nombre, :elo)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':elo', $elo, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $jugador_id = $pdo->lastInsertId(); // Obtener el ID del jugador recién insertado

            // Insertar las relaciones entre el jugador y las ligas seleccionadas
            if (!empty($ligasSeleccionadas)) {
                $sqlRelaciones = "INSERT INTO db_Liga_Jugadores (jugador_id, liga_id) VALUES (:jugador_id, :liga_id)";
                $stmtRelaciones = $pdo->prepare($sqlRelaciones);

                foreach ($ligasSeleccionadas as $liga_id) {
                    $stmtRelaciones->bindParam(':jugador_id', $jugador_id, PDO::PARAM_INT);
                    $stmtRelaciones->bindParam(':liga_id', $liga_id, PDO::PARAM_INT);
                    $stmtRelaciones->execute();
                }
            }

            $mensaje = "<div class='alert alert-success'>Jugador agregado correctamente.</div>";
            // Redirigir después de insertar
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al agregar el jugador.</div>";
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>El nombre es obligatorio.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar Jugadores</title>
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

<div class="container mt-5">
    <h2 class="text-center">Agregar Jugador</h2>
    <?= $mensaje; ?>

    <div class="card shadow-sm p-4">
        <form id="formJugador" method="post">
            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" id="nombre" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Elo (Opcional)</label>
                <input type="number" name="elo" id="elo" class="form-control" value="0">
            </div>
            <div class="mb-3">
                <label class="form-label">Seleccionar Ligas</label>
                <select name="ligas[]" id="ligas" class="form-control" multiple>
                    <?php foreach ($ligas as $liga): ?>
                        <option value="<?= $liga['id']; ?>"><?= $liga['nombre']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-success w-100">Agregar Jugador</button>
        </form>
    </div>
</div>

<script>
    $("#formJugador").submit(function(event) {
        if ($("#nombre").val().trim() === "") {
            alert("El nombre es obligatorio.");
            event.preventDefault();
        }
    });
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
</body>
</html>

