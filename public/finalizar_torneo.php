<?php
require_once __DIR__ . "/database/connection.php";
session_start();

header('Content-Type: application/json');

// Validar ID de torneo
$torneo_id = filter_input(INPUT_POST, 'torneo_id', FILTER_VALIDATE_INT);
if (!$torneo_id) {
    echo json_encode(["error" => "ID de torneo inválido"]);
    exit;
}

try {
    // Buscar partidas sin resultado
    $stmt = $pdo->prepare("SELECT ronda, tablero FROM db_Partidas WHERE torneo_id = ? AND (resultado IS NULL OR resultado = '')");
    $stmt->execute([$torneo_id]);
    $partidas_sin_resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($partidas_sin_resultado)) {
        echo json_encode([
            "error" => "Hay partidas sin resultado. No se puede finalizar el torneo.",
            "partidas" => $partidas_sin_resultado
        ]);
        exit;
    }

    // Finalizar torneo
    unset($_SESSION['active_round']);

    $stmt = $pdo->prepare("UPDATE db_Torneos SET estado = 'finalizado' WHERE id = ?");
    $stmt->execute([$torneo_id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['exito'] = "Torneo finalizado exitosamente";
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["error" => "No se pudo finalizar el torneo."]);
    }
} catch (Exception $e) {
    echo json_encode(["error" => "Error del servidor: " . $e->getMessage()]);
}
