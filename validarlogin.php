<?php
session_start();
require_once("conexion.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$email = trim($_POST["email"]);
$password = $_POST["password"];

$sql = "SELECT * FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {

    if ((int)$user["bloqueado"] === 1) {
        header("Location: index.php?error=" . urlencode("Usuario bloqueado. Recupera tu contraseña para continuar."));
        exit;
    }

    if (password_verify($password, $user["password_hash"])) {

        $conn->prepare("UPDATE usuarios SET intentos_fallidos = 0 WHERE id = ?")
             ->execute([$user["id"]]);

        $_SESSION["usuario"] = $user["nombre"];
        $_SESSION["usuario_id"] = $user["id"];

        header("Location: guardar.php");
        exit;

    } else {

        $intentos = (int)$user["intentos_fallidos"] + 1;
        $bloqueado = ($intentos > 3) ? 1 : 0;

        $conn->prepare("UPDATE usuarios SET intentos_fallidos = ?, bloqueado = ? WHERE id = ?")
             ->execute([$intentos, $bloqueado, $user["id"]]);

        if ($bloqueado === 1) {
            header("Location: index.php?error=" . urlencode("Usuario bloqueado por más de 3 intentos fallidos."));
        } else {
            header("Location: index.php?error=" . urlencode("Credenciales incorrectas. Intento $intentos de 4."));
        }
        exit;
    }

} else {
    header("Location: index.php?error=" . urlencode("Usuario no encontrado."));
    exit;
}