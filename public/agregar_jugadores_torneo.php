<!-- agregar_jugadores_torneo.php -->
<?php

require_once __DIR__ . "/database/connection.php";
include __DIR__ . '/templates/header.php';


// Verificar autenticación Google
session_start();
if (empty($_SESSION['user_id'])) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Obtener torneos en estado "creado"
$sqlTorneos = "SELECT id, nombre, fecha_inicio, sistema 
               FROM db_Torneos 
               WHERE estado = 'creado' 
               AND created_id = :user_id 
               ORDER BY fecha_inicio, nombre";
$stmt = $pdo->prepare($sqlTorneos);
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$torneos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Jugadores al Torneo</title>
    <link rel="stylesheet" href="styles.css">
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
    <h2 class="text-center">Gestión de Torneo</h2>

    <!-- Seleccionar torneo -->
    <div class="card shadow-sm p-4 mb-4">
        <label class="form-label">Seleccionar Torneo</label>
        <select id="torneo_id" class="form-control">
            <option value="">-- Seleccione un torneo --</option>
            <?php foreach ($torneos as $row): ?>
                <option value="<?= htmlspecialchars($row['id']); ?>">
                    <?= htmlspecialchars($row['nombre']) . " (" . htmlspecialchars($row['sistema']) . ")"; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Sección para agregar jugadores -->
    <div id="contenido_torneo" style="display:none;">
        <div class="card shadow-sm p-4 mb-4">
            <h4 class="text-center">Agregar Jugadores</h4>
            <form id="form_agregar">
                <div class="mb-3">
                    <label class="form-label">Seleccionar Jugador</label>
                    <select id="jugador_id" class="form-control" required>
                        <option value="">-- Seleccione un jugador --</option>
                    </select>
                </div>
                <div class="mt-4 text-center">
                    <button type="submit" class="btn btn-success">Agregar Jugador</button>
                </div>
            </form>
        </div>

        <!-- Tabla de jugadores -->
        <div class="card shadow-sm p-4">
            <h4 class="text-center">Jugadores en el Torneo</h4>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th> <!-- Columna de numeración -->
                        <th>Nombre</th>
                        <th>ELO</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody id="tabla_jugadores"></tbody>
            </table>
        </div>

        <!-- Botón para iniciar el torneo -->
        <div class="mt-4 text-center" style="margin-bottom: 20px;">
            <button id="iniciar_torneo" class="btn btn-primary" disabled>
                Iniciar Torneo
            </button>
        </div>
    </div>
</div>


<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Manejar la selección de torneo
        $("#torneo_id").change(function() {
            let torneo_id = $(this).val();
            if (torneo_id) {
                $("#contenido_torneo").show();
                cargarJugadores(torneo_id);
                cargarJugadoresDisponibles(torneo_id);
            } else {
                $("#contenido_torneo").hide();
            }
        });

        // Cargar jugadores del torneo
        function cargarJugadores(torneo_id) {
            $.post("procesar_torneo.php", {
                action: "listar_jugadores",
                torneo_id: torneo_id
            }, function(data) {
                $("#tabla_jugadores").html(data);
                $("#iniciar_torneo").prop("disabled", data.trim() === "");
            });
        }

        // Cargar jugadores disponibles
        function cargarJugadoresDisponibles(torneo_id) {
            $.post("procesar_torneo.php", {
                action: "listar_disponibles",
                torneo_id: torneo_id
            }, function(data) {
                $("#jugador_id").html(data);
            });
        }

        // Agregar jugador
        $("#form_agregar").submit(function(e) {
            e.preventDefault();
            let torneo_id = $("#torneo_id").val();
            let jugador_id = $("#jugador_id").val();
            if (!jugador_id) return;

            $.post("procesar_torneo.php", {
                    action: "agregar_jugador",
                    torneo_id,
                    jugador_id
                })
                .done(function(response) {
                    // alert(response);
                    cargarJugadores(torneo_id);
                    cargarJugadoresDisponibles(torneo_id);
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    alert("Error: " + textStatus + " - " + errorThrown);
                });
        });

        // Eliminar jugador
        $(document).on("click", ".eliminar-jugador", function() {
            let torneo_id = $("#torneo_id").val();
            let jugador_id = $(this).data("id");

            if (confirm("¿Seguro que quieres eliminar a este jugador?")) {
                $.post("procesar_torneo.php", {
                    action: "eliminar_jugador",
                    torneo_id: torneo_id,
                    jugador_id: jugador_id
                }, function(response) {
                    // alert(response);
                    cargarJugadores(torneo_id);
                    cargarJugadoresDisponibles(torneo_id);
                });
            }
        });

        // Iniciar torneo
        $("#iniciar_torneo").click(function() {
            let torneo_id = $("#torneo_id").val();
            // Deshabilitar el botón para evitar múltiples clics
            $("#iniciar_torneo").prop('disabled', true);
            $.post("procesar_torneo.php", {
                action: "iniciar_torneo",
                torneo_id: torneo_id
            }, function(response) {
                // alert(response);
                cargarJugadores(torneo_id); // Recargar solo la tabla en lugar de refrescar la página
                $("#iniciar_torneo").prop('disabled', true);
                
                // Redirigir a partidas.php
                 window.location.href = "partidas.php";  // Cambiar la URL a la página de partidas
            });
        });
    });
</script>
<?php include __DIR__ . '/templates/footer.php'; ?>
</body>
</html>