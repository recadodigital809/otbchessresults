<?php
session_start();
require_once __DIR__ . "/database/connection.php";

$response = ['success' => false, 'error' => ''];

try {
    if (empty($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }

    $active_round = filter_input(INPUT_POST, 'active_round', FILTER_VALIDATE_INT);
    if ($active_round === false || $active_round < 1) {
        throw new Exception('Invalid round number');
    }

    $_SESSION['active_round'] = $active_round;
    $response['success'] = true;
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
