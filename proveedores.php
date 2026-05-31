<?php
session_start();
require_once("conexion.php");

// Validar sesión
if (!isset($_SESSION["usuario"])) {
    header("Location: index.php");
    exit;
}

$mensaje = "";
$modoEditar = false;

// ELIMINAR PROVEEDOR
if (isset($_GET["eliminar"])) {
    $id = intval($_GET["eliminar"]);

    $stmt = $conn->prepare("DELETE FROM proveedores WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: proveedores.php?msg=eliminado");
    exit;
}

// CARGAR PROVEEDOR PARA EDITAR
$idEditar = "";
$nombreEditar = "";
$telefonoEditar = "";
$correoEditar = "";
$direccionEditar = "";

if (isset($_GET["editar"])) {
    $idEditar = intval($_GET["editar"]);

    $stmt = $conn->prepare("SELECT * FROM proveedores WHERE id = ?");
    $stmt->execute([$idEditar]);
    $proveedorEditar = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($proveedorEditar) {
        $modoEditar = true;
        $nombreEditar = $proveedorEditar["nombre"];
        $telefonoEditar = $proveedorEditar["telefono"];
        $correoEditar = $proveedorEditar["correo"];
        $direccionEditar = $proveedorEditar["direccion"];
    }
}

// GUARDAR O ACTUALIZAR PROVEEDOR
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST["accion"];
    $nombre = trim($_POST["nombre"]);
    $telefono = trim($_POST["telefono"]);
    $correo = trim($_POST["correo"]);
    $direccion = trim($_POST["direccion"]);

    if ($accion == "guardar") {
        $sql = "INSERT INTO proveedores (nombre, telefono, correo, direccion)
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nombre, $telefono, $correo, $direccion]);

        header("Location: proveedores.php?msg=guardado");
        exit;
    }

    if ($accion == "actualizar") {
        $id = intval($_POST["id"]);

        $sql = "UPDATE proveedores
                SET nombre = ?, telefono = ?, correo = ?, direccion = ?
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nombre, $telefono, $correo, $direccion, $id]);

        header("Location: proveedores.php?msg=actualizado");
        exit;
    }
}

// MENSAJES ALERTAS
if (isset($_GET["msg"])) {
    if ($_GET["msg"] == "guardado") {
        $mensaje = "Proveedor guardado correctamente.";
    } elseif ($_GET["msg"] == "actualizado") {
        $mensaje = "Proveedor actualizado correctamente.";
    } elseif ($_GET["msg"] == "eliminado") {
        $mensaje = "Proveedor eliminado correctamente.";
    }
}

// LISTAR PROVEEDORES
$proveedores = $conn->query("SELECT * FROM proveedores ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proveedores</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f4f6f9;
        }

        .card-custom {
            border: none;
            border-radius: 16px;
        }

        .titulo-card {
            font-weight: 700;
            color: #1b2a41;
        }

        .form-control, .btn {
            border-radius: 12px;
        }

        .dropdown-menu {
            border-radius: 12px;
        }

        .btn-regresar {
            border-radius: 12px;
            padding: 10px 18px;
        }
    </style>
</head>
<body>

<div class="container py-4">

    <div class="mb-4 d-flex gap-2">
        <a href="guardar.php" class="btn btn-secondary btn-regresar">
            🔙 Regresar al menú
        </a>

        <a href="compras.php" class="btn btn-warning btn-regresar">
            🛒 Ir a Compras
        </a>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- FORMULARIO -->
        <div class="col-md-4">
            <div class="card shadow card-custom">
                <div class="card-body p-4">

                    <h2 class="titulo-card text-center mb-4">
                        <?php echo $modoEditar ? "Editar Proveedor" : "Nuevo Proveedor"; ?>
                    </h2>

                    <form method="POST">
                        <input type="hidden" name="accion" value="<?php echo $modoEditar ? 'actualizar' : 'guardar'; ?>">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($idEditar); ?>">

                        <div class="mb-3">
                            <input type="text"
                                   name="nombre"
                                   class="form-control form-control-lg"
                                   placeholder="Nombre del proveedor"
                                   value="<?php echo htmlspecialchars($nombreEditar); ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <input type="text"
                                   name="telefono"
                                   class="form-control form-control-lg"
                                   placeholder="Teléfono"
                                   value="<?php echo htmlspecialchars($telefonoEditar); ?>">
                        </div>

                        <div class="mb-3">
                            <input type="email"
                                   name="correo"
                                   class="form-control form-control-lg"
                                   placeholder="Correo"
                                   value="<?php echo htmlspecialchars($correoEditar); ?>">
                        </div>

                        <div class="mb-3">
                            <input type="text"
                                   name="direccion"
                                   class="form-control form-control-lg"
                                   placeholder="Dirección"
                                   value="<?php echo htmlspecialchars($direccionEditar); ?>">
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn <?php echo $modoEditar ? 'btn-warning' : 'btn-primary'; ?> btn-lg">
                                <?php echo $modoEditar ? "Actualizar Proveedor" : "Guardar Proveedor"; ?>
                            </button>

                            <?php if ($modoEditar): ?>
                                <a href="proveedores.php" class="btn btn-outline-secondary">
                                    Cancelar edición
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <!-- TABLA -->
        <div class="col-md-8">
            <div class="card shadow card-custom">
                <div class="card-body p-4">

                    <h2 class="titulo-card text-center mb-4">Listado de Proveedores</h2>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Teléfono</th>
                                    <th>Correo</th>
                                    <th>Dirección</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($proveedores) > 0): ?>
                                    <?php foreach($proveedores as $p): ?>
                                        <tr>
                                            <td><?php echo $p["id"]; ?></td>
                                            <td><?php echo htmlspecialchars($p["nombre"]); ?></td>
                                            <td><?php echo htmlspecialchars($p["telefono"]); ?></td>
                                            <td><?php echo htmlspecialchars($p["correo"]); ?></td>
                                            <td><?php echo htmlspecialchars($p["direccion"]); ?></td>
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <button class="btn btn-primary btn-sm dropdown-toggle"
                                                            type="button"
                                                            data-bs-toggle="dropdown"
                                                            aria-expanded="false">
                                                        ⚙️ 
                                                    </button>

                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item text-primary"
                                                               href="proveedores.php?editar=<?php echo $p['id']; ?>">
                                                                 Editar
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item text-danger"
                                                               href="proveedores.php?eliminar=<?php echo $p['id']; ?>"
                                                               onclick="return confirm('¿Eliminar este proveedor?');">
                                                                 Eliminar
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No hay proveedores registrados.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>