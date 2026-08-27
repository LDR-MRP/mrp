-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 27-08-2026 a las 15:15:50
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
-- Estructura de tabla para la tabla `modelos`
--

CREATE TABLE `modelos` (
  `id_modelo` int(11) NOT NULL,
  `id_tipo_unidad` int(11) NOT NULL,
  `nombre_modelo` varchar(50) NOT NULL,
  `nombre_producto` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `modelos`
--

INSERT INTO `modelos` (`id_modelo`, `id_tipo_unidad`, `nombre_modelo`, `nombre_producto`) VALUES
(1, 22, 'BECCAR URBI G2 (Ver B)', 'D9-BECCAR URBI G2'),
(2, 22, 'Chasis Araña', 'AUV BJ6118 Chasis CNG'),
(3, 22, 'Chasis Araña', 'FOTON D9 Midibus (Nacional)'),
(4, 19, 'EST-A 283253-(GNC)', 'EST-A 2853-(CNG)'),
(5, 19, 'EST-A 6x2', 'EST-A 6x2'),
(6, 19, 'EST-A 6x4', 'EST-A 6x4'),
(7, 19, 'EST-A 6x4 560', 'N/D'),
(8, 19, 'NO SE ENCUENTRA', 'EST-A / 3246 Relacion (3.083)'),
(9, 19, 'NO SE ENCUENTRA', 'EST-A / 3246 Relacion (3.364)'),
(10, 22, 'FOTON-D9 Midibus (Ver B)', 'FOTON D9 Midibus (Nacional)'),
(11, 20, 'Galaxy / 3256', 'Galaxy / 3256 / Rel.:2.71 (Nacional)'),
(12, 20, 'Galaxy / 3256', 'Galaxy / 3256 / Rel.:3.08 (Nacional)'),
(13, 20, 'Galaxy / 3256', 'Galaxy / 3256 / Rel.:3.36 (Nacional)'),
(14, 20, 'Galaxy / 3256', 'Galaxy / 3256 / Rel.:3.70 (Nacional)'),
(15, 7, 'Miler 4.5T DR', 'Miler 2'),
(16, 7, 'Miler 4.84.5T RS', 'Miler 3'),
(17, 22, 'S10', 'Aumark S10'),
(18, 13, 'S12', 'Aumark S12-2402	'),
(19, 13, 'S12', 'Aumark S12-EV'),
(20, 13, 'S12-E6', 'Aumark S12-E6 (Nacional)	'),
(21, 15, 'NO SE ENCUENTRA', 'Aumark S20	'),
(22, 8, 'S3', 'Aumark S3 (Importado)'),
(23, 8, 'S3', 'Aumark S3	'),
(24, 8, 'S3 EV', 'Aumark S3 EV	'),
(25, 16, 'S35', 'Aumark S35	'),
(26, 8, 'S3-E6 AMT', 'Aumark S3-E6-AMT	'),
(27, 8, 'S3-E6 MT', 'Aumark S3-E6-MT	'),
(28, 10, 'S5', 'Aumark S5	'),
(29, 10, 'S5-E6 AMT', 'Aumark S5-E6-AMT (Importado)	'),
(30, 10, 'S5-E6 AMT', 'Aumark S5-E6-AMT (Nacional)	'),
(31, 10, 'S5-E6 MT', 'Aumark S5-E6-MT (Importado)	'),
(32, 10, 'S5-E6 MT', 'Aumark S5-E6-MT (Nacional)	'),
(33, 11, 'S6', 'Aumark S6'),
(34, 11, 'S6', 'Aumark S6 (Nacional)'),
(35, 11, 'S6-E6 MT', 'Aumark S6-E6-MT (Importado)'),
(36, 11, 'S6-E6 MT', 'Aumark S6-E6-MT (Nacional)'),
(37, 12, 'S8', 'Aumark S8'),
(38, 12, 'S8', 'Aumark S8 (R19.5)'),
(39, 6, 'TM Chasis C/AB', 'Aumark TM'),
(40, 3, 'Toano Panel', 'TOANO P'),
(41, 3, 'Toano Panel', 'TOANO PANEL HR TM'),
(42, 3, 'Toano Pasajero', 'TOANO PASAJEROS'),
(43, 4, 'Tunland E5', 'TUNLAND G-CS	'),
(44, 4, 'Tunland G7 AT', 'Tunland G7 AT	'),
(45, 4, 'Tunland G7 MT', 'Tunland G7 MT	'),
(46, 4, 'Tunland G7 MT Gasolina', 'Tunland G7 MT Gasolina	'),
(47, 4, 'Tunland G9 AT', 'Tunland G9 AT	'),
(48, 4, 'Tunland V7 (MHEV)', 'Tunland V7 (MHEV)	'),
(49, 4, 'Tunland V9 (MHEV)', 'Tunland V9 (MHEV)'),
(50, 1, 'View CS2 Panel', 'View CS2 P	'),
(51, 1, 'View CS2 Panel', 'VIEW CS2-2501 Panel (Nacional)'),
(52, 1, 'View CS2 Royal', 'View CS2	'),
(53, 5, 'Wonder', 'N/D'),
(54, 5, 'Wonder EV', 'Wonder EV	'),
(55, 22, 'AYCO ORION FT', 'AYCO ORION FT'),
(56, 21, 'EST S38', 'EST S38 AMT'),
(57, 3, 'HiVan Pasajeros', 'HIVAN PASAJEROS'),
(58, 1, 'View CS2 Pasajeros', 'VIEW CS2 Pasajeros'),
(59, 1, 'VIEW EV', ''),
(60, 1, 'TM EV', ''),
(61, 1, 'Tunland V7 gasolina', ''),
(62, 1, 'Tunland G7 Chasis', ''),
(63, 4, 'TUNLAND EV', ''),
(64, 4, 'Tunland V7 gasolina 4x2', ''),
(65, 4, 'Tunland V7 gasolina 4x4', ''),
(66, 3, 'EV-Hivan Pro', 'EV-Hivan Pro'),
(67, 12, 'S8-E6 AMT', 'Aumark S8 AMT'),
(69, 6, 'TM3 1.6L', 'TM3 1.6L'),
(70, 17, 'GTL / 2491 / Rel.:3.08', 'GTL / 2491 / Rel.:3.08'),
(71, 3, 'HiVan-EV', 'HiVan-EV'),
(72, 7, 'Miler-EV', 'Miler-EV'),
(73, 3, 'HiVan Panel', 'HiVan Panel'),
(74, 23, 'Galaxus', 'Galaxus / Rel.: 4.10'),
(75, 23, 'Galaxus', 'Galaxus / Rel.: 3.70'),
(76, 23, 'Galaxus', 'Galaxus / Rel.: 3.36'),
(77, 2, 'VIEW Grand AT Panel', 'VIEW Grand AT Panel');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `modelos`
--
ALTER TABLE `modelos`
  ADD PRIMARY KEY (`id_modelo`),
  ADD KEY `fk_modelos_tipo_unidad` (`id_tipo_unidad`),
  ADD KEY `idx_modelos_id_modelo` (`id_modelo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `modelos`
--
ALTER TABLE `modelos`
  MODIFY `id_modelo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
