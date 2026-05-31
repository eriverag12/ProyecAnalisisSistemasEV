<?php
session_start();

// Validar sesión
if (!isset($_SESSION["usuario"])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard POS Retail</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--  Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            height: 100vh;
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)),
                        url('fondo-login.jpg');
            background-size: cover;
            background-position: center;
            font-family: 'Segoe UI', sans-serif;
        }

        .card-custom {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            animation: fadeIn 0.7s ease;
        }

        .bienvenida {
            font-size: 2.2rem;
            font-weight: bold;
        }

        .subtitulo {
            opacity: 0.85;
        }

        .btn-menu {
            border-radius: 15px;
            padding: 15px;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-menu:hover {
            transform: scale(1.04);
        }

        h5 {
            text-align: left;
            font-weight: bold;
            opacity: 0.9;
            margin-top: 10px;
        }

        hr {
            border-color: rgba(255,255,255,0.4);
        }

        .btn-primary { background: #3a86ff; border: none; }
        .btn-info { background: #00b4d8; border: none; }
        .btn-warning { background: #ffaa00; border: none; }
        .btn-success { background: #2dc653; border: none; }
        .btn-secondary { background: #6c757d; border: none; }
        .btn-danger { background: #e63946; border: none; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px);}
            to { opacity: 1; transform: translateY(0);}
        }
    </style>
</head>

<body>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-7">

            <div class="card shadow-lg card-custom p-5 text-center">

                <!--  BIENVENIDA -->
                <div class="mb-4">
                    <h2 class="bienvenida">
                        Bienvenida <?php echo htmlspecialchars($_SESSION["usuario"]); ?> 
                    </h2>

                    <p class="subtitulo">
                        Sistema POS Retail - Control completo del sistema
                    </p>
                </div>

                <!--  MENÚ -->
                <div class="d-grid gap-3 mt-4 text-start">

                    <!--  OPERACIONES -->
                    <h5> Operaciones</h5>

                    <a href="productos.php" class="btn btn-primary btn-menu text-white">
                         Gestión de Productos
                    </a>

                    <a href="proveedores.php" class="btn btn-info btn-menu text-white">
                         Gestión de Proveedores
                    </a>

                    <a href="compras.php" class="btn btn-warning btn-menu text-dark">
                        🛒 Módulo de Compras
                    </a>

                    <a href="ventas.php" class="btn btn-success btn-menu text-white">
                         Módulo de Ventas
                    </a>

                    <hr>

                    <!-- 🔐 SEGURIDAD -->
                    <h5>🔐 Seguridad</h5>

                    <a href="recuperar.php" class="btn btn-secondary btn-menu text-white">
                        🔑 Recuperar contraseña
                    </a>

                    <a href="desbloquear_usuario.php" class="btn btn-secondary btn-menu text-white">
                        🔓 Desbloquear usuario
                    </a>

                    <hr>

                    <!--  SALIDA -->
                    <a href="logout.php" class="btn btn-danger btn-menu text-white">
                         Cerrar sesión
                    </a>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>