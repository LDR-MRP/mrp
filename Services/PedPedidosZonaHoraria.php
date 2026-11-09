<?php
/**
 * Zona horaria exclusiva de las conexiones de Pedidos y Backorders.
 * No modifica la configuracion global de MySQL ni datos historicos.
 * Ciudad de Mexico utiliza UTC-06:00 en el regimen horario vigente.
 */
trait PedPedidosZonaHoraria
{
    protected function configurarZonaHorariaPedidos(): void
    {
        // Ejecutar sobre el PDO que utiliza el modelo/servicio.
        // La zona horaria de sesion afecta NOW() y CURRENT_TIMESTAMP.
        $this->getConexion()->exec("SET SESSION time_zone = '-06:00'");
    }
}
