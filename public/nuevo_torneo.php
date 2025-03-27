<?php
require_once 'vendor/autoload.php';
require_once __DIR__ . '/database/connection.php';
include __DIR__ . '/templates/header.php';

// Verificar autenticación
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: /login.php');
    exit();
}

// variables de entorno están cargadas correctamente
var_dump($_ENV['GOOGLE_CLIENT_ID'], $_ENV['GOOGLE_CLIENT_SECRET']);


// Configurar conexión
$pdo = getDBConnection();

// Generar y regenerar CSRF token
if (empty($_SESSION['csrf_token']) || $_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validar CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Token CSRF inválido");
        }

        // Sanitizar y validar entrada
        $nombre = trim($_POST["nombre"] ?? "");
        $fecha_inicio = trim($_POST["fecha_inicio"] ?? "");
        $tipo = trim($_POST["tipo"] ?? "");
        $sistema = trim($_POST["sistema"] ?? "");
        $dobleronda = isset($_POST["dobleronda"]) ? 1 : 0;

        if (empty($nombre) || strlen($nombre) > 100) {
            throw new Exception("Nombre inválido (máx. 100 caracteres)");
        }

        // Validar fecha
        $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha_inicio);
        if (!$fecha_obj || $fecha_obj < new DateTime()) {
            throw new Exception("Fecha de inicio inválida o anterior a hoy");
        }
        $fecha_formateada = $fecha_obj->format('Y-m-d');

        // Validar tipo y sistema
        $tipos_permitidos = ['presencial', 'online'];
        $sistemas_permitidos = ['round robin', 'sistema suizo', 'eliminación simple', 'eliminación doble'];

        if (!in_array($tipo, $tipos_permitidos, true)) {
            throw new Exception("Tipo de torneo no válido");
        }
        if (!in_array($sistema, $sistemas_permitidos, true)) {
            throw new Exception("Sistema de juego no válido");
        }
        if ($dobleronda && $sistema !== 'round robin') {
            throw new Exception("Doble ronda solo disponible para Round Robin");
        }

        // Insertar en la base de datos
        $conn->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO db_Torneos (nombre, fecha_inicio, tipo, sistema, dobleronda, creador_id) 
                                VALUES (:nombre, :fecha_inicio, :tipo, :sistema, :dobleronda, :creador_id)");
        $stmt->execute([
            ':nombre' => $nombre,
            ':fecha_inicio' => $fecha_formateada,
            ':tipo' => $tipo,
            ':sistema' => $sistema,
            ':dobleronda' => $dobleronda,
            ':creador_id' => $_SESSION['usuario']['id']
        ]);
        $conn->commit();

        $_SESSION['exito'] = "Torneo creado exitosamente";
        header("Location: /dashboard.php");
        exit();
    } catch (Exception $e) {
        error_log("Error en nuevo_torneo: " . $e->getMessage() . " - Usuario: " . $_SESSION['usuario']['id']);
        $mensaje = "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
        $conn->rollBack();
    }
}

include __DIR__ . '/templates/footer.php';
