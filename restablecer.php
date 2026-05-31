<?php
session_start();
require_once("conexion.php");
require_once("seguridad.php"); // 🔥 IMPORTANTE

$mensaje = "";

if (!isset($_GET["token"])) {
    die("Token no válido.");
}

$token = $_GET["token"];

$usuario = obtenerUsuarioPorToken($conn, $token);

if (!$usuario) {
    die("El token no existe o ya expiró.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password = $_POST["password"];
    $confirmar = $_POST["confirmar"];

    // ✅ VALIDACIONES
    if ($password !== $confirmar) {
        $mensaje = "Las contraseñas no coinciden.";
    }
    elseif (!validarPasswordSegura($password)) {
        $mensaje = "Debe tener entre 8 y 11 caracteres, con mayúsculas, minúsculas, números y símbolos.";
    }
    else {

        // ✅ ENCRIPTAR
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            UPDATE usuarios
            SET password_hash = ?, 
                token_recuperacion = NULL,
                token_expira = NULL,
                intentos_fallidos = 0,
                bloqueado = 0
            WHERE id = ?
        ");

        $stmt->execute([$hash, $usuario["id"]]);

        header("Location: index.php?success=Contraseña actualizada correctamente");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body style="background:#f4f6f9;">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">

            <div class="card shadow p-4">

                <h3 class="text-center mb-3">Nueva contraseña</h3>

                <?php if (!empty($mensaje)): ?>
                    <div class="alert alert-warning">
                        <?php echo $mensaje; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="mb-3">
                        <label>Nueva contraseña</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Confirmar contraseña</label>
                        <input type="password" name="confirmar" class="form-control" required>
                    </div>

                    <button class="btn btn-success w-100">
                        Guardar nueva contraseña
                    </button>

                </form>

            </div>

        </div>
    </div>
</div>
</body>
</html>