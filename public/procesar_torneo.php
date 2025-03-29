<!-- procesar_torneo.php -->
<?php
require_once __DIR__ . "/database/connection.php";

require_once 'algoritmo_roundrobin.php';


$action = $_POST['action'] ?? '';
$torneo_id = filter_var($_POST['torneo_id'] ?? '', FILTER_VALIDATE_INT);

if (!$torneo_id) {
    die("ID de torneo inválido");
}

try {
    if ($action === "listar_jugadores") {
        $sql = "SELECT j.id, j.nombre, j.elo 
                FROM db_Jugadores j 
                INNER JOIN db_TorneoJugadores tj ON j.id = tj.jugador_id 
                WHERE tj.torneo_id = ? ORDER BY j.elo DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$torneo_id]);
        $jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $contador = 1;
        foreach ($jugadores as $row) {
            echo "<tr>
                    <td>{$contador}</td>
                    <td>" . htmlspecialchars($row['nombre']) . "</td>
                    <td>{$row['elo']}</td>
                    <td><button class='btn btn-danger btn-sm eliminar-jugador' data-id='{$row['id']}'>Eliminar</button></td>
                  </tr>";
            $contador++;
        }
    }

    if ($action === "listar_disponibles") {
        $sql = "SELECT id, nombre, elo FROM db_Jugadores 
                WHERE id NOT IN (SELECT jugador_id FROM db_TorneoJugadores WHERE torneo_id = ?) 
                ORDER BY nombre";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$torneo_id]);
        $jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<option value=''>-- Seleccione un jugador --</option>";
        foreach ($jugadores as $row) {
            echo "<option value='{$row['id']}'>" . htmlspecialchars($row['nombre']) . " (ELO: {$row['elo']})</option>";
        }
    }

    if ($action === "agregar_jugador") {
        $jugador_id = filter_var($_POST['jugador_id'] ?? '', FILTER_VALIDATE_INT);
        if (!$jugador_id) {
            die("ID de jugador inválido");
        }

        $sql = "INSERT INTO db_TorneoJugadores (torneo_id, jugador_id) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        echo $stmt->execute([$torneo_id, $jugador_id]) ? "Jugador agregado." : "Error al agregar.";
    }

    if ($action === "eliminar_jugador") {
        $jugador_id = filter_var($_POST['jugador_id'] ?? '', FILTER_VALIDATE_INT);
        if (!$jugador_id) {
            die("ID de jugador inválido");
        }

        $sql = "DELETE FROM db_TorneoJugadores WHERE torneo_id = ? AND jugador_id = ?";
        $stmt = $pdo->prepare($sql);
        echo $stmt->execute([$torneo_id, $jugador_id]) ? "Jugador eliminado." : "Error al eliminar.";
    }

    if ($action === "iniciar_torneo") {
        $pdo->beginTransaction();

        $updateSql = "UPDATE db_Torneos SET estado = 'en curso' WHERE id = ?";
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute([$torneo_id]);

        $torneoSql = "SELECT sistema, dobleronda FROM db_Torneos WHERE id = ?";
        $stmt = $pdo->prepare($torneoSql);
        $stmt->execute([$torneo_id]);
        $torneoData = $stmt->fetch(PDO::FETCH_ASSOC);
        $sistema = $torneoData['sistema'];
        $dobleRonda = $torneoData['dobleronda'];

        if ($sistema === 'round robin') {
            $jugadoresSql = "SELECT j.id FROM db_Jugadores j
                            INNER JOIN db_TorneoJugadores tj ON j.id = tj.jugador_id
                            WHERE tj.torneo_id = ?";
            $stmt = $pdo->prepare($jugadoresSql);
            $stmt->execute([$torneo_id]);
            $playerIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (count($playerIds) < 2) {
                throw new Exception("Se necesitan al menos 2 jugadores");
            }

            $rondas = generateRoundRobinRounds($playerIds);

            $insertPartida = $pdo->prepare("INSERT INTO db_Partidas 
                (torneo_id, jugador_blancas, jugador_negras, ronda, tablero) 
                VALUES (?, ?, ?, ?, ?)");

            $numeroRonda = 1;
            foreach ($rondas as $ronda) {
                $numeroTablero = 1;
                foreach ($ronda as $partida) {
                    $insertPartida->execute([$torneo_id, $partida['blancas'], $partida['negras'], $numeroRonda, $numeroTablero]);
                    $numeroTablero++;
                }
                $numeroRonda++;
            }

            if ($dobleRonda) {
                foreach ($rondas as $ronda) {
                    $numeroTablero = 1;
                    foreach ($ronda as $partida) {
                        $insertPartida->execute([$torneo_id, $partida['negras'], $partida['blancas'], $numeroRonda, $numeroTablero]);
                        $numeroTablero++;
                    }
                    $numeroRonda++;
                }
            }
        }

        $pdo->commit();
        echo "Torneo iniciado correctamente.";
    }
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
?>