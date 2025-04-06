<?php
// Iniciar la sesión y verificar autenticación
session_start();
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

// Obtener la ronda activa desde la solicitud
$active_round = $_POST['active_round'] ?? null;
if ($active_round === null) {
    echo json_encode(['success' => false, 'error' => 'Ronda no válida']);
    exit;
}

// Actualizar la ronda activa en la base de datos o en la sesión
try {
    $_SESSION['active_round'] = $active_round; // Asumimos que se guarda en sesión
    // Si es necesario, realiza una actualización en la base de datos aquí
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
