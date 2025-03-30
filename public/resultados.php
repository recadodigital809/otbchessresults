<?php
require_once __DIR__ . "/database/connection.php";
include __DIR__ . '/templates/header.php';

// Obtener la lista de torneos para el filtro
$query_torneos = "SELECT id as torneo_id, nombre as torneo FROM db_Torneos where estado <>'creado' ORDER BY fecha_inicio desc, nombre asc";
$stmt_torneos = $pdo->prepare($query_torneos);
$stmt_torneos->execute();
$torneos = $stmt_torneos->fetchAll(PDO::FETCH_ASSOC);

$torneo_id = $_GET['torneo_id'] ?? '';

// Obtener los resultados filtrados
$query_resultados = "SELECT * FROM vw_PuntosTorneos";
$params = [];

if (!empty($torneo_id)) {
    $query_resultados .= " WHERE torneo_id = :torneo_id";
    $params[':torneo_id'] = $torneo_id;
}

$stmt_resultados = $pdo->prepare($query_resultados);
$stmt_resultados->execute($params);
$resultados = $stmt_resultados->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Torneos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-4">
        <h2 class="mb-4">Resultados de Torneos</h2>
        <form method="GET" class="mb-3">
            <label for="torneo_id" class="form-label">Selecciona un Torneo:</label>
            <select name="torneo_id" id="torneo_id" class="form-select" onchange="this.form.submit()">
                <option value="">Todos</option>
                <?php foreach ($torneos as $torneo): ?>
                    <option value="<?= $torneo['torneo_id'] ?>" <?= ($torneo_id == $torneo['torneo_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($torneo['torneo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Jugador</th>
                    <th>ELO</th>
                    <th>Victorias</th>
                    <th>Empates</th>
                    <th>Derrotas</th>
                    <th>Puntos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultados as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['jugador']) ?></td>
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
</body>

</html>
<?php
include __DIR__ . '/templates/footer.php';
