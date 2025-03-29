<?php

// Limpiar buffer de salida
ob_start();

include 'config.php';

header('Content-Type: application/json');

try {
    // Verificar si hay errores de conexión
    if ($conn->connect_error) {
        throw new Exception("Error de conexión a la base de datos: " . $conn->connect_error);
    }

    // Obtener y validar parámetros
    $partida_id = filter_input(INPUT_POST, 'partida_id', FILTER_VALIDATE_INT);
    $resultado = filter_input(INPUT_POST, 'resultado', FILTER_SANITIZE_SPECIAL_CHARS);
    $torneo_id = filter_input(INPUT_POST, 'torneo_id', FILTER_VALIDATE_INT);

    if (!$partida_id || !$torneo_id) {
        throw new Exception("Parámetros inválidos");
    }

    // Convertir resultado vacío a NULL
    $resultado = ($resultado === '') ? null : $resultado;

    if ($resultado && !in_array($resultado, ['1-0', '0-1', '½-½'])) {
        throw new Exception("Resultado no válido");
    }

    // Ejecutar consulta
    $stmt = $conn->prepare("UPDATE db_Partidas SET resultado = ? WHERE id = ? AND torneo_id = ?");
    if (!$stmt) {
        throw new Exception("Error en preparación de consulta: " . $conn->error);
    }

    $stmt->bind_param("sii", $resultado, $partida_id, $torneo_id);

    if (!$stmt->execute()) {
        throw new Exception("Error ejecutando consulta: " . $stmt->error);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage()); // Log en el servidor
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
// Limpiar cualquier salida previa
ob_end_clean();
echo json_encode(['success' => true]);
