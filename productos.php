<?php
session_start();
require_once("conexion.php");

if (!isset($_SESSION["usuario"])) {
    header("Location: index.php");
    exit;
}

$mensaje = "";
$modoEditar = false;

if (isset($_GET["eliminar"])) {
    $id = intval($_GET["eliminar"]);

    $stmt = $conn->prepare("UPDATE productos SET estado = 0, fecha_eliminacion = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: productos.php?msg=eliminado");
    exit;
}

if (isset($_GET["reactivar"])) {
    $id = intval($_GET["reactivar"]);

    $stmt = $conn->prepare("UPDATE productos SET estado = 1, fecha_eliminacion = NULL WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: productos.php?msg=reactivado");
    exit;
}

$idEditar = "";
$nombreEditar = "";
$descripcionEditar = "";
$precioEditar = "";
$stockEditar = "";

if (isset($_GET["editar"])) {
    $idEditar = intval($_GET["editar"]);

    $stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->execute([$idEditar]);
    $productoEditar = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($productoEditar) {
        $modoEditar = true;
        $nombreEditar = $productoEditar["nombre"];
        $descripcionEditar = $productoEditar["descripcion"];
        $precioEditar = $productoEditar["precio"];
        $stockEditar = $productoEditar["stock"];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST["accion"];
    $nombre = trim($_POST["nombre"]);
    $descripcion = trim($_POST["descripcion"]);
    $precio = $_POST["precio"];
    $stock = $_POST["stock"];

    if ($accion == "guardar") {
        $sql = "INSERT INTO productos (nombre, descripcion, precio, stock, estado, fecha_eliminacion)
                VALUES (?, ?, ?, ?, 1, NULL)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nombre, $descripcion, $precio, $stock]);

        header("Location: productos.php?msg=guardado");
        exit;
    }

    if ($accion == "actualizar") {
        $id = intval($_POST["id"]);

        $sql = "UPDATE productos
                SET nombre = ?, descripcion = ?, precio = ?, stock = ?
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nombre, $descripcion, $precio, $stock, $id]);

        header("Location: productos.php?msg=actualizado");
        exit;
    }
}

if (isset($_GET["msg"])) {
    if ($_GET["msg"] == "guardado") {
        $mensaje = "Producto guardado correctamente.";
    } elseif ($_GET["msg"] == "actualizado") {
        $mensaje = "Producto actualizado correctamente.";
    } elseif ($_GET["msg"] == "eliminado") {
        $mensaje = "Producto enviado al historial de eliminados.";
    } elseif ($_GET["msg"] == "reactivado") {
        $mensaje = "Producto reactivado correctamente.";
    }
}

$productos = $conn->query("
    SELECT * FROM productos
    WHERE estado = 1
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$productosEliminados = $conn->query("
    SELECT * FROM productos
    WHERE estado = 0
    ORDER BY fecha_eliminacion DESC, id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .btn-regresar {
            border-radius: 12px;
            padding: 10px 18px;
        }
        .form-control {
            border-radius: 12px;
        }
        .btn {
            border-radius: 12px;
        }
        .dropdown-menu {
            border-radius: 12px;
        }
    </style>
</head>
<body>

<div class="container py-4">

    <div class="mb-4">
        <a href="guardar.php" class="btn btn-secondary btn-regresar">🔙 Regresar al menú</a>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <div class="col-md-4">
            <div class="card shadow card-custom">
                <div class="card-body p-4">
                    <h2 class="titulo-card text-center mb-4">
                        <?php echo $modoEditar ? "Editar Producto" : "Nuevo Producto"; ?>
                    </h2>

                    <form method="POST">
                        <input type="hidden" name="accion" value="<?php echo $modoEditar ? 'actualizar' : 'guardar'; ?>">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($idEditar); ?>">

                        <div class="mb-3">
                            <input type="text"
                                   name="nombre"
                                   class="form-control form-control-lg"
                                   placeholder="Nombre"
                                   value="<?php echo htmlspecialchars($nombreEditar); ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <input type="text"
                                   name="descripcion"
                                   class="form-control form-control-lg"
                                   placeholder="Descripción"
                                   value="<?php echo htmlspecialchars($descripcionEditar); ?>">
                        </div>

                        <div class="mb-3">
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   name="precio"
                                   class="form-control form-control-lg"
                                   placeholder="Precio"
                                   value="<?php echo htmlspecialchars($precioEditar); ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <input type="number"
                                   min="0"
                                   name="stock"
                                   class="form-control form-control-lg"
                                   placeholder="Stock"
                                   value="<?php echo htmlspecialchars($stockEditar); ?>"
                                   required>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn <?php echo $modoEditar ? 'btn-warning' : 'btn-primary'; ?> btn-lg">
                                <?php echo $modoEditar ? "Actualizar Producto" : "Guardar Producto"; ?>
                            </button>

                            <?php if ($modoEditar): ?>
                                <a href="productos.php" class="btn btn-outline-secondary">Cancelar edición</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow card-custom">
                <div class="card-body p-4">
                    <h2 class="titulo-card text-center mb-4">Listado de Productos</h2>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($productos) > 0): ?>
                                    <?php foreach($productos as $p): ?>
                                        <tr>
                                            <td><?php echo $p["id"]; ?></td>
                                            <td><?php echo htmlspecialchars($p["nombre"]); ?></td>
                                            <td><?php echo htmlspecialchars($p["descripcion"]); ?></td>
                                            <td>Q<?php echo number_format($p["precio"], 2); ?></td>
                                            <td><?php echo $p["stock"]; ?></td>
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
                                                               href="productos.php?editar=<?php echo $p['id']; ?>">
                                                                Editar
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item text-danger"
                                                               href="productos.php?eliminar=<?php echo $p['id']; ?>"
                                                               onclick="return confirm('¿Enviar este producto al historial de eliminados?');">
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
                                        <td colspan="6" class="text-center text-muted">No hay productos registrados.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <div class="card shadow card-custom mt-4">
        <div class="card-body p-4">
            <h2 class="titulo-card text-center mb-4">Historial de Productos Eliminados</h2>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Fecha eliminación</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($productosEliminados) > 0): ?>
                            <?php foreach($productosEliminados as $p): ?>
                                <tr>
                                    <td><?php echo $p["id"]; ?></td>
                                    <td><?php echo htmlspecialchars($p["nombre"]); ?></td>
                                    <td><?php echo htmlspecialchars($p["descripcion"]); ?></td>
                                    <td>Q<?php echo number_format($p["precio"], 2); ?></td>
                                    <td><?php echo $p["stock"]; ?></td>
                                    <td><?php echo $p["fecha_eliminacion"]; ?></td>
                                    <td class="text-center">
                                        <a href="productos.php?reactivar=<?php echo $p['id']; ?>"
                                           class="btn btn-success btn-sm">
                                            Reactivar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No hay productos eliminados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>