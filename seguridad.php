<?php
require_once("conexion.php");

function validarPasswordSegura($password) {

    if (strlen($password) < 8 || strlen($password) > 11) {
        return false;
    }

    $tieneMayuscula = preg_match('/[A-Z]/', $password);
    $tieneMinuscula = preg_match('/[a-z]/', $password);
    $tieneNumero    = preg_match('/[0-9]/', $password);
    $tieneEspecial  = preg_match('/[\W_]/', $password);

    return $tieneMayuscula && $tieneMinuscula && $tieneNumero && $tieneEspecial;
}

function generarTokenSeguro() {
    return bin2hex(random_bytes(16));
}

function guardarTokenRecuperacion($conn, $email) {

    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) return false;

    $token = generarTokenSeguro();
    $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

    $stmt = $conn->prepare("
        UPDATE usuarios
        SET token_recuperacion = ?, token_expira = ?
        WHERE id = ?
    ");
    $stmt->execute([$token, $expira, $usuario["id"]]);

    return [
        "token" => $token,
        "usuario" => $usuario
    ];
}

function obtenerUsuarioPorToken($conn, $token) {

    $stmt = $conn->prepare("
        SELECT * FROM usuarios
        WHERE token_recuperacion = ?
        AND token_expira >= NOW()
    ");
    $stmt->execute([$token]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}