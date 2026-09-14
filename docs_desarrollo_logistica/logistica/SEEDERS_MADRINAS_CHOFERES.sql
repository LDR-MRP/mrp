-- ==============================================================================
-- 🚚 SEEDER: TRASLADISTAS, MADRINAS, CHOFERES Y ASIGNACIONES DE EJEMPLO
-- ==============================================================================
-- Este script inserta:
-- 1. Proveedores con giro de Trasladista (si no existen) y su relación en prv_rel_proveedores_actividades.
-- 2. Choferes con licencias federales tipo B y E.
-- 3. Madrinas con distintas capacidades (4, 6, 8, 10 y 12 vehículos) y placas de tracto/caja.
-- 4. Historial y asignación activa de Chofer a Madrina.
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. ACTIVIDAD DE TRASLADISTA
INSERT IGNORE INTO `prv_cat_actividades` (`cve_actividad`, `descripcion`, `estado`) VALUES
('TRASLADO_UNIDADES', 'Traslado y Logística de Unidades (Trasladistas)', 2);

SET @id_act_traslado = (SELECT `id_actividad` FROM `prv_cat_actividades` WHERE `cve_actividad` = 'TRASLADO_UNIDADES' LIMIT 1);

-- 2. PROVEEDORES TRASLADISTAS DE EJEMPLO
INSERT IGNORE INTO `prv_cat_proveedores` (
    `id_empresa`, `rfc`, `razon_social`, `nombre_comercial`, `id_tipo_persona`, 
    `id_regimen_fiscal`, `tipo`, `origen`, `estatus_onboarding`, `estatus_operativo`
) VALUES
(1, 'TLE180420AA1', 'Transportes y Logística Especializada S.A. de C.V.', 'Logística TLE', 1, 1, 'nacional', 'nacional', 3, 1),
(1, 'ATM150912BC3', 'Auto Traslados México S.A. de C.V.', 'ATM Traslados', 1, 1, 'nacional', 'nacional', 3, 1),
(1, 'NFB191105XY8', 'Nodrizas y Fletes del Bajío S.A. de C.V.', 'Nodrizas Bajío', 1, 1, 'nacional', 'nacional', 3, 1);

-- Asociar proveedores a la actividad de TRASLADO_UNIDADES
INSERT IGNORE INTO `prv_rel_proveedores_actividades` (`id_proveedor`, `id_actividad`)
SELECT `id_proveedor`, @id_act_traslado FROM `prv_cat_proveedores` 
WHERE `rfc` IN ('TLE180420AA1', 'ATM150912BC3', 'NFB191105XY8');

-- Obtener IDs de los proveedores para usarlos en madrinas y choferes
SET @id_prov_tle = (SELECT `id_proveedor` FROM `prv_cat_proveedores` WHERE `rfc` = 'TLE180420AA1' LIMIT 1);
SET @id_prov_atm = (SELECT `id_proveedor` FROM `prv_cat_proveedores` WHERE `rfc` = 'ATM150912BC3' LIMIT 1);
SET @id_prov_nfb = (SELECT `id_proveedor` FROM `prv_cat_proveedores` WHERE `rfc` = 'NFB191105XY8' LIMIT 1);

-- Fallback si ya existían otros proveedores
SET @id_prov_tle = COALESCE(@id_prov_tle, 1);
SET @id_prov_atm = COALESCE(@id_prov_atm, @id_prov_tle);
SET @id_prov_nfb = COALESCE(@id_prov_nfb, @id_prov_tle);


-- 3. CHOFERES DE EJEMPLO
INSERT INTO `prv_det_choferes` (
    `id_proveedor`, `nombre`, `apellidos`, `num_licencia`, `tipo_licencia`, 
    `vigencia_licencia`, `telefono`, `estatus_operativo`
) VALUES
(@id_prov_tle, 'Juan Carlos', 'Ramírez Mendoza', 'FED-8492019-E', 'E', '2028-11-15', '477-123-4567', 1),
(@id_prov_tle, 'Roberto', 'Morales Hernández', 'FED-7391028-B', 'B', '2027-06-30', '477-987-6543', 1),
(@id_prov_atm, 'Alejandro', 'Domínguez Luna', 'FED-6284910-E', 'E', '2029-02-14', '331-456-7890', 1),
(@id_prov_atm, 'Sergio', 'García Pérez', 'FED-5193820-B', 'B', '2027-12-01', '554-321-9876', 1),
(@id_prov_nfb, 'Fernando', 'Torres Ruiz', 'FED-9402817-E', 'E', '2028-08-20', '442-567-8901', 1),
(@id_prov_nfb, 'Miguel Ángel', 'Vázquez Cruz', 'FED-4029183-B', 'B', '2026-10-18', '477-332-1144', 1);


-- 4. MADRINAS DE EJEMPLO (TRACTOS + NODRIZAS)
INSERT INTO `prv_det_madrinas` (
    `id_proveedor`, `numero_economico`, `placas`, `placa_caja`, `marca`, 
    `modelo`, `anio`, `color`, `num_serie_vin`, `capacidad_vehiculos`, `estatus_operativo`
) VALUES
(@id_prov_tle, 'ECO-101', '45-AA-1B', '89-BB-2C', 'Freightliner', 'Cascadia', 2023, 'Blanco', '3AKJHHDR8PS109283', 10, 1),
(@id_prov_tle, 'ECO-102', '78-CD-3E', '12-DE-4F', 'Kenworth', 'T680', 2024, 'Azul Marino', '1XKYDB9X4PJ847291', 8, 1),
(@id_prov_atm, 'ECO-201', '90-MN-8P', '23-QR-9S', 'Freightliner', 'Cascadia', 2024, 'Plata', '3AKJHHDR5RS938102', 12, 1),
(@id_prov_atm, 'ECO-202', '11-ST-0U', '44-VW-1X', 'Volvo', 'VNL 860', 2023, 'Blanco', '4V4NC9EH1PN738291', 6, 1),
(@id_prov_nfb, 'ECO-301', '34-GH-5I', '56-JK-7L', 'International', 'LT Series', 2022, 'Rojo', '3HSCUAPR8NN847293', 8, 1),
(@id_prov_nfb, 'ECO-302', '67-XY-9Z', '88-ZA-3B', 'Kenworth', 'T880 Lowboy', 2023, 'Amarillo', '1XKWDB9X1PJ938472', 4, 1);


-- 5. ASIGNACIÓN ACTIVA DE CHOFERES A MADRINAS (HISTORIAL)
-- Asocia cada chofer con su madrina asignada
INSERT INTO `prv_det_madrina_chofer_historial` (`id_madrina`, `id_chofer`, `fecha_inicio`, `activo`, `observaciones`)
SELECT m.id_madrina, c.id_chofer, NOW(), 1, 'Asignación operativa inicial de flota'
FROM prv_det_madrinas m
INNER JOIN prv_det_choferes c ON c.id_proveedor = m.id_proveedor
WHERE m.numero_economico = 'ECO-101' AND c.nombre = 'Juan Carlos'
LIMIT 1;

INSERT INTO `prv_det_madrina_chofer_historial` (`id_madrina`, `id_chofer`, `fecha_inicio`, `activo`, `observaciones`)
SELECT m.id_madrina, c.id_chofer, NOW(), 1, 'Asignación operativa inicial de flota'
FROM prv_det_madrinas m
INNER JOIN prv_det_choferes c ON c.id_proveedor = m.id_proveedor
WHERE m.numero_economico = 'ECO-102' AND c.nombre = 'Roberto'
LIMIT 1;

INSERT INTO `prv_det_madrina_chofer_historial` (`id_madrina`, `id_chofer`, `fecha_inicio`, `activo`, `observaciones`)
SELECT m.id_madrina, c.id_chofer, NOW(), 1, 'Asignación operativa inicial de flota'
FROM prv_det_madrinas m
INNER JOIN prv_det_choferes c ON c.id_proveedor = m.id_proveedor
WHERE m.numero_economico = 'ECO-201' AND c.nombre = 'Alejandro'
LIMIT 1;

INSERT INTO `prv_det_madrina_chofer_historial` (`id_madrina`, `id_chofer`, `fecha_inicio`, `activo`, `observaciones`)
SELECT m.id_madrina, c.id_chofer, NOW(), 1, 'Asignación operativa inicial de flota'
FROM prv_det_madrinas m
INNER JOIN prv_det_choferes c ON c.id_proveedor = m.id_proveedor
WHERE m.numero_economico = 'ECO-301' AND c.nombre = 'Fernando'
LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;
