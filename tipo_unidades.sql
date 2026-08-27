-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 27-08-2026 a las 15:15:40
-- Versión del servidor: 11.8.8-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u546825723_dbfotontru`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_unidades`
--

CREATE TABLE `tipo_unidades` (
  `id_tipo_unidad` int(11) NOT NULL,
  `id_segmento` int(11) NOT NULL,
  `nombre_tipo_unidad` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_unidades`
--

INSERT INTO `tipo_unidades` (`id_tipo_unidad`, `id_segmento`, `nombre_tipo_unidad`) VALUES
(1, 5, 'VIEW'),
(2, 5, 'Grand VIEW'),
(3, 5, 'HiVan'),
(4, 5, 'Tunland'),
(5, 4, 'Wonder'),
(6, 4, 'TM3'),
(7, 2, 'Miler'),
(8, 2, 'S3'),
(9, 2, 'S4'),
(10, 2, 'S5'),
(11, 2, 'S6'),
(12, 3, 'S8'),
(13, 3, 'S12'),
(14, 3, 'S13'),
(15, 3, 'S20'),
(16, 1, 'S35'),
(17, 1, 'GTL'),
(18, 1, 'S40'),
(19, 1, 'EST-A'),
(20, 1, 'Galaxy'),
(21, 1, 'EST'),
(22, 5, 'Buses'),
(23, 1, 'GALAXUS');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `tipo_unidades`
--
ALTER TABLE `tipo_unidades`
  ADD PRIMARY KEY (`id_tipo_unidad`),
  ADD KEY `fk_tipo_unidad_segmento` (`id_segmento`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `tipo_unidades`
--
ALTER TABLE `tipo_unidades`
  MODIFY `id_tipo_unidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `tipo_unidades`
--
ALTER TABLE `tipo_unidades`
  ADD CONSTRAINT `fk_tipo_unidad_segmento` FOREIGN KEY (`id_segmento`) REFERENCES `segmentos` (`id_segmento`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
