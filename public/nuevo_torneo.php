<?php
// Verificar autenticación y permisos
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

// Conexión a la base de datos
require_once __DIR__ . "/database/connection.php";  // Asegúrate de que este archivo define `$pdo`

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("<div class='alert alert-danger'>Error de seguridad (CSRF inválido).</div>");
    }

    // Obtener los datos del formulario
    $nombre = trim($_POST["nombre"]);
    $fecha_inicio = DateTime::createFromFormat('Y-m-d', $_POST["fecha_inicio"]);

    if (!$fecha_inicio) {
        $mensaje = "<div class='alert alert-danger'>Fecha de inicio inválida.</div>";
    } else {
        $fecha_formateada = $fecha_inicio->format('Y-m-d');
        $tipo = trim($_POST["tipo"]);
        $sistema = trim($_POST["sistema"]);
        $dobleronda = isset($_POST["dobleronda"]) ? 1 : 0;

        try {
            // Uso de consultas preparadas con PDO
            $sql = "INSERT INTO db_Torneos (nombre, fecha_inicio, tipo, sistema, dobleronda) 
                    VALUES (:nombre, :fecha_inicio, :tipo, :sistema, :dobleronda)";
            $stmt = $pdo->prepare($sql);

            // Bind parameters con tipos explícitos
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':fecha_inicio', $fecha_formateada, PDO::PARAM_STR);
            $stmt->bindParam(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->bindParam(':sistema', $sistema, PDO::PARAM_STR);
            $stmt->bindParam(':dobleronda', $dobleronda, PDO::PARAM_INT);

            // Ejecutar la consulta
            if ($stmt->execute()) {
                $mensaje = "<div class='alert alert-success'>Torneo agregado con éxito.</div>";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error: No se pudo agregar el torneo.</div>";
            }
        } catch (PDOException $e) {
            $mensaje = "<div class='alert alert-danger'>Error en la base de datos: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
?>

<div class="container mt-5">
    <h2 class="text-center">Agregar Nuevo Torneo</h2>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <?= htmlspecialchars($mensaje); ?>
            <div class="card shadow-sm p-4">
                <form method="post">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                    <div class="mb-3">
                        <label class="form-label">Nombre del Torneo</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Torneo</label>
                        <select name="tipo" class="form-select" required>
                            <option value="presencial">Presencial</option>
                            <option value="online">Online</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sistema de Juego</label>
                        <select name="sistema" class="form-select" id="sistema" required>
                            <option value="round robin">Round Robin</option>
                            <option value="sistema suizo">Sistema Suizo</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Configuración de Rondas</label>
                            <div class="form-check mt-2">
                                <input type="checkbox" name="dobleronda" class="form-check-input" id="dobleronda">
                                <label class="form-check-label" for="dobleronda">Doble Ronda?</label>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-trophy-fill me-2"></i> Crear Torneo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $("#sistema").change(function() {
            if ($(this).val() === "round robin") {
                $("#dobleronda").prop("disabled", false);
            } else {
                $("#dobleronda").prop("disabled", true).prop("checked", false);
            }
        });
    });
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>