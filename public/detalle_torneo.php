<?php
require_once __DIR__ . "/database/connection.php";
include __DIR__ . "/templates/header.php";
session_start();

// Validación básica
$torneo_id = $_GET['torneo_id'] ?? '';
$jugador_id = $_GET['jugador_id'] ?? '';

if (empty($torneo_id) || empty($jugador_id)) {
    echo "<div class='alert alert-warning m-4'>Faltan parámetros para mostrar el detalle del torneo.</div>";
    exit;
}

// Obtener partidas del jugador en el torneo
$query = "
    SELECT *
    FROM vw_Partidas
    WHERE torneo_id = :torneo_id
    AND (:jugador_id IN (jugador_blancas_id, jugador_negras_id))
    ORDER BY ronda ASC, tablero ASC
";
$stmt = $pdo->prepare($query);
$stmt->execute([
    ':torneo_id' => $torneo_id,
    ':jugador_id' => $jugador_id
]);
$partidas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener el nombre del jugador
$query_nombre = "SELECT nombre FROM db_Jugadores WHERE id = :jugador_id";
$stmt_nombre = $pdo->prepare($query_nombre);
$stmt_nombre->execute([':jugador_id' => $jugador_id]);
$jugador_nombre = $stmt_nombre->fetchColumn();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Detalle del Torneo</title>
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

        #tabla-resultados-jugador {
            display: <?= empty($torneo_id) ? 'none' : 'table' ?>;
        }

        @media (max-width: 600px) {
            #tabla-resultados {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2 class="mb-4">Partidas de <?= htmlspecialchars($jugador_nombre) ?></h2>

        <?php if (count($partidas) === 0): ?>
            <div class="alert alert-info">No se encontraron partidas para este jugador en el torneo seleccionado.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="tabla-resultados-jugador">

                    <thead class="table-dark">
                        <tr>
                            <th>Ronda</th>
                            <!-- <th>Tablero</th> -->
                            <th>Blancas</th>
                            <th>Negras</th>
                            <th>Logro</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($partidas as $p): ?>
                            <?php
                            $esBlancas = $p['jugador_blancas_id'] == $jugador_id;
                            $esNegras = $p['jugador_negras_id'] == $jugador_id;
                            $resultado_icono = '';

                            if ($p['resultado'] === '1-0') {
                                $resultado_icono = $esBlancas ? '✅' : '❌';
                            } elseif ($p['resultado'] === '0-1') {
                                $resultado_icono = $esNegras ? '✅' : '❌';
                            } elseif ($p['resultado'] === '½-½') {
                                $resultado_icono = '🤝';
                            }
                            ?>
                            <tr class="<?= $esBlancas || $esNegras ? 'table-primary' : '' ?>">
                                <td><?= htmlspecialchars($p['ronda']) ?></td>

                                <td><?= htmlspecialchars($p['jugador_blancas']) ?></td>
                                <td><?= htmlspecialchars($p['jugador_negras']) ?></td>
                                <td><?= htmlspecialchars($p['resultado']) ?></td>
                                <td><?= $resultado_icono ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <a style="margin-bottom: 30px;" href="resultados.php?torneo_id=<?= urlencode($torneo_id) ?>" class="btn btn-secondary mt-3">⬅ Volver a Resultados</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include __DIR__ . '/templates/footer.php'; ?>
</body>

</html>