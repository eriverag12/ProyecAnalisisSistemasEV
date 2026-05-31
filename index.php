<?php
session_start();

$mensaje = "";

if (isset($_GET["error"])) {
    $mensaje = $_GET["error"];
}
if (isset($_GET["success"])) {
    $mensaje = $_GET["success"];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login POS Retail</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

/* RESET */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

/*  FONDO COMPLETO */
body{
    height:100vh;
    display:flex;
    justify-content:flex-end; 
    align-items:center;
    font-family:'Segoe UI', sans-serif;

    background:
        linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)),
        url('fondo-login.jpg'); 

    background-size:cover;
    background-position:center;
}

/* LOGIN */
.login-box{
    width:460px;             
    height:520px;         
    
    margin-right:80px;

    padding:50px 40px;      

    background:rgba(0,30,60,0.85);
    border:2px solid #ffffff;
    border-radius:20px;

    color:white;
    box-shadow:0 15px 40px rgba(0,0,0,0.5);

    display:flex;           
    flex-direction:column;
    justify-content:center; 
}

    background:rgba(0,30,60,0.85);
    border:2px solid #ffffff;
    border-radius:20px;

    color:white;
    box-shadow:0 15px 40px rgba(0,0,0,0.5);
}

    padding:45px;          

    background:rgba(0,30,60,0.85);
    border:2px solid #ffffff;
    border-radius:20px;

    color:white;
    box-shadow:0 15px 40px rgba(0,0,0,0.5);
}


    background:rgba(0,30,60,0.85);
    border:2px solid #ffffff;
    border-radius:15px;
    padding:35px;

    color:white;
    box-shadow:0 10px 30px rgba(0,0,0,0.4);
}

/* TITULO */
h2{
    text-align:center;
    font-size:2.4rem;
    font-weight:bold;
    margin-bottom:25px;
}

/* LABEL */
label{
    font-weight:bold;
}

/* INPUT */
input{
    width:100%;
    padding:12px;
    margin-bottom:15px;
    border-radius:8px;
    border:none;
}

/* BOTON */
.btn-login{
    width:100%;
    padding:12px;
    border-radius:10px;
    border:none;
    background:#1c7ed6;
    color:white;
    font-size:1.1rem;
    font-weight:bold;
}

.btn-login:hover{
    background:#1864ab;
}

/* LINK */
.link-recuperar{
    display:block;
    text-align:center;
    margin-top:15px;
    color:#ffc107;
    text-decoration:none;
    font-weight:bold;
}

.link-recuperar:hover{
    text-decoration:underline;
}

/* ALERT */
.alert{
    margin-bottom:15px;
}

</style>
</head>

<body>

<div class="login-box">

    <h2>Iniciar sesión</h2>

    <?php if($mensaje): ?>
        <div class="alert alert-warning text-dark">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    
    <form action="validarlogin.php" method="POST">

        <label>🔑 Usuario</label>
        <input type="email" name="email" required>

        <label>🔒 Contraseña</label>
        <input type="password" name="password" required>

        <button type="submit" class="btn-login">
            Ingresar
        </button>

    </form>

    <!-- LINK RECUPERAR FUNCIONANDO -->
    <a href="recuperar.php" class="link-recuperar">
        ¿Olvidaste tu contraseña?
    </a>

</div>

</body>
</html>