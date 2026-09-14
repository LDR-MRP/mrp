-- Script para crear la tabla ficticia de unidades de envíos con Origen y Destino
CREATE TABLE IF NOT EXISTS `lgs_unidades_envios` (
  `id_unidad` int(11) NOT NULL AUTO_INCREMENT,
  `vin` varchar(50) NOT NULL UNIQUE,
  `num_serie` varchar(50) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `origen` varchar(150) DEFAULT NULL,
  `destino` varchar(150) DEFAULT NULL,
  `estatus` varchar(50) DEFAULT 'disponible',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_unidad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar unidades de prueba con Origen y Destino
INSERT INTO `lgs_unidades_envios` (`vin`, `num_serie`, `modelo`, `origen`, `destino`, `estatus`)
VALUES
  ('VIN-2026-TOL-001', 'SN-8801', 'Camión Eléctrico E-Truck 4x2', 'Planta Toluca', 'Distribuidor CDMX Sur', 'disponible'),
  ('VIN-2026-TOL-002', 'SN-8802', 'Tractocamión Heavy Duty 6x4', 'Planta Toluca', 'Agencia Monterrey', 'disponible'),
  ('VIN-2026-TOL-003', 'SN-8803', 'Van Carga Urbana 3.5T', 'Planta Toluca', 'Puebla Centro', 'disponible'),
  ('VIN-2026-TOL-004', 'SN-8804', 'Chasis Cabina Diesel', 'Planta Toluca', 'Guadalajara Norte', 'disponible'),
  ('VIN-2026-TOL-005', 'SN-8805', 'Autobús Urbano 30 Pasajeros', 'Planta Toluca', 'Querétaro Parque Ind.', 'disponible'),
  ('VIN-2026-TOL-006', 'SN-8806', 'Camión de Volteo 14m3', 'Planta Toluca', 'León Guanajuato', 'disponible'),
  ('VIN-2026-TOL-007', 'SN-8807', 'Pickup 4x4 Doble Cabina', 'Planta Toluca', 'Veracruz Puerto', 'disponible'),
  ('VIN-2026-TOL-008', 'SN-8808', 'Panel Repartidor 2.0L', 'Planta Toluca', 'San Luis Potosí', 'disponible')
ON DUPLICATE KEY UPDATE `estatus` = VALUES(`estatus`);
