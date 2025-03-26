<?php

require_once __DIR__ . '/database/connection.php';

try {
    $pdo = getDBConnection();
    echo "<p>✅ Conexión a la base de datos establecida correctamente.</p>";

    // Ejemplo: Obtener la versión de MySQL
    $stmt = $pdo->query("SELECT VERSION() AS mysql_version");
    $row = $stmt->fetch();
    echo "<p>Versión de MySQL: " . htmlspecialchars($row['mysql_version']) . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
