<?php
require_once __DIR__ . "/database/connection.php";

// Validar torneo_id
$torneo_id = filter_input(INPUT_POST, 'torneo_id', FILTER_VALIDATE_INT);

if (!$torneo_id) {
    echo json_encode(["error" => "ID de torneo inválido"]);
    exit;
}

try {
    // Verificar si hay partidas sin resultado
    $stmt = $pdo->prepare("SELECT COUNT(*) AS sin_resultado FROM db_Partidas WHERE torneo_id = ? AND (resultado IS NULL OR resultado = '')");
    $stmt->bindValue(1, $torneo_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result || !isset($result['sin_resultado'])) {
        echo json_encode(["error" => "Error al obtener los datos de las partidas"]);
        exit;
    }

    if ($result['sin_resultado'] > 0) {
        echo json_encode(["error" => "Hay {$result['sin_resultado']} partidas sin resultado. No se puede finalizar el torneo."]);
        exit;
    }

    // Si todas las partidas tienen resultado, finalizar el torneo
    $stmt = $pdo->prepare("UPDATE db_Torneos SET estado = 'finalizado' WHERE id = ?");
    $stmt->bindValue(1, $torneo_id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => "Torneo finalizado exitosamente"]);
        // Redirección para evitar reenvío de formulario
        $_SESSION['exito'] = "Torneo creado exitosamente";
        header("Location: agregar_jugadores_torneo.php");
        exit();
    } else {
        echo json_encode(["error" => "No se pudo finalizar el torneo"]);
    }
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
