<?php
/** Avisos posteriores al COMMIT. Un error SMTP no revierte una aprobación confirmada. */
class PedRevisionNotificador extends Mysql
{
    private function datos(string $clave): ?array
    {
        return $this->select('SELECT p.clave,p.folio_pedido,p.estatus,p.estatus_revision_comercial,p.idcliente,p.total,
                 COALESCE(NULLIF(ua.correo,\'\'),NULLIF(c.correo,\'\')) AS correo_distribuidor,
                 COALESCE(NULLIF(CONCAT_WS(\' \',ua.nombre,ua.apellido),\'\'),c.nombre_comercial,c.razon_social) AS nombre,
                 r.numero_revision,r.total AS total_propuesta
                 FROM ped_pedidos p JOIN cli_clientes c ON c.idcliente=p.idcliente
                 LEFT JOIN cli_usuarios_acceso ua ON ua.idusuario_acceso=p.idusuario_acceso
                 LEFT JOIN ped_pedidos_revisiones r ON r.idrevision=(SELECT MAX(rr.idrevision) FROM ped_pedidos_revisiones rr WHERE rr.idpedido=p.idpedido)
                 WHERE p.clave=? LIMIT 1',[$clave]) ?: null;
    }
    private function enviar(string $destino, string $plantilla, array $datos): void
    {
        if (!filter_var($destino,FILTER_VALIDATE_EMAIL)) {
            error_log('Notificación comercial omitida: destinatario sin correo válido');
            return;
        }
        if (!function_exists('sendMailLocalCron')) {
            error_log('Notificación comercial pendiente: sendMailLocalCron no disponible');
            return;
        }
        $datos['email']=$destino;
        $datos['logo_url']=base_url().'/Assets/images/logo-cotizaciones.png';
        error_log('[PED_CORREO] Intentando enviar '.$plantilla.' a '.$destino.' folio='.$datos['folio_pedido']);
        $enviado=sendMailLocalCron($datos,$plantilla,'carlos.cruz@ldrsolutions.com.mx');
        if (!$enviado) error_log('[PED_CORREO] FALLÓ '.$plantilla.' destino='.$destino.' folio='.$datos['folio_pedido']);
        else error_log('[PED_CORREO] Envío reportado como exitoso '.$plantilla.' destino='.$destino.' folio='.$datos['folio_pedido']);
    }
    public function distribuidor(string $clave,string $evento):void
    {
        $d=$this->datos($clave);if(!$d)return;
        $d['asunto']='Propuesta comercial pendiente · '.$d['folio_pedido'];
        $d['url_propuesta']=base_url().'/orders/verpropuestacomercial/'.rawurlencode($clave);
        $this->enviar((string)$d['correo_distribuidor'],'email_ped_propuesta_enviada',$d);
    }
    public function comercial(string $clave,string $respuesta,string $observaciones):void
    {
        $d=$this->datos($clave);if(!$d)return;
        // Configurar en Config.php: define('PED_COMERCIAL_NOTIFICACION_EMAIL','correo@empresa.com');
        $destino=defined('PED_COMERCIAL_NOTIFICACION_EMAIL')?(string)PED_COMERCIAL_NOTIFICACION_EMAIL:'';
        if (!$destino) {error_log('[PED_CORREO] NO ENVIADO: define PED_COMERCIAL_NOTIFICACION_EMAIL en Config.php; respuesta='.$respuesta.' folio='.$d['folio_pedido']);return;}
        $map=['ACEPTAR'=>['email_ped_propuesta_aceptada','Propuesta aceptada'], 'SOLICITAR_AJUSTE'=>['email_ped_propuesta_ajustes','Ajustes solicitados'], 'RECHAZAR'=>['email_ped_propuesta_rechazada','Propuesta rechazada']];
        if(!isset($map[$respuesta]))return;
        $d['observaciones_respuesta']=$observaciones;
        $d['asunto']=$map[$respuesta][1].' · '.$d['folio_pedido'];
        $d['url_gestion']=base_url().'/ped_pedidos/gestionar/'.rawurlencode($clave);
        $this->enviar($destino,$map[$respuesta][0],$d);
    }
}
