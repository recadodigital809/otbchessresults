<?php
require_once __DIR__ . "/database/connection.php";
include __DIR__ . '/templates/header.php';

// Verificar autenticación Google
session_start();
if (empty($_SESSION['user_id'])) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Obtener parámetros
$action = $_POST['action'] ?? $_GET['action'] ?? '';
// Validar torneo_id
$torneo_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?:
    filter_input(INPUT_POST, 'torneo_id', FILTER_VALIDATE_INT) ?: 0;


// Obtener datos del torneo
$torneo = [];
$rondas = [];

try {
    $stmt = $pdo->prepare("SELECT * FROM db_Torneos WHERE id = :torneo_id");
    $stmt->execute([':torneo_id' => $torneo_id]);
    $torneo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$torneo) {
        throw new Exception("Torneo no encontrado");
    }

    $stmt = $pdo->prepare("
        SELECT p.*, 
               b.nombre AS blancas_nombre,
               n.nombre AS negras_nombre,
               COALESCE(numero_ordinal(vb.lugar), '-') AS blancas_lugar,
               COALESCE(numero_ordinal(vn.lugar), '-') AS negras_lugar
        FROM db_Partidas p
        LEFT JOIN db_Jugadores b ON p.jugador_blancas = b.id
        LEFT JOIN db_Jugadores n ON p.jugador_negras = n.id
        LEFT JOIN vw_PuntosTorneos vb ON p.jugador_blancas = vb.jugador_id AND p.torneo_id = vb.torneo_id
        LEFT JOIN vw_PuntosTorneos vn ON p.jugador_negras = vn.jugador_id AND p.torneo_id = vn.torneo_id
        WHERE p.torneo_id = ?
        ORDER BY p.ronda;
    ");
    $stmt->bindValue(1, $torneo_id, PDO::PARAM_INT);
    $stmt->execute();
    $rondas = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rondas[$row['ronda']][] = $row;
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partidas del Torneo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        .partida-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link.active {
            font-weight: bold;
            background: #f8f9fa;
        }

        .saving {
            opacity: 0.7;
            pointer-events: none;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-4">
        <!-- Selector de Torneo -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="post" class="row g-3" id="form-torneo">
                    <div class="col-md-8">
                        <select name="torneo_id" class="form-select" required>
                            <option value="">-- Seleccionar Torneo Activo --</option>
                            <?php
                            $stmt = $pdo->prepare("SELECT id, nombre FROM db_Torneos WHERE estado = 'en curso'");
                            $stmt->execute();
                            while ($t = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                                <option value="<?= $t['id'] ?>" <?= $t['id'] == $torneo_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['nombre']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-arrow-clockwise"></i> Cargar Torneo
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="mensaje-ajax"></div>

        <?php if ($torneo_id && $torneo): ?>
            <!-- Encabezado del Torneo -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h2 class="h4 mb-0">Torneo: <?= htmlspecialchars($torneo['nombre']) ?></h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <p class="mb-1"><strong>Sistema:</strong> <?= $torneo['sistema'] ?></p>
                        </div>
                        <div class="col-md-3">
                            <p class="mb-1"><strong>Estado:</strong>
                                <span id="estado-torneo" data-estado="<?= htmlspecialchars($torneo['estado']) ?>">
                                    <?= htmlspecialchars($torneo['estado']) ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-3">
                            <p class="mb-1"><strong>Rondas:</strong> <?= count($rondas) ?></p>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" id="btn-finalizar" class="btn btn-danger btn-sm">Finalizar Torneo</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Pestañas de Rondas -->
            <?php if (!empty($rondas)): ?>
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <?php foreach ($rondas as $num_ronda => $partidas_ronda): ?>
                            <button class="nav-link <?= $num_ronda == 1 ? 'active' : '' ?>"
                                data-bs-toggle="tab"
                                data-bs-target="#ronda-<?= $num_ronda ?>"
                                type="button">
                                Ronda <?= $num_ronda ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </nav>

                <!-- Contenido de Rondas -->
                <div class="tab-content" id="nav-tabContent">
                    <?php foreach ($rondas as $num_ronda => $partidas_ronda): ?>
                        <?php
                        // Ordenar las partidas por tablero antes de iterar
                        usort($partidas_ronda, function ($a, $b) {
                            return $a['tablero'] <=> $b['tablero'];
                        });
                        ?>
                        <div class="tab-pane fade <?= $num_ronda == 1 ? 'show active' : '' ?>"
                            id="ronda-<?= $num_ronda ?>">

                            <div class="row mt-4">
                                <?php foreach ($partidas_ronda as $partida): ?>
                                    <div class="col-md-6">
                                        <div class="partida-card">
                                            <span class="badge bg-secondary" style="position: relative; top: -27px;">Tablero# <?= $partida['tablero'] ?></span>
                                            <form method="POST" class="form-resultado">
                                                <input type="hidden" name="partida_id" value="<?= $partida['id'] ?>">
                                                <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">

                                                <?php
                                                $A = htmlspecialchars($partida['blancas_nombre'] ?? 'Bye');
                                                $B = htmlspecialchars($partida['negras_nombre'] ?? 'Bye');

                                                $disabled = ($A === 'Bye' || $B === 'Bye') ? 'disabled' : '';
                                                ?>

                                                <div class="row align-items-center">
                                                    <!-- Jugador Blancas -->
                                                    <div class="col-4 text-end pe-4">
                                                        <div class="fw-bold text-primary"><?= htmlspecialchars($partida['blancas_nombre'] ?? 'Bye')  ?> - <?= htmlspecialchars($partida['blancas_lugar']) ?></div>
                                                        <small class="text-muted">Blancas</small>
                                                    </div>

                                                    <!-- Resultado -->
                                                    <div class="col-4 text-center">
                                                        <select name="resultado" <?= $disabled ?> class="form-select form-select-sm" style="text-align: center; text-align-last: center;" <?= ($torneo['estado'] === 'finalizado') ? 'disabled' : '' ?>>
                                                            <option value="">-</option>
                                                            <option value="1-0" <?= $partida['resultado'] == '1-0' ? 'selected' : '' ?>>1-0</option>
                                                            <option value="0-1" <?= $partida['resultado'] == '0-1' ? 'selected' : '' ?>>0-1</option>
                                                            <option value="½-½" <?= $partida['resultado'] == '½-½' ? 'selected' : '' ?>>½-½</option>
                                                        </select>
                                                    </div>

                                                    <!-- Jugador Negras -->
                                                    <div class="col-4 text-start ps-4">
                                                        <div class="fw-bold text-dark"><?= htmlspecialchars($partida['negras_nombre'] ?? 'Bye') ?> - <?= htmlspecialchars($partida['negras_lugar']) ?></div>
                                                        <small class="text-muted">Negras</small>
                                                    </div>
                                                </div>

                                                <!-- Botton de guardar -->
                                                <div class="text-center mt-3">
                                                    <button type="submit" <?= $disabled ?> class="btn btn-sm btn-success guardar-resultado"
                                                        <?= ($torneo['estado'] === 'finalizado') ? 'disabled' : '' ?>>
                                                        <i class="bi bi-save"></i> Guardar
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <div class="alert alert-warning">No hay partidas programadas en este torneo</div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.form-resultado').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $button = $form.find('button[type="submit"]');

                $button.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Guardando...');
                $form.addClass('saving');

                $.ajax({
                    type: 'POST',
                    url: 'procesar_resultado.php',
                    data: $form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.success) {
                            $form.removeClass('saving').addClass('bg-success-light');
                            $button.html('<i class="bi bi-check-circle"></i> Guardado!');

                            setTimeout(() => {
                                $form.removeClass('bg-success-light');
                                $button.html('<i class="bi bi-save"></i> Guardado!').prop('disabled', false).addClass('bg-info');
                            }, 1000);
                        } else {
                            const errorMsg = response?.error ?? 'Error desconocido';
                            console.error('Error en la respuesta:', response);
                            $('#mensaje-ajax').html(`<div class="alert alert-danger">${errorMsg}</div>`);
                            $button.html('<i class="bi bi-x-circle"></i> Error').prop('disabled', false);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('Error en la solicitud:', {
                            status: jqXHR.status,
                            response: jqXHR.responseText,
                            textStatus: textStatus,
                            errorThrown: errorThrown
                        });

                        let errorMessage = 'Error de conexión en la respuesta del servidor';
                        try {
                            const serverResponse = JSON.parse(jqXHR.responseText);
                            if (serverResponse.error) errorMessage = serverResponse.error;
                        } catch (e) {
                            console.error('Error al analizar la respuesta JSON', e);
                        }

                        $('#mensaje-ajax').html(`<div class="alert alert-danger">${errorMessage}</div>`);
                        $button.html('<i class="bi bi-x-circle"></i> Error').prop('disabled', false);
                    },
                    complete: function() {
                        $form.removeClass('saving');
                    }
                });
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            $("#btn-finalizar").click(function(e) {
                e.preventDefault(); // Evita que el formulario se envíe de forma predeterminada

                // let torneo_id = $("#torneo_id").val(); // Asegurar que el ID está presente
                let torneo_id = $("select[name='torneo_id']").val();

                if (!torneo_id) {
                    alert("Error: No se encontró el ID del torneo.");
                    return;
                }

                $.post("finalizar_torneo.php", {
                        action: "btn-finalizar",
                        torneo_id: torneo_id
                    })
                    .done(function(response) {
                        console.log("Respuesta del servidor:", response);
                        alert("Torneo finalizado exitosamente.");
                        location.reload();
                    })
                    .fail(function(jqXHR, textStatus, errorThrown) {
                        console.error("Error en la solicitud:", textStatus, errorThrown);
                        alert("Hubo un error al finalizar el torneo.");
                    });
            });
        });
    </script>
  
    <?php include __DIR__ . '/templates/footer.php'; ?>
</body>
</html>