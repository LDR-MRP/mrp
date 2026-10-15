import csv
import re

csv_path = 'semana 41.csv'

with open(csv_path, mode='r', encoding='utf-8-sig') as f:
    reader = csv.DictReader(f)
    raw_rows = list(reader)

with open('u546825723_dbmrp (13).sql', 'r', encoding='utf-8', errors='ignore') as f:
    dump = f.read()

m = re.search(r'INSERT INTO \`lgs_unidades_envios\`.*?;', dump, re.DOTALL)
existing_vins = set()
if m:
    for match in re.findall(r"\(\d+,\s*'([^']+)'", m.group(0)):
        existing_vins.add(match)

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
unidades_bandeja_rows = []

current_id_unidad = 122
current_id_lgs = 128
seen_in_batch = set()

for idx, r in enumerate(raw_rows, 1):
    vin = r['VIN'].strip()
    if not vin:
        continue
    if vin in seen_in_batch:
        continue
    seen_in_batch.add(vin)

    is_already_existing = vin in existing_vins
    
    modelo = clean_str(r.get('MODELO', ''))
    origen = clean_str(r.get('ORIGEN', '')) or 'PLANTA'
    destino = clean_str(r.get('DESTINO', '')) or clean_str(r.get('DISTRIBUIDOR', '')) or 'Sin Asignar'
    status_raw = r.get('ESTATUS', 'Programado')
    estatus = get_estatus_unidad(status_raw)
    id_estado_proceso = get_estado_proceso(status_raw)
    fecha_programada = parse_date(r.get('FECHA PROGRAMADA DE SALIDA', ''))
    
    fecha_salida_sql = f"'{fecha_programada}'" if fecha_programada else "NULL"
    fecha_llegada_sql = f"'{fecha_programada.replace('00:00:00', '18:00:00')}'" if (fecha_programada and id_estado_proceso == 3) else "NULL"
    
    num_serie = "NULL"
    if len(vin) >= 6 and vin[-6:].isdigit():
        num_serie = f"'{vin[-6:]}'"

    if not is_already_existing:
        unidades_envios_rows.append(
            f"({current_id_unidad}, '{vin}', {num_serie}, '{modelo}', '{origen}', '{destino}', '{estatus}', '2026-10-08 10:00:00')"
        )
        unidades_bandeja_rows.append(
            f"({current_id_lgs}, {current_id_unidad}, 1, NULL, '{destino}', {id_estado_proceso}, {fecha_salida_sql}, {fecha_llegada_sql}, 1, NULL, '2026-10-08 10:00:00', NULL)"
        )
        current_id_unidad += 1
        current_id_lgs += 1

sql_content = f"""-- ==============================================================================
-- INSERCIÓN CONSECUTIVA DE UNIDADES DE SEMANA 41 EN EL MÓDULO DE LOGÍSTICA
-- Tabla 1: `lgs_unidades_envios` (Catálogo maestro de unidades disponibles, desde ID 122)
-- Tabla 2: `lgs_unidades` (Bandeja operativa de logística, desde ID 128)
-- Fecha: 2026-10-08
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Insertar en lgs_unidades_envios (Consecutivo 122 al {current_id_unidad - 1})
INSERT INTO `lgs_unidades_envios` (`id_unidad`, `vin`, `num_serie`, `modelo`, `origen`, `destino`, `estatus`, `created_at`) VALUES
{',\n'.join(unidades_envios_rows)}
ON DUPLICATE KEY UPDATE 
    `modelo` = VALUES(`modelo`),
    `origen` = VALUES(`origen`),
    `destino` = VALUES(`destino`),
    `estatus` = VALUES(`estatus`);

-- 2. Insertar en lgs_unidades (Bandeja de Logística, Consecutivo 128 al {current_id_lgs - 1})
INSERT INTO `lgs_unidades` (`id_lgs_unidad`, `id_unidad`, `id_motivo`, `id_destino`, `destino_descripcion`, `id_estado_proceso`, `fecha_salida`, `fecha_llegada`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
{',\n'.join(unidades_bandeja_rows)}
ON DUPLICATE KEY UPDATE 
    `destino_descripcion` = VALUES(`destino_descripcion`),
    `id_estado_proceso` = VALUES(`id_estado_proceso`),
    `fecha_salida` = VALUES(`fecha_salida`),
    `fecha_llegada` = VALUES(`fecha_llegada`);

-- 3. Actualizar auto_increment para futuros registros
ALTER TABLE `lgs_unidades_envios` AUTO_INCREMENT = {current_id_unidad};
ALTER TABLE `lgs_unidades` AUTO_INCREMENT = {current_id_lgs};

SET FOREIGN_KEY_CHECKS = 1;
"""

with open('/home/christianguarneros/.gemini/antigravity-ide/brain/d130e921-4cc3-4686-8e9f-42c679be7a47/insert_unidades_semana_41.sql', 'w', encoding='utf-8') as f:
    f.write(sql_content)

print(f"Generated SQL successfully with {len(unidades_envios_rows)} rows!")
