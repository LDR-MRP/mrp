-- =============================================================================
-- ETL DEFINITIVO E IDEMPOTENTE: Cruce Aprobado (Clientes -> cli-mrp)
-- Reglas estrictas:
--   1) Conserva 100% INTACTOS los 26 clientes MRP Base (33 filas) y sus 25 pedidos
--      (solo rellena la columna nueva grupo_comercial para mostrar el grupo en selects).
--   2) Conserva TODOS los clientes de prueba / pedidos existentes sin tocar ni borrar.
--   3) Sube EXCLUSIVAMENTE los 23 clientes nuevos aprobados (24 filas en el cruce):
--        - 17 Distribuidores (18 sedes con domicilio completo; SSANDIER tiene 2)
--        -  6 Clientes gubernamentales (sin sede, con domicilio en cli_direcciones)
--   4) 100% idempotente (puede ejecutarse 2 veces sin duplicar nada).
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. Llenar únicamente grupo_comercial en clientes MRP Base existentes (por RFC)
--    (NO modifica razón social, teléfono, correo, sucursales ni pedidos)
-- -----------------------------------------------------------------------------
UPDATE cli_clientes dst
INNER JOIN cli_clientes_fiscal f ON f.idcliente = dst.idcliente
INNER JOIN (
    SELECT
        UPPER(REPLACE(REPLACE(TRIM(s.rfc), '-', ''), ' ', '')) AS clean_rfc,
        MAX(NULLIF(LEFT(TRIM(g.nombre), 120), '')) AS grupo_nom
    FROM stg_ext_cli_clientes s
    INNER JOIN stg_ext_cli_grupos g ON g.id = s.grupo_id AND g.estado <> 0
    WHERE s.estado = 2
      AND s.id NOT IN (51) -- id=51 Camiones Asia Pacífico se sube como cliente nuevo independiente
      AND s.rfc IS NOT NULL
      AND CHAR_LENGTH(UPPER(REPLACE(REPLACE(TRIM(s.rfc), '-', ''), ' ', ''))) IN (12, 13)
    GROUP BY UPPER(REPLACE(REPLACE(TRIM(s.rfc), '-', ''), ' ', ''))
) og ON og.clean_rfc = f.rfc
SET dst.grupo_comercial = og.grupo_nom
WHERE (dst.grupo_comercial IS NULL OR TRIM(dst.grupo_comercial) = '')
  AND og.grupo_nom IS NOT NULL;

-- -----------------------------------------------------------------------------
-- 2. Registrar alertas de revisión del cruce definitivo (informativas)
-- -----------------------------------------------------------------------------
INSERT INTO stg_ext_alertas (entidad, src_id, severidad, detalle)
SELECT
    'cli_clientes_fiscal',
    s.id,
    'WARN',
    CONCAT(
        'Cliente nuevo "', s.razon_social,
        '" comparte RFC ', UPPER(REPLACE(REPLACE(TRIM(s.rfc), '-', ''), ' ', '')),
        ' con cliente existente idcliente=', f.idcliente,
        ' -> se inserta en cli_clientes y sucursal, se omite RFC duplicado en cli_clientes_fiscal'
    )
FROM stg_ext_cli_clientes s
INNER JOIN cli_clientes_fiscal f
    ON f.rfc = UPPER(REPLACE(REPLACE(TRIM(s.rfc), '-', ''), ' ', ''))
WHERE s.id IN (12, 23, 30, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 65, 66, 67, 68, 69, 70)
  AND NOT EXISTS (
      SELECT 1 FROM cli_clientes c
      WHERE c.codigo_cliente = CONCAT('CLI-SYNC-', LPAD(s.id, 6, '0'))
        AND c.idcliente = f.idcliente
  );

-- -----------------------------------------------------------------------------
-- 3. Insertar los 23 clientes NUEVOS aprobados del cruce definitivo
--    Cabezas: 12, 23, 30, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63 (+64 SSANDIER), 65..70
-- -----------------------------------------------------------------------------

-- 3.1 Mapear los que ya se hubieran insertado en una corrida previa (idempotencia)
INSERT INTO stg_ext_map_clientes (src_id, mrp_idcliente, es_cabeza, match_tipo, accion)
SELECT
    s.id,
    c.idcliente,
    1,
    'CODIGO_SYNC',
    'EXISTENTE'
FROM stg_ext_cli_clientes s
INNER JOIN cli_clientes c
    ON c.codigo_cliente = CONCAT('CLI-SYNC-', LPAD(s.id, 6, '0'))
WHERE s.id IN (12, 23, 30, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 65, 66, 67, 68, 69, 70);

-- 3.2 Insertar los faltantes de los 23 clientes nuevos aprobados
INSERT INTO cli_clientes (
    idtipo_cliente,
    idregimen_fiscal,
    tipo_persona,
    codigo_cliente,
    razon_social,
    nombre_comercial,
    grupo_comercial,
    correo,
    sitio_web,
    fecha_alta,
    telefono,
    celular,
    estado,
    clave_distribuidor,
    zona_comercial,
    territorio,
    responsable_comercial,
    requiere_acceso_portal,
    correo_acceso,
    numero_empleado,
    departamento,
    centro_costos,
    jefe_inmediato,
    correo_corporativo,
    origen_cliente,
    ejecutivo_asignado,
    segmento_mercado,
    dependencia,
    unidad_administrativa,
    nivel_gobierno,
    partida_presupuestal,
    tipo_contratacion,
    usuarioid
)
SELECT
    COALESCE(dtc.id, @sync_default_tipo_cliente_id) AS idtipo_cliente,
    COALESCE(drf.id, @sync_default_regimen_id) AS idregimen_fiscal,
    CASE
        WHEN UPPER(TRIM(COALESCE(s.tipo_persona, ''))) IN ('1', 'FISICA', 'FÍSICA', 'PERSONA FISICA', 'PERSONA FÍSICA') THEN 'FISICA'
        WHEN UPPER(TRIM(COALESCE(s.tipo_persona, ''))) IN ('2', 'MORAL', 'PERSONA MORAL') THEN 'MORAL'
        WHEN CHAR_LENGTH(UPPER(REPLACE(REPLACE(TRIM(COALESCE(s.rfc, '')), '-', ''), ' ', ''))) = 13 THEN 'FISICA'
        ELSE 'MORAL'
    END AS tipo_persona,
    CONCAT('CLI-SYNC-', LPAD(s.id, 6, '0')) AS codigo_cliente,
    LEFT(
        COALESCE(
            NULLIF(TRIM(s.razon_social), ''),
            NULLIF(TRIM(CONCAT_WS(' ', s.nombre_fisica, s.apellido_paterno, s.apellido_materno)), ''),
            NULLIF(TRIM(s.nombre_comercial), ''),
            CONCAT('CLIENTE SIN NOMBRE #', s.id)
        ),
        200
    ) AS razon_social,
    NULLIF(LEFT(TRIM(s.nombre_comercial), 200), '') AS nombre_comercial,
    NULLIF(LEFT(TRIM(sgrp.nombre), 120), '') AS grupo_comercial,
    NULLIF(LEFT(TRIM(s.correo), 150), '') AS correo,
    '' AS sitio_web,
    COALESCE(DATE(s.fecha_registro), CURDATE()) AS fecha_alta,
    NULLIF(LEFT(TRIM(s.telefono), 30), '') AS telefono,
    NULL AS celular,
    2 AS estado,
    '' AS clave_distribuidor,
    '' AS zona_comercial,
    '' AS territorio,
    '' AS responsable_comercial,
    0 AS requiere_acceso_portal,
    '' AS correo_acceso,
    '' AS numero_empleado,
    '' AS departamento,
    '' AS centro_costos,
    '' AS jefe_inmediato,
    '' AS correo_corporativo,
    'SYNC_EXTERNO' AS origen_cliente,
    '' AS ejecutivo_asignado,
    '' AS segmento_mercado,
    '' AS dependencia,
    '' AS unidad_administrativa,
    '' AS nivel_gobierno,
    '' AS partida_presupuestal,
    '' AS tipo_contratacion,
    @sync_usuario_id AS usuarioid
FROM stg_ext_cli_clientes s
LEFT JOIN stg_ext_map_clientes m ON m.src_id = s.id
LEFT JOIN stg_ext_cli_tipos_clientes stc ON stc.id = s.tipo_cliente_id
LEFT JOIN cli_tipos_cliente dtc ON dtc.estado <> 0 AND (
    (UPPER(COALESCE(stc.nombre, '')) LIKE '%DISTRIBUIDOR%' AND dtc.id = 1)
    OR (UPPER(COALESCE(stc.nombre, '')) LIKE '%INTERN%' AND dtc.id = 2)
    OR (UPPER(COALESCE(stc.nombre, '')) LIKE '%EXTERN%' AND dtc.id = 3)
    OR (UPPER(COALESCE(stc.nombre, '')) LIKE '%GUBERNAM%' AND dtc.id = 4)
)
LEFT JOIN stg_ext_cli_regimenes_fiscales srf ON srf.id = s.regimen_fiscal_id
LEFT JOIN cli_regimenes_fiscales drf ON TRIM(drf.c_regimen_fiscal) = TRIM(srf.c_regimen_fiscal) AND drf.estado <> 0
LEFT JOIN stg_ext_cli_grupos sgrp ON sgrp.id = s.grupo_id AND sgrp.estado <> 0
WHERE s.id IN (12, 23, 30, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 65, 66, 67, 68, 69, 70)
  AND m.src_id IS NULL;

-- Registrar los recién insertados en el mapa
INSERT INTO stg_ext_map_clientes (src_id, mrp_idcliente, es_cabeza, match_tipo, accion)
SELECT
    s.id,
    c.idcliente,
    1,
    'NUEVO',
    'INSERT'
FROM stg_ext_cli_clientes s
INNER JOIN cli_clientes c
    ON c.codigo_cliente = CONCAT('CLI-SYNC-', LPAD(s.id, 6, '0'))
LEFT JOIN stg_ext_map_clientes m ON m.src_id = s.id
WHERE s.id IN (12, 23, 30, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 65, 66, 67, 68, 69, 70)
  AND m.src_id IS NULL;

-- Mapear la 2da sede de SSANDIER (id=64) al idcliente de SSANDIER (id=63)
INSERT INTO stg_ext_map_clientes (src_id, mrp_idcliente, es_cabeza, match_tipo, accion)
SELECT
    64,
    m63.mrp_idcliente,
    0,
    'SEDE_SSANDIER',
    'CONSOLIDADA'
FROM stg_ext_map_clientes m63
WHERE m63.src_id = 63
  AND NOT EXISTS (SELECT 1 FROM stg_ext_map_clientes m64 WHERE m64.src_id = 64);

-- -----------------------------------------------------------------------------
-- 4. Insertar datos fiscales (cli_clientes_fiscal) SOLO para los nuevos con RFC único
-- -----------------------------------------------------------------------------
SET @fiscal_inserted = 0;

INSERT INTO cli_clientes_fiscal (
    idcliente,
    rfc,
    curp,
    regimen_fiscal,
    uso_cfdi,
    codigo_postal_fiscal,
    correo_facturacion,
    requiere_factura,
    estado,
    usuarioid
)
SELECT
    m.mrp_idcliente,
    UPPER(REPLACE(REPLACE(TRIM(s.rfc), '-', ''), ' ', '')) AS clean_rfc,
    NULLIF(LEFT(UPPER(TRIM(s.curp)), 18), '') AS curp,
    COALESCE(NULLIF(LEFT(TRIM(srf.c_regimen_fiscal), 10), ''), @sync_default_regimen_sat) AS regimen_sat,
    @sync_default_uso_cfdi,
    CASE
        WHEN CHAR_LENGTH(COALESCE(df1.cp_fiscal, '')) = 5 THEN df1.cp_fiscal
        ELSE '00000'
    END AS cp_fiscal,
    NULLIF(LEFT(TRIM(s.correo), 150), '') AS correo_facturacion,
    1,
    2,
    @sync_usuario_id
FROM stg_ext_map_clientes m
INNER JOIN stg_ext_cli_clientes s ON s.id = m.src_id
LEFT JOIN stg_ext_cli_regimenes_fiscales srf ON srf.id = s.regimen_fiscal_id
LEFT JOIN (
    SELECT
        df.distribuidor_id,
        LEFT(REGEXP_REPLACE(TRIM(COALESCE(df.codigo_postal, '')), '[^0-9]', ''), 5) AS cp_fiscal,
        ROW_NUMBER() OVER (PARTITION BY df.distribuidor_id ORDER BY (df.estado = 2) DESC, df.id ASC) AS rn
    FROM stg_ext_cli_clientes_direcciones_fiscales df
) df1 ON df1.distribuidor_id = s.id AND df1.rn = 1
WHERE m.es_cabeza = 1
  AND s.rfc IS NOT NULL
  AND CHAR_LENGTH(UPPER(REPLACE(REPLACE(TRIM(s.rfc), '-', ''), ' ', ''))) IN (12, 13)
  AND NOT EXISTS (SELECT 1 FROM cli_clientes_fiscal f1 WHERE f1.idcliente = m.mrp_idcliente)
  AND NOT EXISTS (SELECT 1 FROM cli_clientes_fiscal f2 WHERE f2.rfc = UPPER(REPLACE(REPLACE(TRIM(s.rfc), '-', ''), ' ', '')));
SET @fiscal_inserted = ROW_COUNT();

-- -----------------------------------------------------------------------------
-- 5. Insertar sucursales NUEVAS faltantes (SIN modificar ni borrar ninguna existente):
--    5.1 Las 18 sucursales de los 17 Distribuidores NUEVOS (SSANDIER tiene 2)
--    5.2 Las sedes reales que faltaban en los 26 clientes MRP Base (GRUMAR 4, Jiménez 11, etc.),
--        omitiendo las 17 coincidentes / casi iguales para no duplicarlas
-- -----------------------------------------------------------------------------
SET @suc_inserted = 0;

INSERT INTO cli_clientes_sucursales (
    idcliente,
    nombre_sucursal,
    telefono,
    correo,
    responsable,
    calle,
    numero_exterior,
    numero_interior,
    colonia,
    codigo_postal,
    municipio,
    estado_republica,
    pais,
    estado,
    usuarioid
)
SELECT
    src.mrp_idcliente,
    src.nombre_sucursal,
    src.telefono,
    src.correo,
    NULL AS responsable,
    COALESCE(NULLIF(LEFT(TRIM(src.calle), 255), ''), '') AS calle,
    COALESCE(NULLIF(LEFT(TRIM(src.numero_ext), 255), ''), 'S/N') AS numero_exterior,
    COALESCE(LEFT(TRIM(src.numero_int), 255), '') AS numero_interior,
    COALESCE(LEFT(TRIM(src.colonia), 255), '') AS colonia,
    COALESCE(NULLIF(LEFT(TRIM(src.codigo_postal), 255), ''), '00000') AS codigo_postal,
    COALESCE(LEFT(TRIM(src.municipio), 255), '') AS municipio,
    COALESCE(LEFT(TRIM(src.estado_republica), 255), '') AS estado_republica,
    COALESCE(NULLIF(LEFT(TRIM(src.pais), 255), ''), 'México') AS pais,
    2 AS estado,
    @sync_usuario_id AS usuarioid
FROM (
    SELECT *
    FROM (
        SELECT
            cand.mrp_idcliente,
            LEFT(TRIM(s.nombre_comercial), 150) AS nombre_sucursal,
            NULLIF(LEFT(TRIM(s.telefono), 30), '') AS telefono,
            NULLIF(LEFT(TRIM(s.correo), 150), '') AS correo,
            dir.calle,
            dir.numero_ext,
            dir.numero_int,
            dir.colonia,
            dir.codigo_postal,
            dir.municipio,
            dir.estado_republica,
            dir.pais,
            ROW_NUMBER() OVER (
                PARTITION BY cand.mrp_idcliente, UPPER(TRIM(s.nombre_comercial))
                ORDER BY (s.estado = 2) DESC, (dir.calle IS NOT NULL) DESC, s.id ASC
            ) AS rn_suc
        FROM (
            -- A) Los 23 clientes nuevos (incluyendo 64 SSANDIER)
            SELECT m.src_id, m.mrp_idcliente
            FROM stg_ext_map_clientes m

            UNION

            -- B) Agencias de clientes MRP Base (empatados por RFC, excluyendo id=51 que va como cliente nuevo)
            SELECT s2.id AS src_id, f.idcliente AS mrp_idcliente
            FROM stg_ext_cli_clientes s2
            INNER JOIN cli_clientes_fiscal f
                ON f.rfc = UPPER(REPLACE(REPLACE(TRIM(s2.rfc), '-', ''), ' ', ''))
            INNER JOIN cli_clientes c_base
                ON c_base.idcliente = f.idcliente
               AND c_base.estado = 2
               AND c_base.codigo_cliente NOT LIKE 'CLI-SYNC-%'
            WHERE s2.estado = 2
              AND s2.id <> 51
              AND s2.rfc IS NOT NULL
              AND CHAR_LENGTH(UPPER(REPLACE(REPLACE(TRIM(s2.rfc), '-', ''), ' ', ''))) IN (12, 13)
        ) cand
        INNER JOIN stg_ext_cli_clientes s ON s.id = cand.src_id
        LEFT JOIN (
            SELECT
                d.distribuidor_id,
                d.calle,
                d.numero_ext,
                d.numero_int,
                d.colonia,
                d.codigo_postal,
                mun.nombre AS municipio,
                est.nombre AS estado_republica,
                COALESCE(p.nombre, p2.nombre, 'México') AS pais,
                ROW_NUMBER() OVER (PARTITION BY d.distribuidor_id ORDER BY (d.estado = 2) DESC, d.id ASC) AS rn
            FROM stg_ext_cli_clientes_direcciones d
            LEFT JOIN stg_ext_cli_municipios mun ON mun.id = d.municipio_id
            LEFT JOIN stg_ext_cli_estados est ON est.id = d.estado_id
            LEFT JOIN stg_ext_cli_paises p ON p.id = d.pais_id
            LEFT JOIN stg_ext_cli_paises p2 ON p2.id = est.pais_id
        ) dir ON dir.distribuidor_id = s.id AND dir.rn = 1
        WHERE s.nombre_comercial IS NOT NULL
          AND TRIM(s.nombre_comercial) <> ''
    ) dedup
    WHERE dedup.rn_suc = 1
) src
WHERE NOT EXISTS (
    SELECT 1
    FROM cli_clientes_sucursales ex
    WHERE ex.idcliente = src.mrp_idcliente
      AND (
          UPPER(TRIM(ex.nombre_sucursal)) = UPPER(TRIM(src.nombre_sucursal))
          OR (UPPER(TRIM(ex.nombre_sucursal)) = 'FOTON PUEBLA (ASTURCAR)' AND UPPER(TRIM(src.nombre_sucursal)) = 'FOTON ASTURCAR')
          OR (UPPER(TRIM(ex.nombre_sucursal)) = 'FOTON TOLUCA'            AND UPPER(TRIM(src.nombre_sucursal)) = 'FOTON ASTROCAMIONES')
          OR (UPPER(TRIM(ex.nombre_sucursal)) = 'FOTON SAN LUIS'          AND UPPER(TRIM(src.nombre_sucursal)) = 'FOTON SAN LUIS POTOSI')
          OR (UPPER(TRIM(ex.nombre_sucursal)) = 'FOTON MONTERREY'         AND UPPER(TRIM(src.nombre_sucursal)) = 'FOTON ESCOBEDO')
          OR (UPPER(TRIM(ex.nombre_sucursal)) = 'FOTON TEPIC'             AND UPPER(TRIM(src.nombre_sucursal)) = 'FOTON NAYARIT')
      )
);
SET @suc_inserted = ROW_COUNT();

-- -----------------------------------------------------------------------------
-- 6. Insertar direcciones (cli_direcciones) SOLO para los 23 clientes NUEVOS
-- -----------------------------------------------------------------------------
SET @dir_inserted = 0;

INSERT INTO cli_direcciones (
    idcliente,
    tipo_direccion,
    calle,
    numero_exterior,
    numero_interior,
    colonia,
    codigo_postal,
    municipio,
    estado_republica,
    pais,
    referencias,
    estado,
    usuarioid
)
SELECT
    src.mrp_idcliente,
    src.tipo_direccion,
    src.calle,
    src.numero_exterior,
    src.numero_interior,
    src.colonia,
    src.codigo_postal,
    src.municipio,
    src.estado_republica,
    src.pais,
    NULL AS referencias,
    2 AS estado,
    @sync_usuario_id AS usuarioid
FROM (
    -- Dirección FISCAL de la cabeza
    SELECT
        m.mrp_idcliente,
        'FISCAL' AS tipo_direccion,
        LEFT(TRIM(df.calle), 180) AS calle,
        COALESCE(NULLIF(LEFT(TRIM(df.numero_ext), 30), ''), 'S/N') AS numero_exterior,
        NULLIF(LEFT(TRIM(df.numero_int), 30), '') AS numero_interior,
        COALESCE(LEFT(TRIM(df.colonia), 150), '') AS colonia,
        CASE
            WHEN CHAR_LENGTH(REGEXP_REPLACE(TRIM(COALESCE(df.codigo_postal, '')), '[^0-9]', '')) >= 5
            THEN LEFT(REGEXP_REPLACE(TRIM(df.codigo_postal), '[^0-9]', ''), 5)
            ELSE '00000'
        END AS codigo_postal,
        COALESCE(LEFT(TRIM(mun.nombre), 150), '') AS municipio,
        COALESCE(LEFT(TRIM(est.nombre), 120), '') AS estado_republica,
        COALESCE(LEFT(TRIM(COALESCE(p.nombre, p2.nombre)), 100), 'México') AS pais
    FROM stg_ext_cli_clientes_direcciones_fiscales df
    INNER JOIN stg_ext_map_clientes m ON m.src_id = df.distribuidor_id AND m.es_cabeza = 1
    LEFT JOIN stg_ext_cli_municipios mun ON mun.id = df.municipio_id
    LEFT JOIN stg_ext_cli_estados est ON est.id = df.estado_id
    LEFT JOIN stg_ext_cli_paises p ON p.id = df.pais_id
    LEFT JOIN stg_ext_cli_paises p2 ON p2.id = est.pais_id
    WHERE df.calle IS NOT NULL AND TRIM(df.calle) <> ''

    UNION ALL

    -- Dirección operativa de la cabeza
    SELECT
        m.mrp_idcliente,
        CASE UPPER(TRIM(COALESCE(d.tipo, '')))
            WHEN 'COMERCIAL' THEN 'COMERCIAL'
            WHEN 'TALLER' THEN 'TALLER'
            ELSE 'ENTREGA'
        END AS tipo_direccion,
        LEFT(TRIM(d.calle), 180) AS calle,
        COALESCE(NULLIF(LEFT(TRIM(d.numero_ext), 30), ''), 'S/N') AS numero_exterior,
        NULLIF(LEFT(TRIM(d.numero_int), 30), '') AS numero_interior,
        COALESCE(LEFT(TRIM(d.colonia), 150), '') AS colonia,
        CASE
            WHEN CHAR_LENGTH(REGEXP_REPLACE(TRIM(COALESCE(d.codigo_postal, '')), '[^0-9]', '')) >= 5
            THEN LEFT(REGEXP_REPLACE(TRIM(d.codigo_postal), '[^0-9]', ''), 5)
            ELSE '00000'
        END AS codigo_postal,
        COALESCE(LEFT(TRIM(mun.nombre), 150), '') AS municipio,
        COALESCE(LEFT(TRIM(est.nombre), 120), '') AS estado_republica,
        COALESCE(LEFT(TRIM(COALESCE(p.nombre, p2.nombre)), 100), 'México') AS pais
    FROM stg_ext_cli_clientes_direcciones d
    INNER JOIN stg_ext_map_clientes m ON m.src_id = d.distribuidor_id AND m.es_cabeza = 1
    LEFT JOIN stg_ext_cli_municipios mun ON mun.id = d.municipio_id
    LEFT JOIN stg_ext_cli_estados est ON est.id = d.estado_id
    LEFT JOIN stg_ext_cli_paises p ON p.id = d.pais_id
    LEFT JOIN stg_ext_cli_paises p2 ON p2.id = est.pais_id
    WHERE d.calle IS NOT NULL AND TRIM(d.calle) <> ''
) src
WHERE NOT EXISTS (
    SELECT 1
    FROM cli_direcciones ex
    WHERE ex.idcliente = src.mrp_idcliente
      AND ex.estado <> 0
      AND (
          (src.tipo_direccion = 'FISCAL' AND ex.tipo_direccion = 'FISCAL')
          OR (
              ex.tipo_direccion = src.tipo_direccion
              AND UPPER(TRIM(ex.calle)) = UPPER(TRIM(src.calle))
              AND ex.codigo_postal = src.codigo_postal
          )
      )
);
SET @dir_inserted = ROW_COUNT();

-- -----------------------------------------------------------------------------
-- 7. Insertar contactos SOLO para los 23 clientes NUEVOS (si tuvieran en origen)
-- -----------------------------------------------------------------------------
SET @cont_inserted = 0;

INSERT INTO cli_clientes_contactos (
    idcliente,
    nombre,
    correo,
    telefono,
    tipo,
    notificar,
    usuarioid,
    puesto,
    estado
)
SELECT
    m.mrp_idcliente,
    LEFT(TRIM(ct.nombre), 150) AS nombre,
    NULLIF(LEFT(TRIM(ct.correo), 150), '') AS correo,
    NULLIF(LEFT(TRIM(CONCAT_WS(' ext ', NULLIF(TRIM(ct.telefono), ''), NULLIF(TRIM(ct.extension), ''))), 30), '') AS telefono,
    CASE
        WHEN UPPER(COALESCE(dep.nombre, '')) LIKE '%FACTUR%' THEN 'FACTURACION'
        WHEN UPPER(COALESCE(dep.nombre, '')) LIKE '%ADMIN%' OR UPPER(COALESCE(dep.nombre, '')) LIKE '%DIRECC%' THEN 'ADMINISTRATIVO'
        ELSE 'COMERCIAL'
    END AS tipo,
    0 AS notificar,
    @sync_usuario_id AS usuarioid,
    NULLIF(LEFT(TRIM(pu.nombre), 100), '') AS puesto,
    2 AS estado
FROM stg_ext_cli_contactos ct
INNER JOIN stg_ext_map_clientes m ON m.src_id = ct.distribuidor_id
LEFT JOIN stg_ext_cli_puestos pu ON pu.id = ct.puesto_id
LEFT JOIN stg_ext_cli_departamentos dep ON dep.id = pu.departamento_id
WHERE @sync_include_contactos = 1
  AND ct.estado = 2
  AND ct.nombre IS NOT NULL AND TRIM(ct.nombre) <> ''
  AND NOT EXISTS (
      SELECT 1
      FROM cli_clientes_contactos ex
      WHERE ex.idcliente = m.mrp_idcliente
        AND ex.estado <> 0
        AND UPPER(TRIM(ex.nombre)) = UPPER(TRIM(ct.nombre))
        AND COALESCE(LOWER(TRIM(ex.correo)), '') = COALESCE(LOWER(TRIM(ct.correo)), '')
  );
SET @cont_inserted = ROW_COUNT();

-- -----------------------------------------------------------------------------
-- 8. Seguridad en Logística: desactivar SOLO distribuidores huérfanos sin cliente
--    y con 0 pedidos / 0 envíos (los de prueba CON pedidos se mantienen intactos)
-- -----------------------------------------------------------------------------
UPDATE lgs_cat_distribuidores d
LEFT JOIN cli_clientes c ON c.idcliente = d.id_cliente
SET d.activo = 0
WHERE d.activo = 1
  AND d.id_cliente IS NOT NULL
  AND c.idcliente IS NULL
  AND NOT EXISTS (SELECT 1 FROM ped_pedidos p WHERE p.idcliente = d.id_cliente)
  AND NOT EXISTS (
      SELECT 1
      FROM lgs_cat_ubicaciones u
      INNER JOIN lgs_envios_nodos n ON n.id_ubicacion = u.id_ubicacion
      WHERE u.id_distribuidor = d.id_distribuidor
  );

-- -----------------------------------------------------------------------------
-- 9. Reporte final de verificación (incluye integridad de pedidos existentes)
-- -----------------------------------------------------------------------------
SELECT '=== RESUMEN CRUCE DEFINITIVO ===' AS reporte;
SELECT
    'cli_clientes (23 nuevos)' AS tabla,
    SUM(es_cabeza = 1 AND accion = 'INSERT') AS insertados,
    SUM(es_cabeza = 1 AND accion = 'EXISTENTE') AS ya_existian
FROM stg_ext_map_clientes
UNION ALL SELECT 'cli_clientes_fiscal', @fiscal_inserted, 0
UNION ALL SELECT 'cli_clientes_sucursales', @suc_inserted, 0
UNION ALL SELECT 'cli_direcciones', @dir_inserted, 0
UNION ALL SELECT 'cli_clientes_contactos', @cont_inserted, 0;

SELECT '=== VERIFICACION DE PEDIDOS EXISTENTES ===' AS reporte;
SELECT
    COUNT(*) AS total_pedidos,
    SUM(c.idcliente IS NOT NULL) AS pedidos_vinculados_ok,
    SUM(c.idcliente IS NULL) AS pedidos_huerfanos
FROM ped_pedidos p
LEFT JOIN cli_clientes c ON c.idcliente = p.idcliente;

SELECT '=== ALERTAS ===' AS reporte;
SELECT entidad, src_id, severidad, detalle FROM stg_ext_alertas ORDER BY id;
