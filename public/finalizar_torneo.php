<?php
require_once __DIR__ . "/database/connection.php";

// Validar torneo_id
$torneo_id = filter_input(INPUT_POST, 'torneo_id', FILTER_VALIDATE_INT);

if (!$torneo_id) {
    echo json_encode(["error" => "ID de torneo inválido"]);
    exit;
}

try {
    // Obtener lista de partidas sin resultado con ronda y tablero
    $stmt = $pdo->prepare("SELECT ronda, tablero FROM db_Partidas WHERE torneo_id = ? AND (resultado IS NULL OR resultado = '')");
    $stmt->bindValue(1, $torneo_id, PDO::PARAM_INT);
    $stmt->execute();
    $partidas_sin_resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($partidas_sin_resultado) > 0) {
        echo json_encode([
            "error" => "Hay partidas sin resultado. No se puede finalizar el torneo.",
            "partidas" => $partidas_sin_resultado
        ]);
        exit;
    }

    // Si todas las partidas tienen resultado, finalizar el torneo

    unset($_SESSION['active_round']);

    $stmt = $pdo->prepare("UPDATE db_Torneos SET estado = 'finalizado' WHERE id = ?");
    $stmt->bindValue(1, $torneo_id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => "Torneo finalizado exitosamente"]);
        $_SESSION['exito'] = "Torneo creado exitosamente";
        header("Location: agregar_jugadores_torneo.php");
        exit();
    } else {
        echo json_encode(["error" => "No se pudo finalizar el torneo"]);
    }
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
