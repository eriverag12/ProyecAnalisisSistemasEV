<?php
session_start();
require_once("conexion.php");
require_once("seguridad.php");

$mensaje = "";
$linkRecuperacion = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);

    $resultado = guardarTokenRecuperacion($conn, $email);

    if ($resultado) {
        $token = $resultado["token"];

        // GENERAR LINK BIEN FORMADO
        $scheme = "http";
        $host = $_SERVER['HTTP_HOST'];
        $rutaBase = dirname($_SERVER['SCRIPT_NAME']);

        $linkRecuperacion = $scheme . "://" . $host . $rutaBase . "/restablecer.php?token=" . $token;

        $mensaje = "Se generó correctamente el enlace de recuperación.";
    } else {
        $mensaje = "No existe un usuario con ese correo.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar contraseña</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body { background: #f4f6f9; }
        .card { border-radius: 16px; }
    </style>
</head>

<body>

<div class="container py-5">
    <div class="row justify-content-center">

        <div class="col-md-5">
            <div class="card shadow p-4">

                <h3 class="text-center mb-3">Recuperar contraseña</h3>

                <?php if (!empty($mensaje)): ?>
                    <div class="alert alert-info">
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Correo</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <button class="btn btn-primary w-100">
                        Generar enlace
                    </button>
                </form>

                <!-- ✅ LINK PERFECTO -->
                <?php if (!empty($linkRecuperacion)): ?>
                    <div class="alert alert-success mt-4">
                        <strong>Enlace de recuperación:</strong><br>

                        <a href="<?php echo $linkRecuperacion; ?>" target="_blank">
                            <?php echo $linkRecuperacion; ?>
                        </a>

                    </div>
                <?php endif; ?>

                <div class="text-center mt-3">
                    <a href="index.php">Volver al login</a>
                </div>

            </div>
        </div>

    </div>
</div>

</body>
</html>