-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 31-05-2026 a las 05:38:10
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `proyecto-analisis-ev`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compras`
--

CREATE TABLE `compras` (
  `id` int(11) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `tipo_compra` enum('CONTADO','CREDITO') NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `observacion` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `compras`
--

INSERT INTO `compras` (`id`, `proveedor_id`, `fecha`, `tipo_compra`, `total`, `observacion`) VALUES
(1, 1, '2026-05-30 13:33:42', 'CREDITO', 4500.00, ''),
(2, 3, '2026-05-30 13:57:37', 'CONTADO', 6000.00, ''),
(3, 4, '2026-05-30 20:19:19', 'CONTADO', 4000.00, ''),
(4, 4, '2026-05-30 21:09:19', 'CONTADO', 20.00, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_compras`
--

CREATE TABLE `detalle_compras` (
  `id` int(11) NOT NULL,
  `compra_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_compras`
--

INSERT INTO `detalle_compras` (`id`, `compra_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 2, 3, 1500.00, 4500.00),
(2, 2, 4, 5, 1200.00, 6000.00),
(3, 3, 5, 20, 200.00, 4000.00),
(4, 4, 6, 1, 20.00, 20.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ventas`
--

CREATE TABLE `detalle_ventas` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_ventas`
--

INSERT INTO `detalle_ventas` (`id`, `venta_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 2, 2, 5500.00, 11000.00),
(2, 2, 3, 1, 300.00, 300.00),
(3, 3, 5, 13, 250.00, 3250.00),
(4, 4, 4, 2, 1100.00, 2200.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `devoluciones_proveedor`
--

CREATE TABLE `devoluciones_proveedor` (
  `id` int(11) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `motivo` varchar(150) DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `devoluciones_proveedor`
--

INSERT INTO `devoluciones_proveedor` (`id`, `proveedor_id`, `producto_id`, `cantidad`, `motivo`, `fecha`) VALUES
(1, 2, 3, 2, 'en mal estado', '2026-05-30 13:34:12'),
(2, 2, 6, 1, 'dañado', '2026-05-30 21:08:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `kardex`
--

CREATE TABLE `kardex` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `tipo_movimiento` varchar(50) NOT NULL,
  `documento` varchar(50) NOT NULL,
  `entrada` int(11) DEFAULT 0,
  `salida` int(11) DEFAULT 0,
  `stock_actual` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `observacion` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `kardex`
--

INSERT INTO `kardex` (`id`, `producto_id`, `tipo_movimiento`, `documento`, `entrada`, `salida`, `stock_actual`, `fecha`, `observacion`) VALUES
(1, 2, 'VENTA', 'VENTA #1', 0, 2, 0, '2026-05-30 13:24:25', ''),
(2, 2, 'COMPRA', 'COMPRA #1', 3, 0, 3, '2026-05-30 13:33:42', ''),
(3, 3, 'DEVOLUCION_PROVEEDOR', 'DEVOLUCIÓN #1', 0, 2, 3, '2026-05-30 13:34:12', 'en mal estado'),
(4, 3, 'VENTA', 'VENTA #2', 0, 1, 2, '2026-05-30 13:41:39', ''),
(5, 4, 'COMPRA', 'COMPRA #2', 5, 0, 10, '2026-05-30 13:57:37', ''),
(6, 5, 'COMPRA', 'COMPRA #3', 20, 0, 40, '2026-05-30 20:19:19', ''),
(7, 5, 'VENTA', 'VENTA #3', 0, 13, 27, '2026-05-30 20:25:20', ''),
(8, 6, 'DEVOLUCION_PROVEEDOR', 'DEVOLUCIÓN #2', 0, 1, 2, '2026-05-30 21:08:17', 'dañado'),
(9, 6, 'COMPRA', 'COMPRA #4', 1, 0, 3, '2026-05-30 21:09:19', ''),
(10, 4, 'VENTA', 'VENTA #4', 0, 2, 10, '2026-05-30 21:10:21', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_eliminacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `nombre`, `descripcion`, `precio`, `stock`, `estado`, `fecha_eliminacion`) VALUES
(2, 'computadora', 'dell', 5500.00, 3, 1, NULL),
(3, 'case', 'floreado', 300.00, 2, 0, '2026-05-30 20:22:41'),
(4, 'Disco Duro', '1 TB, Kingston', 1100.00, 10, 1, NULL),
(5, 'mouse', 'inalámbrico', 250.00, 27, 1, NULL),
(6, 'cable usb', '', 20.00, 3, 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `direccion` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `nombre`, `telefono`, `correo`, `direccion`) VALUES
(1, 'Grupo Quattro', '2222-2020', 'grupo@quattro.com.gt', 'zona 10'),
(2, 'intelaf', '1010-2323', 'intelaf@gmail.com', 'zona 9'),
(3, 'kemik', '1234-5678', 'kemik@gmail.com', 'zona 1'),
(4, 'Distelsa', '1234-5678', 'distelsa@gmail.com', 'ciudad');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `intentos_fallidos` int(11) NOT NULL,
  `bloqueado` tinyint(4) NOT NULL,
  `token_recuperacion` varchar(100) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password_hash`, `intentos_fallidos`, `bloqueado`, `token_recuperacion`, `token_expira`) VALUES
(1, 'Admin', 'admin@admin.com', '$2y$10$2D/HOiqaMAnX1AgGLjr/0.yZlecpST1I/aKlXLat7ybmUFe80rVCa', 0, 0, NULL, NULL),
(2, 'Evelin Rivera', 'erivera@miumg.edu.gt', '$2y$10$4M7rSfhRo5LIkGH8NtNnH.ULrO7kApUpGrXwmhcgjs7At.qdVEd6a', 0, 0, NULL, NULL),
(3, 'Marilu Gamboa', 'evelinriv99@gmail.com', '$2y$10$EAcUt3VItD/2U2i8abQWAOZz4TtNgHK0geayZHg18xQYZelPfAO/2', 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `cliente_nombre` varchar(100) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `tipo_pago` enum('CONTADO','CREDITO') NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `observacion` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `cliente_nombre`, `fecha`, `tipo_pago`, `total`, `observacion`) VALUES
(1, 'grupo Quattro', '2026-05-30 13:24:25', 'CONTADO', 11000.00, ''),
(2, 'Juan Perez', '2026-05-30 13:41:39', 'CONTADO', 300.00, ''),
(3, 'Mariano Diaz', '2026-05-30 20:25:20', 'CREDITO', 3250.00, ''),
(4, 'Juan Lopéz', '2026-05-30 21:10:21', 'CREDITO', 2200.00, '');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proveedor_id` (`proveedor_id`);

--
-- Indices de la tabla `detalle_compras`
--
ALTER TABLE `detalle_compras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `compra_id` (`compra_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_id` (`venta_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `devoluciones_proveedor`
--
ALTER TABLE `devoluciones_proveedor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proveedor_id` (`proveedor_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `kardex`
--
ALTER TABLE `kardex`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `compras`
--
ALTER TABLE `compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `detalle_compras`
--
ALTER TABLE `detalle_compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `devoluciones_proveedor`
--
ALTER TABLE `devoluciones_proveedor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `kardex`
--
ALTER TABLE `kardex`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `compras`
--
ALTER TABLE `compras`
  ADD CONSTRAINT `compras_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`);

--
-- Filtros para la tabla `detalle_compras`
--
ALTER TABLE `detalle_compras`
  ADD CONSTRAINT `detalle_compras_ibfk_1` FOREIGN KEY (`compra_id`) REFERENCES `compras` (`id`),
  ADD CONSTRAINT `detalle_compras_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD CONSTRAINT `detalle_ventas_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`),
  ADD CONSTRAINT `detalle_ventas_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `devoluciones_proveedor`
--
ALTER TABLE `devoluciones_proveedor`
  ADD CONSTRAINT `devoluciones_proveedor_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`),
  ADD CONSTRAINT `devoluciones_proveedor_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `kardex`
--
ALTER TABLE `kardex`
  ADD CONSTRAINT `kardex_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
