<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Correo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background:#f4f6f9;">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow p-4">
                <h3 class="mb-3 text-center">Configuración de Correo</h3>

                <div class="alert alert-info">
                    Este módulo está preparado para una futura integración con envío real por SMTP.
                    Por ahora, la recuperación funciona mediante token y enlace generado automáticamente.
                </div>

                <div class="text-center">
                    <a href="guardar.php" class="btn btn-secondary">Volver al menú</a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>