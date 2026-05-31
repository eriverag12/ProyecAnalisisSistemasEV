<?php
session_start();
require_once("conexion.php");

if (!isset($_SESSION["usuario"])) {
    header("Location: index.php");
    exit;
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);

    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {

        $stmt = $conn->prepare("
            UPDATE usuarios 
            SET intentos_fallidos = 0, bloqueado = 0 
            WHERE email = ?
        ");
        $stmt->execute([$email]);

        $mensaje = "Usuario desbloqueado correctamente.";

    } else {
        $mensaje = "El usuario no existe.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Desbloquear Usuario</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body style="background:#f4f6f9;">
<div class="container py-5">
    <div class="row justify-content-center">

        <div class="col-md-5">
            <div class="card shadow p-4">

                <h3 class="text-center mb-3">Desbloquear Usuario</h3>

                <?php if ($mensaje): ?>
                    <div class="alert alert-info">
                        <?php echo $mensaje; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label>Correo del usuario</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <button class="btn btn-warning w-100">
                        Desbloquear usuario
                    </button>
                </form>

                <div class="text-center mt-3">
                    <a href="guardar.php">Volver</a>
                </div>

            </div>
        </div>

    </div>
</div>
</body>
</html>
