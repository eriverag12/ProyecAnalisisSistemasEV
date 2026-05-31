<?php
session_start();
require_once("conexion.php");

// ==============================
// VALIDAR SESIÓN
// ==============================
if (!isset($_SESSION["usuario"])) {
    header("Location: index.php");
    exit;
}

$mensaje = "";

// ==============================
// REGISTRAR COMPRA
// ==============================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "guardar_compra") {
    $proveedor_id = intval($_POST["proveedor_id"]);
    $producto_id = intval($_POST["producto_id"]);
    $tipo_compra = $_POST["tipo_compra"];
    $cantidad = intval($_POST["cantidad"]);
    $precio_unitario = floatval($_POST["precio_unitario"]);
    $observacion = trim($_POST["observacion"]);
    $subtotal = $cantidad * $precio_unitario;

    try {
        $conn->beginTransaction();

        // 1. Guardar compra
        $sqlCompra = "INSERT INTO compras (proveedor_id, tipo_compra, total, observacion)
                      VALUES (?, ?, ?, ?)";
        $stmtCompra = $conn->prepare($sqlCompra);
        $stmtCompra->execute([$proveedor_id, $tipo_compra, $subtotal, $observacion]);

        $compra_id = $conn->lastInsertId();

        // 2. Guardar detalle
        $sqlDetalle = "INSERT INTO detalle_compras (compra_id, producto_id, cantidad, precio_unitario, subtotal)
                       VALUES (?, ?, ?, ?, ?)";
        $stmtDetalle = $conn->prepare($sqlDetalle);
        $stmtDetalle->execute([$compra_id, $producto_id, $cantidad, $precio_unitario, $subtotal]);

        // 3. Obtener stock actual
        $stmtStock = $conn->prepare("SELECT stock FROM productos WHERE id = ?");
        $stmtStock->execute([$producto_id]);
        $producto = $stmtStock->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            throw new Exception("El producto seleccionado no existe.");
        }

        $stockActual = intval($producto["stock"]);
        $nuevoStock = $stockActual + $cantidad;

        // 4. Actualizar stock
        $stmtUpdate = $conn->prepare("UPDATE productos SET stock = ? WHERE id = ?");
        $stmtUpdate->execute([$nuevoStock, $producto_id]);

        // 5. Registrar kardex
        $documento = "COMPRA #" . $compra_id;
        $stmtKardex = $conn->prepare("
            INSERT INTO kardex (producto_id, tipo_movimiento, documento, entrada, salida, stock_actual, observacion)
            VALUES (?, 'COMPRA', ?, ?, 0, ?, ?)
        ");
        $stmtKardex->execute([$producto_id, $documento, $cantidad, $nuevoStock, $observacion]);

        $conn->commit();
        header("Location: compras.php?msg=compra_ok");
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        $mensaje = "Error al registrar compra: " . $e->getMessage();
    }
}

// ==============================
// REGISTRAR DEVOLUCIÓN AL PROVEEDOR
// ==============================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "guardar_devolucion") {
    $proveedor_id = intval($_POST["proveedor_id_dev"]);
    $producto_id = intval($_POST["producto_id_dev"]);
    $cantidad = intval($_POST["cantidad_dev"]);
    $motivo = trim($_POST["motivo_dev"]);

    try {
        $conn->beginTransaction();

        // 1. Obtener stock actual
        $stmtStock = $conn->prepare("SELECT stock FROM productos WHERE id = ?");
        $stmtStock->execute([$producto_id]);
        $producto = $stmtStock->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            throw new Exception("El producto seleccionado no existe.");
        }

        $stockActual = intval($producto["stock"]);

        if ($cantidad > $stockActual) {
            throw new Exception("No hay suficiente stock para devolver al proveedor.");
        }

        $nuevoStock = $stockActual - $cantidad;

        // 2. Guardar devolución
        $stmtDev = $conn->prepare("
            INSERT INTO devoluciones_proveedor (proveedor_id, producto_id, cantidad, motivo)
            VALUES (?, ?, ?, ?)
        ");
        $stmtDev->execute([$proveedor_id, $producto_id, $cantidad, $motivo]);

        $devolucion_id = $conn->lastInsertId();

        // 3. Actualizar stock
        $stmtUpdate = $conn->prepare("UPDATE productos SET stock = ? WHERE id = ?");
        $stmtUpdate->execute([$nuevoStock, $producto_id]);

        // 4. Registrar kardex
        $documento = "DEVOLUCIÓN #" . $devolucion_id;
        $stmtKardex = $conn->prepare("
            INSERT INTO kardex (producto_id, tipo_movimiento, documento, entrada, salida, stock_actual, observacion)
            VALUES (?, 'DEVOLUCION_PROVEEDOR', ?, 0, ?, ?, ?)
        ");
        $stmtKardex->execute([$producto_id, $documento, $cantidad, $nuevoStock, $motivo]);

        $conn->commit();
        header("Location: compras.php?msg=devolucion_ok");
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        $mensaje = "Error al registrar devolución: " . $e->getMessage();
    }
}

// ==============================
// MENSAJES
// ==============================
if (isset($_GET["msg"])) {
    if ($_GET["msg"] == "compra_ok") {
        $mensaje = "Compra registrada correctamente.";
    } elseif ($_GET["msg"] == "devolucion_ok") {
        $mensaje = "Devolución al proveedor registrada correctamente.";
    }
}

// ==============================
// CONSULTAS PARA COMBOS Y TABLAS
// ==============================
$proveedores = $conn->query("SELECT * FROM proveedores ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$productos = $conn->query("SELECT * FROM productos ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

$historialCompras = $conn->query("
    SELECT c.id, c.fecha, c.tipo_compra, c.total, p.nombre AS proveedor
    FROM compras c
    INNER JOIN proveedores p ON c.proveedor_id = p.id
    ORDER BY c.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$historialDevoluciones = $conn->query("
    SELECT d.id, d.fecha, d.cantidad, d.motivo, p.nombre AS proveedor, pr.nombre AS producto
    FROM devoluciones_proveedor d
    INNER JOIN proveedores p ON d.proveedor_id = p.id
    INNER JOIN productos pr ON d.producto_id = pr.id
    ORDER BY d.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$historialKardex = $conn->query("
    SELECT k.id, k.fecha, pr.nombre AS producto, k.tipo_movimiento, k.documento, k.entrada, k.salida, k.stock_actual, k.observacion
    FROM kardex k
    INNER JOIN productos pr ON k.producto_id = pr.id
    WHERE k.tipo_movimiento IN ('COMPRA', 'DEVOLUCION_PROVEEDOR')
    ORDER BY k.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ==============================
// RESUMEN / REPORTE SIMPLE
// ==============================
$totalCompras = count($historialCompras);
$totalDevoluciones = count($historialDevoluciones);

$montoInvertido = 0;
foreach ($historialCompras as $c) {
    $montoInvertido += floatval($c["total"]);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Módulo de Compras</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f6f9;
        }

        .card-custom {
            border: none;
            border-radius: 18px;
        }

        .titulo-card {
            font-weight: 700;
            color: #1b2a41;
        }

        .form-control,
        .form-select,
        .btn {
            border-radius: 12px;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .resumen-card {
            border: none;
            border-radius: 16px;
            color: white;
        }

        .bg-card-1 { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
        .bg-card-2 { background: linear-gradient(135deg, #059669, #047857); }
        .bg-card-3 { background: linear-gradient(135deg, #d97706, #b45309); }

        .empresa-box h4 {
            margin-bottom: 2px;
            font-weight: 700;
        }

        .empresa-box small {
            display: block;
            color: #555;
        }
    </style>
</head>
<body>

<div class="container py-4">

    <!-- BOTONES SUPERIORES -->
    <div class="mb-4 d-flex gap-2">
        <a href="guardar.php" class="btn btn-secondary">🔙 Regresar al menú</a>
        <a href="proveedores.php" class="btn btn-info text-white">🚚 Crear / ver proveedores</a>
    </div>

    <div class="text-center mb-2">
        <h5>POS Retail</h5>
        <small>Control de compras, devoluciones e inventario</small>
    </div>

    <h1 class="mb-4 text-center titulo-card text-uppercase">Sistema de Compras</h1>

    <?php if (count($proveedores) == 0): ?>
        <div class="alert alert-warning">
            No hay proveedores registrados.
            <a href="proveedores.php" class="alert-link">Haz clic aquí para crear uno</a>.
        </div>
    <?php endif; ?>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- RESUMEN -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card resumen-card bg-card-1 shadow">
                <div class="card-body">
                    <h6 class="mb-2">Total de compras</h6>
                    <h3 class="mb-0"><?php echo $totalCompras; ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card resumen-card bg-card-2 shadow">
                <div class="card-body">
                    <h6 class="mb-2">Monto total invertido</h6>
                    <h3 class="mb-0">Q<?php echo number_format($montoInvertido, 2); ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card resumen-card bg-card-3 shadow">
                <div class="card-body">
                    <h6 class="mb-2">Total de devoluciones</h6>
                    <h3 class="mb-0"><?php echo $totalDevoluciones; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- FORMULARIO DE COMPRA -->
        <div class="col-md-6">
            <div class="card shadow card-custom">
                <div class="card-body p-4">
                    <h3 class="titulo-card mb-3">Registrar Compra</h3>

                    <form method="POST">
                        <input type="hidden" name="accion" value="guardar_compra">

                        <div class="mb-3">
                            <label class="form-label">Proveedor</label>
                            <select name="proveedor_id" class="form-select" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($proveedores as $prov): ?>
                                    <option value="<?php echo $prov["id"]; ?>">
                                        <?php echo htmlspecialchars($prov["nombre"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Producto</label>
                            <select name="producto_id" class="form-select" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($productos as $prod): ?>
                                    <option value="<?php echo $prod["id"]; ?>">
                                        <?php echo htmlspecialchars($prod["nombre"]); ?> (Stock actual: <?php echo $prod["stock"]; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tipo de compra</label>
                            <select name="tipo_compra" class="form-select" required>
                                <option value="CONTADO">Contado</option>
                                <option value="CREDITO">Crédito</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cantidad</label>
                            <input type="number" min="1" name="cantidad" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Precio unitario</label>
                            <input type="number" step="0.01" min="0" name="precio_unitario" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Observación</label>
                            <input type="text" name="observacion" class="form-control" placeholder="Opcional">
                        </div>

                        <button class="btn btn-primary w-100">Guardar compra</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- FORMULARIO DEVOLUCIÓN -->
        <div class="col-md-6">
            <div class="card shadow card-custom">
                <div class="card-body p-4">
                    <h3 class="titulo-card mb-3">Devolución al proveedor</h3>

                    <form method="POST">
                        <input type="hidden" name="accion" value="guardar_devolucion">

                        <div class="mb-3">
                            <label class="form-label">Proveedor</label>
                            <select name="proveedor_id_dev" class="form-select" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($proveedores as $prov): ?>
                                    <option value="<?php echo $prov["id"]; ?>">
                                        <?php echo htmlspecialchars($prov["nombre"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Producto</label>
                            <select name="producto_id_dev" class="form-select" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($productos as $prod): ?>
                                    <option value="<?php echo $prod["id"]; ?>">
                                        <?php echo htmlspecialchars($prod["nombre"]); ?> (Stock actual: <?php echo $prod["stock"]; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cantidad a devolver</label>
                            <input type="number" min="1" name="cantidad_dev" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Motivo</label>
                            <input type="text" name="motivo_dev" class="form-control" required>
                        </div>

                        <button class="btn btn-warning w-100">Registrar devolución</button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- HISTORIAL DE COMPRAS -->
    <div class="card shadow card-custom mt-4">
        <div class="card-body p-4">
            <h3 class="titulo-card mb-3">Historial de Compras</h3>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Proveedor</th>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($historialCompras) > 0): ?>
                            <?php foreach ($historialCompras as $c): ?>
                                <tr>
                                    <td><?php echo $c["id"]; ?></td>
                                    <td><?php echo htmlspecialchars($c["proveedor"]); ?></td>
                                    <td><?php echo $c["fecha"]; ?></td>
                                    <td><?php echo $c["tipo_compra"]; ?></td>
                                    <td>Q<?php echo number_format($c["total"], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay compras registradas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- HISTORIAL DE DEVOLUCIONES -->
    <div class="card shadow card-custom mt-4">
        <div class="card-body p-4">
            <h3 class="titulo-card mb-3">Historial de devoluciones al proveedor</h3>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Proveedor</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Motivo</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($historialDevoluciones) > 0): ?>
                            <?php foreach ($historialDevoluciones as $d): ?>
                                <tr>
                                    <td><?php echo $d["id"]; ?></td>
                                    <td><?php echo htmlspecialchars($d["proveedor"]); ?></td>
                                    <td><?php echo htmlspecialchars($d["producto"]); ?></td>
                                    <td><?php echo $d["cantidad"]; ?></td>
                                    <td><?php echo htmlspecialchars($d["motivo"]); ?></td>
                                    <td><?php echo $d["fecha"]; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No hay devoluciones registradas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- KARDEX -->
    <div class="card shadow card-custom mt-4 mb-5">
        <div class="card-body p-4">
            <h3 class="titulo-card mb-3">Kardex de Compras</h3>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Movimiento</th>
                            <th>Documento</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Stock actual</th>
                            <th>Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($historialKardex) > 0): ?>
                            <?php foreach ($historialKardex as $k): ?>
                                <tr>
                                    <td><?php echo $k["id"]; ?></td>
                                    <td><?php echo $k["fecha"]; ?></td>
                                    <td><?php echo htmlspecialchars($k["producto"]); ?></td>
                                    <td><?php echo htmlspecialchars($k["tipo_movimiento"]); ?></td>
                                    <td><?php echo htmlspecialchars($k["documento"]); ?></td>
                                    <td><?php echo $k["entrada"]; ?></td>
                                    <td><?php echo $k["salida"]; ?></td>
                                    <td><?php echo $k["stock_actual"]; ?></td>
                                    <td><?php echo htmlspecialchars($k["observacion"]); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">No hay movimientos en kardex.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>