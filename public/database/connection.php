<?php

//require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Validar que las variables de entorno existen
/* $env_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
foreach ($env_vars as $var) {
    if (!isset($_ENV[$var])) {
        throw new Exception("Falta la variable de entorno: $var");
    }
} */

/**
 * Obtiene una conexión a la base de datos utilizando PDO (Singleton).
 *
 * @return PDO
 * @throws Exception
 */
function getDBConnection()
{
    static $pdo = null;

    if ($pdo === null) {
        $host = getenv('DB_HOST') ?: $_ENV['DB_HOST'];
        $dbname = $_ENV['DB_NAME'];
        $username = $_ENV['DB_USER'];
        $password = $_ENV['DB_PASS'];
        $charset = 'utf8mb4';


        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            throw new Exception("No se pudo conectar a la base de datos.");
        }
    }

    return $pdo;
}

// Definir conexión global
$pdo = getDBConnection();
