<?php

class Prv_proveedorModel extends Mysql
{
    use Auditable;

    public function getTableName(): string 
    {
        return "prv_proveedores";
    }

    public function findByCriteria(array $filters = [])
    {
        $sql = "SELECT
                -- Master
                `prv_cat_proveedores`.`id_proveedor`,
                `prv_cat_proveedores`.`id_empresa`,
                `prv_cat_proveedores`.`rfc`,
                `prv_cat_proveedores`.`razon_social`,
                `prv_cat_proveedores`.`nombre_comercial`,
                `prv_cat_proveedores`.`origen`,
                `prv_cat_proveedores`.`estatus_onboarding`,
                `prv_cat_proveedores`.`estatus_operativo`,
                `prv_cat_proveedores`.`created_at`,
                `prv_cat_proveedores`.`created_by`,
                -- Addresses Columns
                `prv_det_direcciones`.`tipo`,
                `prv_det_direcciones`.`calle`,
                `prv_det_direcciones`.`num_ext`,
                `prv_det_direcciones`.`num_int`,
                `prv_det_direcciones`.`colonia`,
                `prv_det_direcciones`.`cp`,
                `prv_det_direcciones`.`municipio`,
                `prv_det_direcciones`.`ciudad`,
                `prv_det_direcciones`.`estado`,
                `prv_det_direcciones`.`es_principal`,
                -- Contacts Columns
                `prv_det_contactos`.`nombre`,
                `prv_det_contactos`.`puesto`,
                `prv_det_contactos`.`email`,
                `prv_det_contactos`.`telefono`,
                `prv_det_contactos`.`notificar_compras`,
                -- Financial Config Columns
                `prv_det_config_financiera`.`id_config_financiera`,
                `prv_det_config_financiera`.`id_proveedor`,
                `prv_det_config_financiera`.`id_condicion_pago`,
                `prv_det_config_financiera`.`id_cuenta_contable`,
                `prv_det_config_financiera`.`limite_credito`,
                `prv_det_config_financiera`.`id_moneda_defecto`,
                `prv_det_config_financiera`.`tasa_iva_default`,
                `cat_condiciones_pago`.`descripcion`,
                -- Banking Information Columns 
                `prv_det_cuentas_bancarias`.`id_banco`,
                `prv_det_cuentas_bancarias`.`clabe`,
                `prv_det_cuentas_bancarias`.`cuenta`,
                `prv_det_cuentas_bancarias`.`swift_bic`,
                `prv_det_cuentas_bancarias`.`es_principal`
            FROM `prv_cat_proveedores`
            -- Addresses JOIN
            LEFT JOIN `prv_det_direcciones`
                ON `prv_det_direcciones`.`id_proveedor` = `prv_cat_proveedores`.`id_proveedor`
            -- Contacts JOIN
            LEFT JOIN `prv_det_contactos`
                ON `prv_det_contactos`.`id_proveedor` = `prv_cat_proveedores`.`id_proveedor`
            -- Financial Config JOIN
            LEFT JOIN `prv_det_config_financiera`
                ON `prv_det_config_financiera`.`id_proveedor` = `prv_cat_proveedores`.`id_proveedor`
            LEFT JOIN `cat_cuentas_contables`
                ON `cat_cuentas_contables`.`id_cuenta_contable` = `prv_det_config_financiera`.`id_cuenta_contable`
            LEFT JOIN `cat_condiciones_pago`
                ON `cat_condiciones_pago`.`id_condicion` = `prv_det_config_financiera`.`id_condicion_pago`
            -- Banking Information JOIN
            LEFT JOIN `prv_det_cuentas_bancarias`
                ON `prv_det_cuentas_bancarias`.`id_proveedor` = `prv_cat_proveedores`.`id_proveedor`
            -- File JOIN
            LEFT JOIN `prv_det_expediente`
                ON `prv_det_expediente`.`id_proveedor` = `prv_cat_proveedores`.`id_proveedor`
            -- Onboarding JOIN
            LEFT JOIN `prv_tra_onboarding`
                ON `prv_tra_onboarding`.`id_proveedor` = `prv_cat_proveedores`.`id_proveedor`
            WHERE true\n";

        if(array_key_exists('id_proveedor', $filters)){ $sql .= "AND id_proveedor = '{$filters['id_proveedor']}'"; }
        if(array_key_exists('estado', $filters)){ $sql .= "AND estado = '{$filters['estado']}'"; }
        if(array_key_exists('rfc', $filters)){ $sql .= "AND rfc = '{$filters['rfc']}'"; }

        return $this->select_all($sql);
    }

    public function getKpi()
    {
        return $this->select_all(
            "SELECT 
                IFNULL(estatus_operativo, 'total') AS estatus,
                COUNT(id_proveedor) AS cantidad
            FROM prv_cat_proveedores
            GROUP BY estatus_operativo WITH ROLLUP;
            "
        );
    }

    public function destroy(int $idproveedor)
    {
        $query = sprintf(
            "UPDATE prv_proveedores SET estatus = 0, deleted_at = NOW() WHERE idproveedor = %d;",
            $idproveedor
        );
        return $this->delete($query);
    }

    public function insertProveedor(array $h): int {
        return $this->insert(
            "INSERT INTO prv_cat_proveedores (
                id_empresa,
                rfc,
                razon_social,
                nombre_comercial,
                id_tipo_persona,
                id_regimen_fiscal,
                origen,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $h['id_empresa'],
                $h['rfc'],
                $h['razon_social'], 
                $h['nombre_comercial'],
                $h['id_tipo_persona'], 
                $h['id_regimen_fiscal'],
                $h['origen'],
                $_SESSION['idUser'],
            ]
        );
    }

    public function insertDireccion(array $d, int $idProveedor): int {
        return $this->insert(
            "INSERT INTO prv_det_direcciones (
                id_proveedor,tipo,
                calle,
                num_ext,
                num_int,
                colonia,
                cp,
                municipio,
                ciudad,
                estado,
                es_principal,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $idProveedor,
                $d['tipo'],
                $d['calle'],
                $d['num_ext'], 
                $d['num_int'],
                $d['colonia'],
                $d['cp'],
                $d['municipio'], 
                $d['ciudad'], 
                $d['estado'], 
                $d['es_principal'],
                $_SESSION['idUser'],
            ]
        );
    }

    public function insertConfigFinanciera(array $f, int $idProveedor): int {
        return $this->insert(
            "INSERT INTO prv_det_config_financiera (
                    id_proveedor,
                    id_condicion_pago,
                    id_cuenta_contable,
                    limite_credito,
                    id_moneda_defecto,
                    tasa_iva_default,
                    created_by
                ) 
                VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $idProveedor,
                    $f['id_condicion_pago'],
                    $f['id_cuenta_contable'], 
                    $f['limite_credito'],
                    $f['id_moneda_defecto'],
                    $f['tasa_iva_default'],
                    $_SESSION['idUser'],
                ]
            );
    }

    public function insertContacto(array $c, int $idProveedor): int {
        return $this->insert(
            "INSERT INTO prv_det_contactos (
                id_proveedor,
                nombre,
                puesto,
                email,
                telefono,
                notificar_compras,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $idProveedor,
                $c['nombre'],
                $c['puesto'], 
                $c['email'],
                $c['telefono'],
                $c['notificar_compras'],
                $_SESSION['idUser'],
            ]
        );
    }

    public function insertOnboarding(int $idProveedor): int {
        return $this->insert(
            "INSERT INTO prv_tra_onboarding (
                id_proveedor,
                paso_actual,
                created_by
            ) VALUES (?, 1, ?)",
            [
                $idProveedor,
                $_SESSION['idUser'],
            ]
        );
    }
}