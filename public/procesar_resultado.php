<?php
header('Content-Type: application/json'); // ¡Primera línea!
session_start();

require_once __DIR__ . "/database/connection.php";

try {
    // La conexión ya es manejada por PDO, no es necesario verificarla manualmente
    // Si la conexión falla, PDO lanzará una excepción automáticamente

    // Obtener y validar parámetros
    $partida_id = filter_input(INPUT_POST, 'partida_id', FILTER_VALIDATE_INT);
    $resultado = filter_input(INPUT_POST, 'resultado', FILTER_SANITIZE_SPECIAL_CHARS);
    $torneo_id = filter_input(INPUT_POST, 'torneo_id', FILTER_VALIDATE_INT);
    $googleuser_id = $_SESSION['user_id'] ?? null;

    // throw new Exception($googleuser_id);

    // $googleuser_id = filter_input(INPUT_POST, $_SESSION['user_id'], FILTER_SANITIZE_STRING); // Asegura que el user_id es seguro


    if (!$partida_id || !$torneo_id) {
        throw new Exception("Parámetros inválidos");
    }

    // Convertir resultado vacío a NULL
    $resultado = ($resultado === '') ? null : $resultado;

    if ($resultado && !in_array($resultado, ['1-0', '0-1', '½-½'])) {
        throw new Exception("Resultado no válido");
    }

    // Ejecutar consulta (PDO)
    $stmt = $pdo->prepare("UPDATE db_Partidas SET resultado = :resultado, google_id = :google_id  WHERE id = :partida_id AND torneo_id = :torneo_id");
    if (!$stmt) {
        throw new Exception("Error en preparación de consulta: " . $pdo->errorInfo()[2]);
    }

    // Usar bindValue para PDO
    $stmt->bindValue(':resultado', $resultado, PDO::PARAM_STR);
    $stmt->bindValue(':partida_id', $partida_id, PDO::PARAM_INT);
    $stmt->bindValue(':torneo_id', $torneo_id, PDO::PARAM_INT);
    $stmt->bindValue(':google_id', $googleuser_id, PDO::PARAM_INT); // Asegura tipo correcto

    if (!$stmt->execute()) {
        throw new Exception("Error ejecutando consulta: " . implode(", ", $stmt->errorInfo()));
    }

    // Solo echo esta respuesta cuando todo ha sido exitoso
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Si ocurre un error, responde con código 500 y mensaje de error
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
