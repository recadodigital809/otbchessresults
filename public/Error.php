<?php
http_response_code(404);
require_once __DIR__ . '/templates/header.php';
?>
<div class="container">
    <h1>Error 404</h1>
    <p>Recurso no encontrado</p>
</div>
<?php include __DIR__ . '/templates/footer.php'; ?>