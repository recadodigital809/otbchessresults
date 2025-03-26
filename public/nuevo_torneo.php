<?php
require 'vendor/autoload.php';

require_once __DIR__ . '/database/connection.php';

include __DIR__ . '/templates/header.php';

// Verificar autenticación y permisos
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: /login.php');
    exit();
}


// Configurar conexión a base de datos
// $conn = Database::getInstance();

// Generar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validar CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Token CSRF inválido");
        }

        // Validación de entrada estricta
        $nombre = filter_input(INPUT_POST, "nombre", FILTER_SANITIZE_STRING);
        $fecha_inicio = filter_input(INPUT_POST, "fecha_inicio", FILTER_SANITIZE_STRING);
        $tipo = filter_input(INPUT_POST, "tipo", FILTER_SANITIZE_STRING);
        $sistema = filter_input(INPUT_POST, "sistema", FILTER_SANITIZE_STRING);
        $dobleronda = isset($_POST["dobleronda"]) ? 1 : 0;

        // Validar campos requeridos
        if (empty($nombre) || strlen($nombre) > 100) {
            throw new Exception("Nombre inválido (máx. 100 caracteres)");
        }

        // Validar fecha futura
        $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha_inicio);
        $fecha_actual = new DateTime();
        if (!$fecha_obj || $fecha_obj < $fecha_actual) {
            throw new Exception("Fecha de inicio inválida o anterior a hoy");
        }
        $fecha_formateada = $fecha_obj->format('Y-m-d');

        // Validar valores permitidos
        $tipos_permitidos = ['presencial', 'online'];
        $sistemas_permitidos = ['round robin', 'sistema suizo', 'eliminación simple', 'eliminación doble'];

        if (!in_array($tipo, $tipos_permitidos)) {
            throw new Exception("Tipo de torneo no válido");
        }

        if (!in_array($sistema, $sistemas_permitidos)) {
            throw new Exception("Sistema de juego no válido");
        }

        // Validar lógica de doble ronda
        if ($dobleronda && $sistema !== 'round robin') {
            throw new Exception("Doble ronda solo disponible para Round Robin");
        }

        // Transacción de base de datos
        $conn->beginTransaction();

        try {
            $sql = "INSERT INTO db_Torneos 
                    (nombre, fecha_inicio, tipo, sistema, dobleronda, creador_id) 
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $nombre,
                $fecha_formateada,
                $tipo,
                $sistema,
                $dobleronda,
                $_SESSION['usuario']['id']
            ]);

            $conn->commit();

            // Redirección para evitar reenvío de formulario
            $_SESSION['exito'] = "Torneo creado exitosamente";
            header("Location: /dashboard.php");
            exit();
        } catch (PDOException $e) {
            $conn->rollBack();
            throw new Exception("Error al guardar en base de datos: " . $e->getMessage());
        }
    } catch (Exception $e) {
        error_log("Error en nuevo_torneo: " . $e->getMessage() . " - Usuario: " . $_SESSION['usuario']['id']);
        $mensaje = "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Incluir vista
include __DIR__ . '/templates/header.php';
?>

<div class="container mt-5">
    <h2 class="text-center mb-4">Crear Nuevo Torneo</h2>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-body">
                    <?= $mensaje ?>

                    <form method="post" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="row g-3">
                            <!-- Nombre -->
                            <div class="col-md-12">
                                <label for="nombre" class="form-label">Nombre del Torneo *</label>
                                <input type="text" class="form-control"
                                    name="nombre" id="nombre"
                                    required maxlength="100"
                                    pattern="[A-Za-zÁ-ú0-9\s\-]{5,100}">
                                <div class="invalid-feedback">
                                    Nombre requerido (5-100 caracteres)
                                </div>
                            </div>

                            <!-- Fecha y Tipo -->
                            <div class="col-md-6">
                                <label for="fecha_inicio" class="form-label">Fecha Inicio *</label>
                                <input type="date" class="form-control"
                                    name="fecha_inicio" id="fecha_inicio"
                                    min="<?= date('Y-m-d') ?>" required>
                                <div class="invalid-feedback">
                                    Seleccione una fecha válida
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="tipo" class="form-label">Modalidad *</label>
                                <select class="form-select" name="tipo" id="tipo" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="presencial">Presencial</option>
                                    <option value="online">Online</option>
                                </select>
                            </div>

                            <!-- Sistema y Configuraciones -->
                            <div class="col-md-6">
                                <label for="sistema" class="form-label">Sistema de Juego *</label>
                                <select class="form-select" name="sistema" id="sistema" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="round robin">Round Robin</option>
                                    <option value="sistema suizo">Sistema Suizo</option>
                                    <option value="eliminación simple">Eliminación Simple</option>
                                    <option value="eliminación doble">Eliminación Doble</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check mt-4 pt-3">
                                    <input class="form-check-input" type="checkbox"
                                        name="dobleronda" id="dobleronda"
                                        disabled>
                                    <label class="form-check-label" for="dobleronda">
                                        Doble Ronda
                                    </label>
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary w-100 btn-lg">
                                    <i class="bi bi-save me-2"></i>Crear Torneo
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Validación en tiempo real
        const forms = document.querySelectorAll('form');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })

        // Control del checkbox
        const sistemaSelect = document.getElementById('sistema');
        const dobleRondaCheck = document.getElementById('dobleronda');

        sistemaSelect.addEventListener('change', function() {
            if (this.value === 'round robin') {
                dobleRondaCheck.disabled = false;
            } else {
                dobleRondaCheck.disabled = true;
                dobleRondaCheck.checked = false;
            }
        });
    });
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>