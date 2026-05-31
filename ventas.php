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
// REGISTRAR VENTA
// ==============================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "guardar_venta") {
    $cliente_nombre = trim($_POST["cliente_nombre"]);
    $producto_id = intval($_POST["producto_id"]);
    $tipo_pago = $_POST["tipo_pago"];
    $cantidad = intval($_POST["cantidad"]);
    $observacion = trim($_POST["observacion"]);

    try {
        $conn->beginTransaction();

        // 1. Obtener producto
        $stmtProd = $conn->prepare("SELECT * FROM productos WHERE id = ?");
        $stmtProd->execute([$producto_id]);
        $producto = $stmtProd->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            throw new Exception("Producto no encontrado.");
        }

        $stockActual = intval($producto["stock"]);
        $precio_unitario = floatval($producto["precio"]);

        if ($cantidad > $stockActual) {
            throw new Exception("No hay suficiente stock para realizar la venta.");
        }

        $subtotal = $cantidad * $precio_unitario;
        $nuevoStock = $stockActual - $cantidad;

        // 2. Guardar venta
        $stmtVenta = $conn->prepare("
            INSERT INTO ventas (cliente_nombre, tipo_pago, total, observacion)
            VALUES (?, ?, ?, ?)
        ");
        $stmtVenta->execute([$cliente_nombre, $tipo_pago, $subtotal, $observacion]);

        $venta_id = $conn->lastInsertId();

        // 3. Guardar detalle
        $stmtDetalle = $conn->prepare("
            INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtDetalle->execute([$venta_id, $producto_id, $cantidad, $precio_unitario, $subtotal]);

        // 4. Actualizar stock
        $stmtUpdate = $conn->prepare("UPDATE productos SET stock = ? WHERE id = ?");
        $stmtUpdate->execute([$nuevoStock, $producto_id]);

        // 5. Registrar kardex
        $documento = "VENTA #" . $venta_id;
        $stmtKardex = $conn->prepare("
            INSERT INTO kardex (producto_id, tipo_movimiento, documento, entrada, salida, stock_actual, observacion)
            VALUES (?, 'VENTA', ?, 0, ?, ?, ?)
        ");
        $stmtKardex->execute([$producto_id, $documento, $cantidad, $nuevoStock, $observacion]);

        $conn->commit();
        header("Location: ventas.php?msg=venta_ok");
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        $mensaje = "Error al registrar venta: " . $e->getMessage();
    }
}

// ==============================
// MENSAJES
// ==============================
if (isset($_GET["msg"])) {
    if ($_GET["msg"] == "venta_ok") {
        $mensaje = "Venta registrada correctamente.";
    }
}

// ==============================
// PRODUCTOS PARA FORMULARIO
// ==============================
$productos = $conn->query("SELECT * FROM productos ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

// ==============================
// HISTORIAL DE VENTAS
// ==============================
$historialVentas = $conn->query("
    SELECT v.id, v.cliente_nombre, v.fecha, v.tipo_pago, v.total
    FROM ventas v
    ORDER BY v.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ==============================
// DETALLE DE VENTAS
// ==============================
$detalleVentas = $conn->query("
    SELECT dv.venta_id, p.nombre AS producto, dv.cantidad, dv.precio_unitario, dv.subtotal
    FROM detalle_ventas dv
    INNER JOIN productos p ON dv.producto_id = p.id
    ORDER BY dv.venta_id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ==============================
// KARDEX DE VENTAS
// ==============================
$kardexVentas = $conn->query("
    SELECT k.id, k.fecha, p.nombre AS producto, k.documento, k.salida, k.stock_actual, k.observacion
    FROM kardex k
    INNER JOIN productos p ON k.producto_id = p.id
    WHERE k.tipo_movimiento = 'VENTA'
    ORDER BY k.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ==============================
// REPORTE SIMPLE
// ==============================
$reporteVenta = $conn->query("
    SELECT 
        COUNT(*) AS total_ventas,
        COALESCE(SUM(total), 0) AS monto_total
    FROM ventas
")->fetch(PDO::FETCH_ASSOC);

$reporteProductosVendidos = $conn->query("
    SELECT COALESCE(SUM(cantidad), 0) AS productos_vendidos
    FROM detalle_ventas
")->fetch(PDO::FETCH_ASSOC);

$totalVentas = $reporteVenta["total_ventas"] ?? 0;
$montoTotal = $reporteVenta["monto_total"] ?? 0;
$productosVendidos = $reporteProductosVendidos["productos_vendidos"] ?? 0;

// ==============================
// COMPROBANTES
// ==============================
$mostrarComprobante = false;
$tipoComprobante = "";
$ventaComprobante = null;
$detalleComprobante = [];

if (isset($_GET["comprobante"]) && isset($_GET["id"])) {
    $tipoComprobante = $_GET["comprobante"];
    $ventaId = intval($_GET["id"]);

    $stmtVentaComp = $conn->prepare("
        SELECT id, cliente_nombre, fecha, tipo_pago, total, observacion
        FROM ventas
        WHERE id = ?
    ");
    $stmtVentaComp->execute([$ventaId]);
    $ventaComprobante = $stmtVentaComp->fetch(PDO::FETCH_ASSOC);

    if ($ventaComprobante) {
        $stmtDetalleComp = $conn->prepare("
            SELECT p.nombre AS producto, dv.cantidad, dv.precio_unitario, dv.subtotal
            FROM detalle_ventas dv
            INNER JOIN productos p ON dv.producto_id = p.id
            WHERE dv.venta_id = ?
        ");
        $stmtDetalleComp->execute([$ventaId]);
        $detalleComprobante = $stmtDetalleComp->fetchAll(PDO::FETCH_ASSOC);

        $mostrarComprobante = true;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Módulo de Ventas</title>
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

        .dropdown-menu {
            border-radius: 12px;
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

        @media print {
            body * {
                visibility: hidden;
            }

            #zonaImpresion, #zonaImpresion * {
                visibility: visible;
            }

            #zonaImpresion {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                background: white;
                padding: 20px;
            }

            .no-imprimir {
                display: none !important;
            }
        }
    </style>
</head>

<body>

<div class="container py-4">

    <!-- BOTONES SUPERIORES -->
    <div class="mb-4 d-flex gap-2">
        <a href="guardar.php" class="btn btn-secondary no-imprimir">🔙 Regresar al menú</a>
        <a href="ventas.php" class="btn btn-outline-primary no-imprimir">💵 Volver a ventas</a>
    </div>

    <h1 class="text-center mb-4 titulo-card">Módulo de Ventas</h1>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- RESUMEN / REPORTE SIMPLE -->
    <div class="row g-3 mb-4 no-imprimir">
        <div class="col-md-4">
            <div class="card resumen-card bg-card-1 shadow">
                <div class="card-body">
                    <h6 class="mb-2">Total de ventas</h6>
                    <h3 class="mb-0"><?php echo $totalVentas; ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card resumen-card bg-card-2 shadow">
                <div class="card-body">
                    <h6 class="mb-2">Monto total vendido</h6>
                    <h3 class="mb-0">Q<?php echo number_format($montoTotal, 2); ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card resumen-card bg-card-3 shadow">
                <div class="card-body">
                    <h6 class="mb-2">Productos vendidos</h6>
                    <h3 class="mb-0"><?php echo $productosVendidos; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- COMPROBANTE -->
    <?php if ($mostrarComprobante && $ventaComprobante): ?>
        <div class="card shadow card-custom mb-4" id="zonaImpresion">
            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="empresa-box">
                        <h4>POS Retail</h4>
                        <small>Sistema de Ventas e Inventario</small>
                        <small>NIT: 9901004-6</small>
                        <small>Guatemala, Guatemala</small>
                        <small>Tel: 5153-5089</small>
                    </div>

                    <div class="text-end">
                        <h2 class="titulo-card mb-2 text-uppercase">
                            <?php echo $tipoComprobante == "venta" ? "Factura de Venta" : "Comprobante de Pago"; ?>
                        </h2>

                        <button onclick="window.print()" class="btn btn-dark no-imprimir">🖨️ Imprimir</button>
                    </div>
                </div>

                <hr>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>No. Documento:</strong> <?php echo $ventaComprobante["id"]; ?></p>
                        <p><strong>Cliente:</strong> <?php echo htmlspecialchars($ventaComprobante["cliente_nombre"]); ?></p>
                        <p><strong>Fecha:</strong> <?php echo $ventaComprobante["fecha"]; ?></p>
                    </div>

                    <div class="col-md-6">
                        <p><strong>Tipo de pago:</strong> <?php echo htmlspecialchars($ventaComprobante["tipo_pago"]); ?></p>
                        <p><strong>Total:</strong> Q<?php echo number_format($ventaComprobante["total"], 2); ?></p>
                        <p><strong>Observación:</strong> <?php echo htmlspecialchars($ventaComprobante["observacion"]); ?></p>
                    </div>
                </div>

                <?php if ($tipoComprobante == "venta"): ?>
                    <h5 class="mb-3">Detalle de productos</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio unitario</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detalleComprobante as $d): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($d["producto"]); ?></td>
                                        <td><?php echo $d["cantidad"]; ?></td>
                                        <td>Q<?php echo number_format($d["precio_unitario"], 2); ?></td>
                                        <td>Q<?php echo number_format($d["subtotal"], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success mt-3">
                        Se confirma el pago por <strong>Q<?php echo number_format($ventaComprobante["total"], 2); ?></strong>
                        correspondiente a la venta No. <strong><?php echo $ventaComprobante["id"]; ?></strong>.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- FORMULARIO -->
        <div class="col-md-4">
            <div class="card shadow card-custom">
                <div class="card-body p-4">
                    <h3 class="titulo-card mb-3 text-center">Nueva Venta</h3>

                    <form method="POST">
                        <input type="hidden" name="accion" value="guardar_venta">

                        <div class="mb-3">
                            <label class="form-label">Cliente</label>
                            <input type="text" name="cliente_nombre" class="form-control" placeholder="Nombre del cliente" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Producto</label>
                            <select name="producto_id" class="form-select" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($productos as $prod): ?>
                                    <option value="<?php echo $prod["id"]; ?>">
                                        <?php echo htmlspecialchars($prod["nombre"]); ?>
                                        (Stock: <?php echo $prod["stock"]; ?> | Precio: Q<?php echo number_format($prod["precio"], 2); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tipo de pago</label>
                            <select name="tipo_pago" class="form-select" required>
                                <option value="CONTADO">Contado</option>
                                <option value="CREDITO">Crédito</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cantidad</label>
                            <input type="number" min="1" name="cantidad" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Observación</label>
                            <input type="text" name="observacion" class="form-control" placeholder="Opcional">
                        </div>

                        <button class="btn btn-primary w-100">Guardar venta</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- HISTORIAL -->
        <div class="col-md-8">
            <div class="card shadow card-custom">
                <div class="card-body p-4">
                    <h3 class="titulo-card mb-3 text-center">Historial de Ventas</h3>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Tipo de pago</th>
                                    <th>Total</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($historialVentas) > 0): ?>
                                    <?php foreach ($historialVentas as $venta): ?>
                                        <tr>
                                            <td><?php echo $venta["id"]; ?></td>
                                            <td><?php echo htmlspecialchars($venta["cliente_nombre"]); ?></td>
                                            <td><?php echo $venta["fecha"]; ?></td>
                                            <td><?php echo $venta["tipo_pago"]; ?></td>
                                            <td>Q<?php echo number_format($venta["total"], 2); ?></td>
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <button class="btn btn-primary btn-sm dropdown-toggle"
                                                            type="button"
                                                            data-bs-toggle="dropdown"
                                                            aria-expanded="false">
                                                        ⚙️ Opciones
                                                    </button>

                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item"
                                                               href="ventas.php?comprobante=venta&id=<?php echo $venta['id']; ?>">
                                                                🧾 Ver comprobante de venta
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item"
                                                               href="ventas.php?comprobante=pago&id=<?php echo $venta['id']; ?>">
                                                                💳 Ver comprobante de pago
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No hay ventas registradas.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- DETALLE DE VENTAS -->
    <div class="card shadow card-custom mt-4">
        <div class="card-body p-4">
            <h3 class="titulo-card mb-3 text-center">Detalle de Ventas</h3>

            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID Venta</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio unitario</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($detalleVentas) > 0): ?>
                            <?php foreach ($detalleVentas as $d): ?>
                                <tr>
                                    <td><?php echo $d["venta_id"]; ?></td>
                                    <td><?php echo htmlspecialchars($d["producto"]); ?></td>
                                    <td><?php echo $d["cantidad"]; ?></td>
                                    <td>Q<?php echo number_format($d["precio_unitario"], 2); ?></td>
                                    <td>Q<?php echo number_format($d["subtotal"], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay detalles registrados.</td>
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
            <h3 class="titulo-card mb-3 text-center">Kardex de Ventas</h3>

            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Documento</th>
                            <th>Salida</th>
                            <th>Stock actual</th>
                            <th>Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($kardexVentas) > 0): ?>
                            <?php foreach ($kardexVentas as $k): ?>
                                <tr>
                                    <td><?php echo $k["id"]; ?></td>
                                    <td><?php echo $k["fecha"]; ?></td>
                                    <td><?php echo htmlspecialchars($k["producto"]); ?></td>
                                    <td><?php echo htmlspecialchars($k["documento"]); ?></td>
                                    <td><?php echo $k["salida"]; ?></td>
                                    <td><?php echo $k["stock_actual"]; ?></td>
                                    <td><?php echo htmlspecialchars($k["observacion"]); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No hay movimientos en kardex de ventas.</td>
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