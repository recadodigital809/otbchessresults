<?php

require_once __DIR__ . "/database/connection.php";
include __DIR__ . '/templates/header.php';

// Obtener criterio de ordenación
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'elo';
$order_dir = isset($_GET['order_dir']) && $_GET['order_dir'] === 'asc' ? 'ASC' : 'DESC';

// Validar que la columna de ordenación sea permitida
$allowed_columns = ['nombre', 'elo'];
if (!in_array($order_by, $allowed_columns)) {
    $order_by = 'elo';
}

try {
    // Consulta segura con valores validados
    $sql = "SELECT id, nombre, elo FROM db_Jugadores ORDER BY $order_by $order_dir";
    $result = $pdo->query($sql); // Cambié $pdoo a $pdo
} catch (PDOException $e) {
    die("Error al obtener los jugadores: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Jugadores</title>
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

        h1 {
            text-align: center;
        }

        table {
            width: 80%;
            border-collapse: collapse;
            margin: 20px auto;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background-color: #333;
            color: white;
        }

        th a {
            color: white;
            text-decoration: none;
        }

        tr:hover {
            background-color: #f1f1f1;
        }
        @media (max-width: 600px) {
            table {
                font-size: 14px;
            }
}
    </style>
</head>

<body>
    <h1 style="padding-top:10px;">Listado de Jugadores</h1>
    <div style="overflow-x:auto;">
    <table>
        <tr>
            <th><a href="?order_by=id&order_dir=asc">ID</a></th>
            <th><a href="?order_by=nombre&order_dir=asc">Nombre</a></th>
            <th><a href="?order_by=elo&order_dir=asc">ELO</a></th>
        </tr>
<?php
$rows = $result->fetchAll(PDO::FETCH_ASSOC);
if (count($rows) > 0):
    foreach ($rows as $row):
?>
    <tr>
        <td><?= htmlspecialchars($row["id"]) ?></td>
        <td><?= htmlspecialchars($row["nombre"]) ?></td>
        <td><?= htmlspecialchars($row["elo"]) ?></td>
    </tr>
<?php
    endforeach;
else:
?>
    <tr>
        <td colspan="4" style="text-align: center;">No hay jugadores registrados</td>
    </tr>
<?php endif; ?>
    </table>
</div>
    <?php include __DIR__ . '/templates/footer.php'; ?>
</body>

</html>
