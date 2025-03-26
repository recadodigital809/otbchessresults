<?php

/**
 * Funciones auxiliares para el sistema de ajedrez
 */

/**
 * Sanitiza datos de entrada
 * @param mixed $data Los datos a sanitizar
 * @return mixed Los datos sanitizados
 */
function sanitizar($data)
{
    if (is_array($data)) {
        return array_map('sanitizar', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida una fecha en formato YYYY-MM-DD
 * @param string $date La fecha a validar
 * @param string $format El formato de fecha (por defecto Y-m-d)
 * @return bool True si es válida, false si no
 */
function validarFecha($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Convierte un valor booleano a tinyint (1 o 0)
 * @param mixed $value El valor a convertir
 * @return int 1 para true, 0 para false
 */
function boolToTinyInt($value)
{
    return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
}

/**
 * Convierte un tinyint a booleano
 * @param int $value El valor a convertir (0 o 1)
 * @return bool True para 1, false para 0
 */
function tinyIntToBool($value)
{
    return $value == 1;
}

/**
 * Formatea una respuesta JSON
 * @param mixed $data Los datos a enviar
 * @param int $status Código de estado HTTP
 * @param array $headers Encabezados adicionales
 */
function jsonResponse($data, $status = 200, $headers = [])
{
    header_remove();
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');

    foreach ($headers as $header => $value) {
        header("$header: $value");
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Maneja errores y devuelve respuesta JSON
 * @param string $message Mensaje de error
 * @param int $status Código de estado HTTP
 * @param array $details Detalles adicionales del error
 */
function handleError($message, $status = 400, $details = [])
{
    $response = ['error' => $message];

    if (!empty($details)) {
        $response['details'] = $details;
    }

    jsonResponse($response, $status);
}

/**
 * Obtiene el cuerpo de la petición como array asociativo
 * @return array Los datos del cuerpo de la petición
 */
function getRequestBody()
{
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        handleError('Error al decodificar JSON: ' . json_last_error_msg());
    }

    return $data;
}

/**
 * Valida los campos requeridos en un array de datos
 * @param array $data Los datos a validar
 * @param array $requiredFields Campos requeridos
 * @return bool True si todos los campos están presentes, false si falta alguno
 */
function validarCamposRequeridos($data, $requiredFields)
{
    $missing = [];

    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        handleError('Campos requeridos faltantes: ' . implode(', ', $missing));
        return false;
    }

    return true;
}

/**
 * Genera un ID único para entidades
 * @return string Un ID único
 */
function generarIdUnico()
{
    return md5(uniqid(rand(), true));
}

/**
 * Redirige a una URL
 * @param string $url La URL a redirigir
 * @param int $statusCode Código de estado HTTP para la redirección
 */
function redirect($url, $statusCode = 303)
{
    header('Location: ' . $url, true, $statusCode);
    exit();
}

/**
 * Registra un mensaje en el log del sistema
 * @param string $message El mensaje a registrar
 * @param string $type Tipo de mensaje (info, error, warning)
 */
function logMessage($message, $type = 'info')
{
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . 'system.log';
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[$timestamp] [$type] $message" . PHP_EOL;

    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

/**
 * Convierte un resultado de base de datos a formato booleano para campos tinyint(1)
 * @param array $data Los datos a convertir
 * @param array $booleanFields Los campos que son booleanos
 * @return array Los datos con los campos convertidos
 */
function convertDbBooleans($data, $booleanFields = [])
{
    foreach ($booleanFields as $field) {
        if (isset($data[$field])) {
            $data[$field] = tinyIntToBool($data[$field]);
        }
    }
    return $data;
}

/**
 * Obtiene el valor de un parámetro de la URL
 * @param string $param El nombre del parámetro
 * @param mixed $default Valor por defecto si no existe
 * @return mixed El valor del parámetro o el valor por defecto
 */
function getUrlParam($param, $default = null)
{
    return isset($_GET[$param]) ? sanitizar($_GET[$param]) : $default;
}

/**
 * Verifica si la petición es de tipo AJAX
 * @return bool True si es AJAX, false si no
 */
function isAjaxRequest()
{
    return strtolower(filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH', FILTER_SANITIZE_STRING)) === 'xmlhttprequest';
}
