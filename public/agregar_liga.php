<?php
require_once __DIR__ . "/database/connection.php";
include __DIR__ . '/templates/header.php';

// Verificar autenticación Google
session_start();
if (empty($_SESSION['user_id'])) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST["nombre"];
    $texto = $_POST["texto"];
    $imagen = null;

    // Subir la imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "uploads/";  // Directorio para guardar la imagen
        $targetFile = $targetDir . basename($_FILES["imagen"]["name"]);
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Verificar si el archivo es una imagen real
        if (getimagesize($_FILES["imagen"]["tmp_name"])) {
            // Mover el archivo a la carpeta de destino
            if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $targetFile)) {
                // Guardar solo el nombre del archivo, no la ruta completa
                $imagen = basename($_FILES["imagen"]["name"]);
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al subir la imagen.</div>";
            }
        } else {
            $mensaje = "<div class='alert alert-danger'>El archivo no es una imagen válida.</div>";
        }
    } else {
        $mensaje = "<div class='alert alert-warning'>No se ha seleccionado ninguna imagen.</div>";
    }

// Insertar la liga en la base de datos
if (!empty($nombre)) {
    $sql = "INSERT INTO db_Liga (nombre, texto, imagen) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $nombre, PDO::PARAM_STR);
    $stmt->bindValue(2, $texto, PDO::PARAM_STR);
    $stmt->bindValue(3, $imagen, PDO::PARAM_STR); // Guardar solo el nombre de la imagen
    if ($stmt->execute()) {
        $mensaje = "<div class='alert alert-success'>Liga agregada correctamente.</div>";
    } else {
        $mensaje = "<div class='alert alert-danger'>Error al agregar la liga.</div>";
    }
}
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Liga</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex: 1;
        }
        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 1rem;
            margin-top: auto;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center">Agregar Nueva Liga</h2>
    <?= $mensaje; ?>

    <div class="card shadow-sm p-4">
        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Nombre de la Liga</label>
                <input type="text" name="nombre" id="nombre" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Descripción (Texto)</label>
                <textarea name="texto" id="texto" class="form-control" rows="5"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Imagen de la Liga</label>
                <input type="file" name="imagen" id="imagen" class="form-control" accept="image/*">
                <!-- <img src="<?= htmlspecialchars($liga['imagen']); ?>" alt="Imagen de la liga" class="img-fluid"> -->

            </div>
            <button type="submit" class="btn btn-success w-100">Agregar Liga</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/templates/footer.php'; ?>
</body>
</html>


