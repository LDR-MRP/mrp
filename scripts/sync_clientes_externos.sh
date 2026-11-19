#!/usr/bin/env bash
# ==============================================================================
# sync_clientes_externos.sh
# Sincronización idempotente de clientes, datos fiscales, sucursales,
# direcciones y contactos desde la BD de la Plataforma de Clientes hacia MRP.
#
# Uso:
#   ./scripts/sync_clientes_externos.sh --env scripts/.env.sync.local --dry-run
#   ./scripts/sync_clientes_externos.sh --env scripts/.env.sync.local --apply
#   ./scripts/sync_clientes_externos.sh --env scripts/.env.sync.prod  --dry-run
#   ./scripts/sync_clientes_externos.sh --env scripts/.env.sync.prod  --apply
# ==============================================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
ETL_SQL="${SCRIPT_DIR}/sync_clientes_etl.sql"
ENV_FILE="${SCRIPT_DIR}/.env.sync"
MODE="dry-run"
EMIT_SQL_FILE=""

usage() {
    cat <<'EOF'
Uso: sync_clientes_externos.sh [--env <archivo>] [--dry-run | --apply | --emit-sql <archivo.sql>]

Opciones:
  --env <ruta>           Archivo de configuración (por defecto: scripts/.env.sync)
  --dry-run              Ejecuta todo el ETL dentro de una transacción con ROLLBACK (por defecto)
  --apply                Genera respaldo .sql.gz previo y aplica los cambios con COMMIT
  --emit-sql <ruta.sql>  Genera un archivo .sql autocontenido listo para importar en phpMyAdmin (MariaDB/MySQL)
  -h, --help             Muestra esta ayuda
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --env)
            ENV_FILE="${2:-}"
            shift 2
            ;;
        --dry-run)
            MODE="dry-run"
            shift
            ;;
        --apply)
            MODE="apply"
            shift
            ;;
        --emit-sql)
            MODE="emit-sql"
            EMIT_SQL_FILE="${2:-}"
            if [[ -z "${EMIT_SQL_FILE}" ]]; then
                echo "[ERROR] --emit-sql requiere una ruta de archivo de salida." >&2
                exit 1
            fi
            shift 2
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            echo "[ERROR] Opción desconocida: $1" >&2
            usage
            exit 1
            ;;
    esac
done

if [[ ! -f "${ENV_FILE}" ]]; then
    echo "[ERROR] No se encontró el archivo de configuración: ${ENV_FILE}" >&2
    echo "        Copia scripts/.env.sync.example como ${ENV_FILE} y ajusta las credenciales." >&2
    exit 1
fi

if [[ ! -f "${ETL_SQL}" ]]; then
    echo "[ERROR] No se encontró el archivo SQL del ETL: ${ETL_SQL}" >&2
    exit 1
fi

# shellcheck disable=SC1090
source "${ENV_FILE}"

# Defaults seguros
SRC_MODE="${SRC_MODE:-mysql}"
DST_MODE="${DST_MODE:-mysql}"
SRC_PORT="${SRC_PORT:-3306}"
DST_PORT="${DST_PORT:-3306}"
SRC_EXTRA_OPTS="${SRC_EXTRA_OPTS:-}"
DST_EXTRA_OPTS="${DST_EXTRA_OPTS:-}"
SYNC_USUARIO_ID="${SYNC_USUARIO_ID:-1}"
SYNC_DEFAULT_TIPO_CLIENTE_ID="${SYNC_DEFAULT_TIPO_CLIENTE_ID:-1}"
SYNC_DEFAULT_REGIMEN_ID="${SYNC_DEFAULT_REGIMEN_ID:-1}"
SYNC_DEFAULT_REGIMEN_SAT="${SYNC_DEFAULT_REGIMEN_SAT:-601}"
SYNC_DEFAULT_USO_CFDI="${SYNC_DEFAULT_USO_CFDI:-G03}"
SYNC_INCLUDE_CONTACTOS="${SYNC_INCLUDE_CONTACTOS:-1}"
SYNC_ONLY_ACTIVE="${SYNC_ONLY_ACTIVE:-0}"
BACKUP_DIR="${BACKUP_DIR:-backups/sync_clientes}"

# Validar variables numéricas para evitar inyección en SET @...
for num_var in SYNC_USUARIO_ID SYNC_DEFAULT_TIPO_CLIENTE_ID SYNC_DEFAULT_REGIMEN_ID SYNC_INCLUDE_CONTACTOS SYNC_ONLY_ACTIVE; do
    if ! [[ "${!num_var}" =~ ^[0-9]+$ ]]; then
        echo "[ERROR] ${num_var} debe ser un entero numérico (valor actual: '${!num_var}')." >&2
        exit 1
    fi
done

# Validar códigos SAT / CFDI alfanuméricos
if ! [[ "${SYNC_DEFAULT_REGIMEN_SAT}" =~ ^[A-Za-z0-9]{1,10}$ ]]; then
    echo "[ERROR] SYNC_DEFAULT_REGIMEN_SAT inválido: '${SYNC_DEFAULT_REGIMEN_SAT}'." >&2
    exit 1
fi
if ! [[ "${SYNC_DEFAULT_USO_CFDI}" =~ ^[A-Za-z0-9]{1,10}$ ]]; then
    echo "[ERROR] SYNC_DEFAULT_USO_CFDI inválido: '${SYNC_DEFAULT_USO_CFDI}'." >&2
    exit 1
fi

TMP_DIR="$(mktemp -d)"
STAGING_CREATED=0

# Helpers de ejecución MySQL (sin exponer contraseñas en la línea de comandos)
run_src_mysql() {
    if [[ "${SRC_MODE}" == "docker" ]]; then
        docker exec -i -e MYSQL_PWD="${SRC_PASS}" "${SRC_DOCKER_CONTAINER}" \
            mysql -u "${SRC_USER}" --default-character-set=utf8mb4 ${SRC_EXTRA_OPTS} "${SRC_DB}" "$@"
    else
        MYSQL_PWD="${SRC_PASS}" mysql -h "${SRC_HOST}" -P "${SRC_PORT}" -u "${SRC_USER}" \
            --default-character-set=utf8mb4 ${SRC_EXTRA_OPTS} "${SRC_DB}" "$@"
    fi
}

run_src_mysqldump() {
    if [[ "${SRC_MODE}" == "docker" ]]; then
        docker exec -i -e MYSQL_PWD="${SRC_PASS}" "${SRC_DOCKER_CONTAINER}" \
            mysqldump -u "${SRC_USER}" --default-character-set=utf8mb4 ${SRC_EXTRA_OPTS} \
            --single-transaction --quick --skip-triggers --no-create-info --complete-insert \
            --skip-add-locks --skip-disable-keys --set-gtid-purged=OFF --no-tablespaces "${SRC_DB}" "$@"
    else
        MYSQL_PWD="${SRC_PASS}" mysqldump -h "${SRC_HOST}" -P "${SRC_PORT}" -u "${SRC_USER}" \
            --default-character-set=utf8mb4 ${SRC_EXTRA_OPTS} \
            --single-transaction --quick --skip-triggers --no-create-info --complete-insert \
            --skip-add-locks --skip-disable-keys --set-gtid-purged=OFF --no-tablespaces "${SRC_DB}" "$@"
    fi
}

run_dst_mysql() {
    if [[ "${DST_MODE}" == "docker" ]]; then
        docker exec -i -e MYSQL_PWD="${DST_PASS}" "${DST_DOCKER_CONTAINER}" \
            mysql -u "${DST_USER}" --default-character-set=utf8mb4 ${DST_EXTRA_OPTS} "${DST_DB}" "$@"
    else
        MYSQL_PWD="${DST_PASS}" mysql -h "${DST_HOST}" -P "${DST_PORT}" -u "${DST_USER}" \
            --default-character-set=utf8mb4 ${DST_EXTRA_OPTS} "${DST_DB}" "$@"
    fi
}

run_dst_mysqldump() {
    if [[ "${DST_MODE}" == "docker" ]]; then
        docker exec -i -e MYSQL_PWD="${DST_PASS}" "${DST_DOCKER_CONTAINER}" \
            mysqldump -u "${DST_USER}" --default-character-set=utf8mb4 ${DST_EXTRA_OPTS} \
            --single-transaction --quick --set-gtid-purged=OFF --no-tablespaces "${DST_DB}" "$@"
    else
        MYSQL_PWD="${DST_PASS}" mysqldump -h "${DST_HOST}" -P "${DST_PORT}" -u "${DST_USER}" \
            --default-character-set=utf8mb4 ${DST_EXTRA_OPTS} \
            --single-transaction --quick --set-gtid-purged=OFF --no-tablespaces "${DST_DB}" "$@"
    fi
}

emit_drop_staging_sql() {
    cat <<'SQL'
DROP TABLE IF EXISTS
    stg_ext_alertas,
    stg_ext_map_clientes,
    stg_ext_cli_contactos,
    stg_ext_cli_puestos,
    stg_ext_cli_departamentos,
    stg_ext_cli_clientes_direcciones,
    stg_ext_cli_clientes_direcciones_fiscales,
    stg_ext_cli_municipios,
    stg_ext_cli_estados,
    stg_ext_cli_paises,
    stg_ext_cli_regimenes_fiscales,
    stg_ext_cli_tipos_clientes,
    stg_ext_cli_grupos,
    stg_ext_cli_clientes;
SQL
}

emit_ensure_grupo_col_sql() {
    cat <<'SQL'
SET @col_grupo_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'cli_clientes'
      AND column_name = 'grupo_comercial'
);
SET @sql_add_grupo := IF(
    @col_grupo_exists = 0,
    'ALTER TABLE cli_clientes ADD COLUMN grupo_comercial VARCHAR(120) COLLATE utf8mb4_general_ci DEFAULT NULL AFTER nombre_comercial',
    'SELECT 1'
);
PREPARE stmt_add_grupo FROM @sql_add_grupo;
EXECUTE stmt_add_grupo;
DEALLOCATE PREPARE stmt_add_grupo;
SQL
}

emit_staging_ddl() {
    cat <<'SQL'
CREATE TABLE stg_ext_cli_grupos (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    codigo VARCHAR(255) NULL,
    nombre VARCHAR(255) NULL,
    descripcion TEXT NULL,
    fecha_registro DATETIME NULL,
    estado TINYINT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE stg_ext_cli_clientes (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    grupo_id BIGINT UNSIGNED NULL,
    matriz_id BIGINT UNSIGNED NULL,
    tipo_cliente_id BIGINT UNSIGNED NULL,
    regimen_fiscal_id BIGINT UNSIGNED NULL,
    tipo_persona VARCHAR(50) NULL,
    nombre_fisica VARCHAR(255) NULL,
    apellido_paterno VARCHAR(255) NULL,
    apellido_materno VARCHAR(255) NULL,
    fecha_nacimiento DATE NULL,
    correo VARCHAR(255) NULL,
    curp VARCHAR(30) NULL,
    razon_social VARCHAR(255) NULL,
    representante_legal VARCHAR(255) NULL,
    domicilio_fiscal VARCHAR(500) NULL,
    rfc VARCHAR(30) NULL,
    nombre_comercial VARCHAR(255) NULL,
    repve VARCHAR(255) NULL,
    plaza VARCHAR(255) NULL,
    clasificacion VARCHAR(255) NULL,
    estatus VARCHAR(255) NULL,
    tipo_negocio VARCHAR(255) NULL,
    telefono VARCHAR(50) NULL,
    telefono_alt VARCHAR(50) NULL,
    no_licitacion VARCHAR(255) NULL,
    no_contrato VARCHAR(255) NULL,
    fecha_registro DATETIME NULL,
    estado TINYINT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE stg_ext_cli_clientes_direcciones (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    distribuidor_id BIGINT UNSIGNED NULL,
    tipo VARCHAR(50) NULL,
    calle VARCHAR(255) NULL,
    numero_ext VARCHAR(50) NULL,
    numero_int VARCHAR(50) NULL,
    colonia VARCHAR(255) NULL,
    codigo_postal VARCHAR(20) NULL,
    pais_id BIGINT UNSIGNED NULL,
    estado_id BIGINT UNSIGNED NULL,
    municipio_id BIGINT UNSIGNED NULL,
    latitud DECIMAL(10,7) NULL,
    longitud DECIMAL(10,7) NULL,
    fecha_registro DATETIME NULL,
    estado TINYINT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE stg_ext_cli_clientes_direcciones_fiscales (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    distribuidor_id BIGINT UNSIGNED NULL,
    tipo VARCHAR(50) NULL,
    calle VARCHAR(255) NULL,
    numero_ext VARCHAR(50) NULL,
    numero_int VARCHAR(50) NULL,
    colonia VARCHAR(255) NULL,
    codigo_postal VARCHAR(20) NULL,
    pais_id BIGINT UNSIGNED NULL,
    estado_id BIGINT UNSIGNED NULL,
    municipio_id BIGINT UNSIGNED NULL,
    latitud DECIMAL(10,7) NULL,
    longitud DECIMAL(10,7) NULL,
    fecha_registro DATETIME NULL,
    estado TINYINT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE stg_ext_cli_tipos_clientes (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    nombre VARCHAR(255) NULL,
    descripcion TEXT NULL,
    fecha_registro DATETIME NULL,
    estado TINYINT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE stg_ext_cli_regimenes_fiscales (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    c_regimen_fiscal VARCHAR(20) NULL,
    descripcion VARCHAR(255) NULL,
    persona_fisica VARCHAR(255) NULL,
    persona_moral VARCHAR(255) NULL,
    fecha_registro DATETIME NULL,
    estado TINYINT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE stg_ext_cli_paises (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    nombre VARCHAR(255) NULL,
    codigo_iso VARCHAR(10) NULL,
    fecha_registro DATETIME NULL,
    estado TINYINT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE stg_ext_cli_estados (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    pais_id BIGINT UNSIGNED NULL,
    region_id BIGINT UNSIGNED NULL,
    nombre VARCHAR(255) NULL,
    abreviatura VARCHAR(20) NULL,
    fecha_registro DATETIME NULL,
    estado TINYINT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE stg_ext_cli_municipios (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    estado_id BIGINT UNSIGNED NULL,
    nombre VARCHAR(255) NULL,
    fecha_registro DATETIME NULL,
    estado TINYINT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE stg_ext_cli_contactos (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    distribuidor_id BIGINT UNSIGNED NULL,
    puesto_id BIGINT UNSIGNED NULL,
    nombre VARCHAR(255) NULL,
    correo VARCHAR(255) NULL,
    extension VARCHAR(30) NULL,
    telefono VARCHAR(30) NULL,
    estatus VARCHAR(50) NULL,
    fecha_registro DATETIME NULL,
    estado TINYINT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE stg_ext_cli_puestos (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    departamento_id BIGINT UNSIGNED NULL,
    nombre VARCHAR(255) NULL,
    descripcion TEXT NULL,
    fecha_registro DATETIME NULL,
    estado TINYINT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE stg_ext_cli_departamentos (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    nombre VARCHAR(255) NULL,
    descripcion TEXT NULL,
    fecha_registro DATETIME NULL,
    estado TINYINT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE stg_ext_map_clientes (
    src_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    mrp_idcliente BIGINT NOT NULL,
    es_cabeza TINYINT(1) NOT NULL DEFAULT 1,
    match_tipo VARCHAR(30) NOT NULL,
    accion VARCHAR(20) NOT NULL,
    KEY idx_mrp_idcliente (mrp_idcliente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE stg_ext_alertas (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    entidad VARCHAR(60) NOT NULL,
    src_id BIGINT UNSIGNED NULL,
    severidad VARCHAR(10) NOT NULL,
    detalle VARCHAR(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SQL
}

drop_staging_tables() {
    emit_drop_staging_sql | run_dst_mysql >/dev/null 2>&1 || true
}

cleanup() {
    if [[ "${STAGING_CREATED}" -eq 1 ]]; then
        drop_staging_tables
    fi
    rm -rf "${TMP_DIR}"
}
trap cleanup EXIT

log() {
    printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"
}

log "========================================================================"
log "Sincronización Plataforma de Clientes -> MRP"
log "Modo: ${MODE^^} | Origen: ${SRC_DB} (${SRC_MODE}) | Destino: ${DST_DB} (${DST_MODE})"
log "========================================================================"

SRC_TABLES=(
    cli_grupos
    cli_clientes
    cli_clientes_direcciones
    cli_clientes_direcciones_fiscales
    cli_tipos_clientes
    cli_regimenes_fiscales
    cli_paises
    cli_estados
    cli_municipios
)

if [[ "${SYNC_INCLUDE_CONTACTOS}" -eq 1 ]]; then
    SRC_TABLES+=(cli_contactos cli_puestos cli_departamentos)
fi

# ------------------------------------------------------------------------------
# Modo --emit-sql: generar migración autocontenida lista para phpMyAdmin
# ------------------------------------------------------------------------------
if [[ "${MODE}" == "emit-sql" ]]; then
    log "1/2 Verificando conexión a BD origen (${SRC_DB})..."
    run_src_mysql -N -e "SELECT 1;" >/dev/null

    mkdir -p "$(dirname "${EMIT_SQL_FILE}")"
    log "2/2 Generando migración SQL autocontenida en: ${EMIT_SQL_FILE}"
    {
        cat <<SQL
-- =============================================================================
-- Migración SQL autocontenida: Plataforma de Clientes -> MRP (phpMyAdmin/MariaDB)
-- Generada: $(date '+%Y-%m-%d %H:%M:%S')
-- Política: CERO sobrescritura en registros preexistentes de MRP
-- =============================================================================
SET NAMES utf8mb4 COLLATE utf8mb4_general_ci;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

SQL
        emit_ensure_grupo_col_sql
        echo ""
        emit_drop_staging_sql
        echo ""
        emit_staging_ddl
        echo ""
        echo "-- Datos extraídos desde ${SRC_DB}"
        run_src_mysqldump --compact --skip-comments "${SRC_TABLES[@]}" \
            | sed -E 's/^INSERT INTO `([a-zA-Z0-9_]+)`/INSERT INTO `stg_ext_\1`/g'
        cat <<SQL

-- Variables de control y ejecución transaccional
SET @sync_usuario_id = ${SYNC_USUARIO_ID};
SET @sync_default_tipo_cliente_id = ${SYNC_DEFAULT_TIPO_CLIENTE_ID};
SET @sync_default_regimen_id = ${SYNC_DEFAULT_REGIMEN_ID};
SET @sync_default_regimen_sat = '${SYNC_DEFAULT_REGIMEN_SAT}';
SET @sync_default_uso_cfdi = '${SYNC_DEFAULT_USO_CFDI}';
SET @sync_include_contactos = ${SYNC_INCLUDE_CONTACTOS};
SET @sync_only_active = ${SYNC_ONLY_ACTIVE};

START TRANSACTION;
SQL
        cat "${ETL_SQL}"
        echo "COMMIT;"
        echo ""
        echo "-- Limpieza de tablas temporales staging"
        emit_drop_staging_sql
    } > "${EMIT_SQL_FILE}"

    log "COMPLETADO: Archivo listo para phpMyAdmin (${EMIT_SQL_FILE}, $(du -h "${EMIT_SQL_FILE}" | cut -f1))."
    exit 0
fi

# ------------------------------------------------------------------------------
# Paso 1: Verificar conectividad en origen y destino
# ------------------------------------------------------------------------------
log "1/5 Verificando conexión a BD origen y BD destino..."
run_src_mysql -N -e "SELECT 1;" >/dev/null
run_dst_mysql -N -e "SELECT 1;" >/dev/null

# Validar que el usuario de auditoría existe en destino
USR_OK="$(run_dst_mysql -N -e "SELECT COUNT(*) FROM usuarios WHERE idusuario = ${SYNC_USUARIO_ID};")"
if [[ "${USR_OK}" -lt 1 ]]; then
    echo "[ERROR] SYNC_USUARIO_ID=${SYNC_USUARIO_ID} no existe en ${DST_DB}.usuarios." >&2
    exit 1
fi

# ------------------------------------------------------------------------------
# Paso 2: Respaldo previo automático en modo --apply
# ------------------------------------------------------------------------------
if [[ "${MODE}" == "apply" ]]; then
    FULL_BACKUP_DIR="${BACKUP_DIR}"
    if [[ "${FULL_BACKUP_DIR}" != /* ]]; then
        FULL_BACKUP_DIR="${PROJECT_ROOT}/${FULL_BACKUP_DIR}"
    fi
    mkdir -p "${FULL_BACKUP_DIR}"
    BACKUP_FILE="${FULL_BACKUP_DIR}/backup_cli_${DST_DB}_$(date '+%Y%m%d_%H%M%S').sql.gz"
    log "2/5 Generando respaldo previo en: ${BACKUP_FILE}"
    run_dst_mysqldump \
        cli_clientes \
        cli_clientes_fiscal \
        cli_clientes_sucursales \
        cli_direcciones \
        cli_clientes_contactos | gzip -9 > "${BACKUP_FILE}"
    log "    Respaldo generado ($(du -h "${BACKUP_FILE}" | cut -f1))."
else
    log "2/5 Modo DRY-RUN: se omite respaldo (la transacción hará ROLLBACK)."
fi

# ------------------------------------------------------------------------------
# Paso 3: Asegurar columna grupo_comercial y crear tablas staging en destino
# ------------------------------------------------------------------------------
log "3/5 Creando tablas staging (stg_ext_*) en ${DST_DB}..."
STAGING_CREATED=1
emit_ensure_grupo_col_sql | run_dst_mysql
drop_staging_tables
emit_staging_ddl | run_dst_mysql

# ------------------------------------------------------------------------------
# Paso 4: Extraer datos de la BD origen (solo lectura) y cargar a staging
# ------------------------------------------------------------------------------
log "4/5 Extrayendo ${#SRC_TABLES[@]} tablas desde ${SRC_DB} hacia staging..."
DUMP_FILE="${TMP_DIR}/origen_staging.sql"

run_src_mysqldump "${SRC_TABLES[@]}" \
    | sed -E 's/^INSERT INTO `([a-zA-Z0-9_]+)`/INSERT INTO `stg_ext_\1`/g; s/^LOCK TABLES `([a-zA-Z0-9_]+)`/LOCK TABLES `stg_ext_\1`/g' \
    > "${DUMP_FILE}"

run_dst_mysql < "${DUMP_FILE}"

# ------------------------------------------------------------------------------
# Paso 5: Ejecutar ETL dentro de transacción atómica (COMMIT o ROLLBACK)
# ------------------------------------------------------------------------------
log "5/5 Ejecutando ETL (${MODE^^})..."
{
    cat <<SQL
SET NAMES utf8mb4 COLLATE utf8mb4_general_ci;
SET @sync_usuario_id = ${SYNC_USUARIO_ID};
SET @sync_default_tipo_cliente_id = ${SYNC_DEFAULT_TIPO_CLIENTE_ID};
SET @sync_default_regimen_id = ${SYNC_DEFAULT_REGIMEN_ID};
SET @sync_default_regimen_sat = '${SYNC_DEFAULT_REGIMEN_SAT}';
SET @sync_default_uso_cfdi = '${SYNC_DEFAULT_USO_CFDI}';
SET @sync_include_contactos = ${SYNC_INCLUDE_CONTACTOS};
SET @sync_only_active = ${SYNC_ONLY_ACTIVE};
START TRANSACTION;
SQL
    cat "${ETL_SQL}"
    if [[ "${MODE}" == "apply" ]]; then
        echo "COMMIT;"
    else
        echo "ROLLBACK;"
    fi
} | run_dst_mysql --table

if [[ "${MODE}" == "apply" ]]; then
    log "COMPLETADO: Cambios confirmados (COMMIT) en ${DST_DB}."
else
    log "DRY-RUN COMPLETADO: Transacción revertida (ROLLBACK). ${DST_DB} NO fue modificada."
fi
