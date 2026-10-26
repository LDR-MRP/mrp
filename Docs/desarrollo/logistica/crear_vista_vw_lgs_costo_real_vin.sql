CREATE OR REPLACE VIEW `vw_lgs_costo_real_vin` AS
SELECT 
    ev.id AS id_envio_vin,
    ev.id_envio,
    ev.id_unidad,
    COALESCE(u.vin, ut.clave, CONCAT('VIN-', ev.id_unidad)) AS vin,
    COALESCE(ev.costo_unidad, 0.00) AS costo_planeado_unidad,
    COALESCE(SUM(CASE WHEN g.naturaleza = 'CARGO' AND g.id_estado IN (3, 5) THEN gv.monto ELSE 0 END), 0.00) AS total_cargos_adicionales,
    COALESCE(SUM(CASE WHEN g.naturaleza = 'DEDUCCION' AND g.id_estado IN (3, 5) THEN gv.monto ELSE 0 END), 0.00) AS total_deducciones,
    (COALESCE(ev.costo_unidad, 0.00) + COALESCE(SUM(CASE WHEN g.naturaleza = 'CARGO' AND g.id_estado IN (3, 5) THEN gv.monto ELSE 0 END), 0.00)) AS costo_real_unidad,
    (COALESCE(ev.costo_unidad, 0.00) + COALESCE(SUM(CASE WHEN g.naturaleza = 'CARGO' AND g.id_estado IN (3, 5) THEN gv.monto ELSE 0 END), 0.00) - COALESCE(SUM(CASE WHEN g.naturaleza = 'DEDUCCION' AND g.id_estado IN (3, 5) THEN gv.monto ELSE 0 END), 0.00)) AS costo_neto_unidad,
    COUNT(DISTINCT CASE WHEN g.id_estado IN (1, 2, 3, 5) THEN g.id_gasto END) AS total_gastos_activos
FROM lgs_envios_vins ev
LEFT JOIN lgs_unidades_envios u ON u.id_unidad = ev.id_unidad
LEFT JOIN mrp_unidades_terminadas ut ON ut.idunidad = ev.id_unidad
LEFT JOIN lgs_det_gastos_adicionales_vins gv ON gv.id_envio_vin = ev.id
LEFT JOIN lgs_tra_gastos_adicionales g ON g.id_gasto = gv.id_gasto AND g.deleted_at IS NULL
GROUP BY ev.id, ev.id_envio, ev.id_unidad, u.vin, ut.clave, ev.costo_unidad;
