<?php
require_once __DIR__ . "/database/connection.php";
include __DIR__ . '/templates/header.php';

// Verificar autenticación Google
session_start();
if (empty($_SESSION['user_id'])) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$torneo_id = $_GET['torneo_id'] ?? '';
$resultados = [];

if (!empty($torneo_id)) {
    // Obtener los resultados solo si hay torneo seleccionado
    $query_resultados = "SELECT * FROM vw_PuntosTorneos WHERE torneo_id = :torneo_id";
    $stmt_resultados = $pdo->prepare($query_resultados);
    $stmt_resultados->execute([':torneo_id' => $torneo_id]);
    $resultados = $stmt_resultados->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Resultado del Torneo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        /* Mostrar u ocultar tabla según si hay torneo seleccionado */
        #tabla-resultados {
            display: <?= empty($torneo_id) ? 'none' : 'table' ?>;
        }

        /* Estilo responsivo para móviles */
        @media (max-width: 600px) {
            #tabla-resultados {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2 class="mb-4">Resultados de Torneos</h2>
        <!-- Selector de Torneo -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3" id="form-torneo">
                    <div class="col-md-8">
                        <select name="torneo_id" class="form-select" required>
                            <option value="">-- Seleccionar Torneo Activo --</option>
                            <?php
                            $query_torneos = "SELECT id, nombre FROM db_Torneos WHERE estado <> 'creado'";
                            if (!empty($_SESSION['user_id'])) {
                                $query_torneos .= " AND created_id = :user_id";
                            }
                            $query_torneos .= " ORDER BY fecha_inicio DESC, nombre ASC";

                            $stmt = $pdo->prepare($query_torneos);
                            $stmt->execute([':user_id' => $_SESSION['user_id'] ?? null]);
                            while ($t = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                                <option value="<?= $t['id'] ?>" <?= $t['id'] == $torneo_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['nombre']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100" id="btn-ver-resultados">
                            <i class="bi bi-arrow-clockwise"></i> Ver Resultados
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <!-- Tabla de Resultados (inicialmente oculta) -->
            <table class="table table-bordered table-striped" id="tabla-resultados">
                <thead class="table-dark">
                    <tr>
                        <th>Player</th>
                        <th>ELO</th>
                        <th>Win</th>
                        <th>Tie</th>
                        <th>Lose</th>
                        <th>Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados as $row): ?>
                        <tr>
                            <td>
                                <a href="detalle_torneo.php?torneo_id=<?= urlencode($torneo_id) ?>&jugador_id=<?= urlencode($row['jugador_id']) ?>">
                                    <?= htmlspecialchars($row['jugador']) ?>
                                </a>
                            </td>
                            <td><?= $row['elo'] ?></td>
                            <td><?= $row['victorias'] ?></td>
                            <td><?= $row['empates'] ?></td>
                            <td><?= $row['derrotas'] ?></td>
                            <td><?= $row['puntos'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.getElementById("form-torneo");
            const selectTorneo = form.querySelector("select[name='torneo_id']");
            const tablaResultados = document.getElementById("tabla-resultados");

            form.addEventListener("submit", function(e) {
                if (selectTorneo.value === "") {
                    alert("Por favor, seleccione un torneo antes de ver los resultados.");
                    e.preventDefault(); // Evita que el formulario se envíe
                }
            });

            if (selectTorneo.value !== "") {
                tablaResultados.style.display = "table";
            }
        });
    </script>

    <?php include __DIR__ . '/templates/footer.php'; ?>
</body>

</html>