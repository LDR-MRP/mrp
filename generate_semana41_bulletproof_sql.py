import csv
import re

csv_path = 'semana 41.csv'

with open(csv_path, mode='r', encoding='utf-8-sig') as f:
    reader = csv.DictReader(f)
    raw_rows = list(reader)

def parse_date(date_str):
    if not date_str or date_str == 'N/A':
        return None
    try:
        parts = date_str.strip().split('/')
        if len(parts) == 3:
            m, d, y = int(parts[0]), int(parts[1]), int(parts[2])
            return f"{y:04d}-{m:02d}-{d:02d} 00:00:00"
    except Exception:
        pass
    return None

def clean_str(s):
    if not s:
        return ''
    s = s.replace('\n', ' ').replace('\r', ' ')
    s = re.sub(r'\s+', ' ', s)
    return s.strip().replace("'", "''")

def get_estado_proceso(status):
    s = (status or '').lower().strip()
    if 'entregado' in s:
        return 3
    elif 'transito' in s or 'tránsito' in s:
        return 2
    else:
        return 1

def get_estatus_unidad(status):
    s = (status or '').lower().strip()
    if 'entregado' in s:
        return 'entregado'
    elif 'transito' in s or 'tránsito' in s:
        return 'en_transito'
    else:
        return 'disponible'

unidades_envios_rows = []
bandeja_select_rows = []
seen_in_batch = set()

for idx, r in enumerate(raw_rows, 1):
    vin = r['VIN'].strip()
    if not vin:
        continue
    if vin in seen_in_batch:
        continue
    seen_in_batch.add(vin)
    
    modelo = clean_str(r.get('MODELO', ''))
    origen = clean_str(r.get('ORIGEN', '')) or 'PLANTA'
    
    distribuidor = clean_str(r.get('DISTRIBUIDOR', ''))
    destino_raw = clean_str(r.get('DESTINO', ''))

    # If distributor is generic or empty, use destino
    if not distribuidor or distribuidor in ['SIN FACTURA', 'GENERADOR']:
        destino_final = destino_raw or distribuidor or 'Sin Asignar'
    elif not destino_raw or destino_raw == 'Pendiente':
        destino_final = distribuidor
    elif distribuidor.lower() in destino_raw.lower():
        destino_final = destino_raw
    elif destino_raw.lower() in distribuidor.lower():
        destino_final = distribuidor
    else:
        # Include both so strpos matches either client or location!
        destino_final = f"{distribuidor} - {destino_raw}"

    status_raw = r.get('ESTATUS', 'Programado')
    estatus = get_estatus_unidad(status_raw)
    id_estado_proceso = get_estado_proceso(status_raw)
    fecha_programada = parse_date(r.get('FECHA PROGRAMADA DE SALIDA', ''))
    
    fecha_salida_sql = f"'{fecha_programada}'" if fecha_programada else "NULL"
    fecha_llegada_sql = f"'{fecha_programada.replace('00:00:00', '18:00:00')}'" if (fecha_programada and id_estado_proceso == 3) else "NULL"
    
    num_serie = "NULL"
    if len(vin) >= 6 and vin[-6:].isdigit():
        num_serie = f"'{vin[-6:]}'"

    unidades_envios_rows.append(
        f"(NULL, '{vin}', {num_serie}, '{modelo}', '{origen}', '{destino_final}', '{estatus}', NOW())"
    )

    bandeja_select_rows.append(
        f"SELECT '{vin}' AS vin, '{destino_final}' AS destino, {id_estado_proceso} AS id_estado, {fecha_salida_sql} AS f_salida, {fecha_llegada_sql} AS f_llegada"
    )

sql_content = f"""-- ==============================================================================
-- INSERCIÓN DE UNIDADES DE SEMANA 41 (COMPATIBLE CON CLIENTES / DESTINOS DE ENVÍOS)
-- Base de datos: u546825723_dbmrp
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Insertar en lgs_unidades_envios con NULL en id_unidad (AUTO_INCREMENT)
INSERT INTO `lgs_unidades_envios` (`id_unidad`, `vin`, `num_serie`, `modelo`, `origen`, `destino`, `estatus`, `created_at`) VALUES
{',\n'.join(unidades_envios_rows)}
ON DUPLICATE KEY UPDATE 
    `modelo` = VALUES(`modelo`),
    `origen` = VALUES(`origen`),
    `destino` = VALUES(`destino`),
    `estatus` = VALUES(`estatus`);

-- 2. Insertar en lgs_unidades (Bandeja de Logística) vinculando dinámicamente por VIN
-- Se utiliza NULL en id_lgs_unidad para AUTO_INCREMENT y u.id_unidad asignado por MySQL
INSERT INTO `lgs_unidades` (`id_lgs_unidad`, `id_unidad`, `id_motivo`, `id_destino`, `destino_descripcion`, `id_estado_proceso`, `fecha_salida`, `fecha_llegada`, `created_by`, `updated_by`, `created_at`, `updated_at`)
SELECT 
    NULL,
    u.id_unidad,
    1,
    NULL,
    tmp.destino,
    tmp.id_estado,
    tmp.f_salida,
    tmp.f_llegada,
    1,
    NULL,
    NOW(),
    NULL
FROM (
    {'\n    UNION ALL\n    '.join(bandeja_select_rows)}
) tmp
INNER JOIN `lgs_unidades_envios` u ON u.vin = tmp.vin
WHERE NOT EXISTS (
    SELECT 1 FROM `lgs_unidades` lu WHERE lu.id_unidad = u.id_unidad
);

SET FOREIGN_KEY_CHECKS = 1;
"""

with open('/home/christianguarneros/.gemini/antigravity-ide/brain/d130e921-4cc3-4686-8e9f-42c679be7a47/insert_unidades_semana_41_autoincrement.sql', 'w', encoding='utf-8') as f:
    f.write(sql_content)

print(f"Generated successfully with {len(unidades_envios_rows)} rows!")
