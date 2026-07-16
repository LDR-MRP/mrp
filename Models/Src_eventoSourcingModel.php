<?php

declare(strict_types=1);

namespace Models;

use Mysql;

class Src_eventoSourcingModel extends Mysql
{
    use \Auditable;

    protected string $table = 'src_eventos_sourcing';

    public function __construct()
    {
        parent::__construct();
    }

    public function getTableName(): string 
    {
        return $this->table;
    }

    /**
     * Obtiene el listado de eventos con conteo de partidas y cotizaciones recibidas.
     */
    public function getSourcingEvents(int $plantaId): array
    {
        $sql = "SELECT 
                    ev.id,
                    ev.folio,
                    ev.titulo,
                    ev.estatus_evento,
                    ev.created_at,
                    u.nombres as comprador_nombre,
                    -- KPI: Cuántas partidas tiene este evento
                    (SELECT COUNT(*) FROM com_requisiciones_detalle WHERE src_evento_sourcing_id = ev.id) as total_partidas,
                    -- KPI: Cuántas cotizaciones totales se han recibido para este grupo
                    (SELECT COUNT(*) FROM com_requisicion_cotizaciones WHERE src_evento_sourcing_id = ev.id AND deleted_at IS NULL) as total_cotizaciones
                FROM src_eventos_sourcing ev
                INNER JOIN usuarios u ON ev.comprador_id = u.idusuario
                WHERE ev.planta_id = ? 
                AND ev.deleted_at IS NULL
                ORDER BY ev.created_at DESC";

        return $this->select_all($sql, [$plantaId]);
    }



    /**
     * Crea la cabecera del evento de sourcing.
     * Renombrado para evitar colisión con el core.
     */
    public function createEventHeader(array $data): int|string
    {
        $query = "INSERT INTO {$this->table} 
                  (folio, titulo, planta_id, comprador_id, estatus_evento, created_by, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['folio'],
            $data['titulo'],
            $data['planta_id'],
            $data['comprador_id'],
            $data['estatus_evento'] ?? 'ABIERTO',
            $data['created_by'],
            $data['created_at']
        ];

        return $this->insert($query, $params);
    }

    /**
     * Verifica si quedan partidas pendientes de catalogar en un evento.
     * Si no hay pendientes, cierra el evento automáticamente.
     */
    public function checkAndCloseEvent(int $eventId, int $userId): void
    {
        // 1. Contar partidas en com_requisiciones_detalle que aún no tienen inventarioid (WMS)
        $queryCount = "SELECT COUNT(*) as pendientes 
                       FROM com_requisiciones_detalle 
                       WHERE src_evento_sourcing_id = ? 
                       AND inventarioid IS NULL";
        
        $result = $this->select($queryCount, [$eventId]);
        $pendientes = (int)($result['pendientes'] ?? 0);

        // 2. Si el contador es 0, el evento se marca como ADJUDICADO (Ciclo completo)
        if ($pendientes === 0) {
            $this->updateStatus($eventId, 'ADJUDICADO', $userId);
        }
    }

    /**
     * Actualiza el estatus del evento y registra la auditoría de Eloquent.
     */
    public function updateStatus(int $eventId, string $status, int $userId): bool
    {
        $query = "UPDATE {$this->table} 
                  SET estatus_evento = ?, 
                      updated_by = ?, 
                      updated_at = ? 
                  WHERE id = ?";
        
        $params = [
            $status,
            $userId,
            date('Y-m-d H:i:s'),
            $eventId
        ];

        return $this->update($query, $params);
    }

    /**
     * Obtiene ítems de requisiciones aprobadas que no están en el Hub.
     */
    public function getPendingSourcingItems(int $plantaId): array
    {
        $query = "SELECT 
                    rd.idrequisicionarticulo, 
                    r.folio as folio_requisicion, 
                    r.idrequisicion,
                    rd.notas as descripcion_item, 
                    in_nue.precio_objetivo, 
                    r.prioridad, 
                    r.created_at as fecha_requisicion,
                    in_nue.categoria as categoria_sourcing
                  FROM com_requisiciones_detalle rd
                  INNER JOIN com_requisiciones r ON rd.requisicionid = r.idrequisicion
                  INNER JOIN com_requisicion_items_nuevos in_nue ON rd.idrequisicionarticulo = in_nue.idrequisicionarticulo
                  WHERE r.plantaid = ? 
                  AND r.estatus = 'aprobada' 
                  AND rd.inventarioid IS NULL 
                  AND rd.src_evento_sourcing_id IS NULL 
                  AND rd.deleted_at IS NULL";
                  
        return $this->select_all($query, [$plantaId]);
    }

    /**
     * Obtiene todas las partidas (ítems) vinculadas a un evento de sourcing específico.
     * Este método es el que permite "poblar" el menú lateral del detalle.
     */
    public function getItemsByEvent(int $eventId): array
    {
        $sql = "SELECT 
                    rd.idrequisicionarticulo,
                    rd.requisicionid,
                    rd.inventarioid,
                    r.folio as folio_requisicion,
                    COALESCE(in_nue.descripcion_sourcing, rd.notas) as descripcion,
                    rd.cantidad,
                    in_nue.precio_objetivo,
                    -- Traemos el conteo de cotizaciones por cada partida para el indicador visual
                    (SELECT COUNT(*) FROM com_requisicion_cotizaciones 
                    WHERE idrequisicionarticulo = rd.idrequisicionarticulo 
                    AND deleted_at IS NULL) as total_cotizaciones
                FROM com_requisiciones_detalle rd
                INNER JOIN com_requisiciones r ON rd.requisicionid = r.idrequisicion
                LEFT JOIN com_requisicion_items_nuevos in_nue ON rd.idrequisicionarticulo = in_nue.idrequisicionarticulo
                WHERE rd.src_evento_sourcing_id = ? 
                AND rd.deleted_at IS NULL";

        return $this->select_all($sql, [$eventId]);
    }

    /**
     * Obtiene la información de cabecera de un evento de sourcing.
     * 
     * @param int $id ID del evento (PK de src_eventos_sourcing).
     * @return array|null
     */
    public function getEventHeader(int $id): ?array
    {
        $sql = "SELECT 
                    ev.id,
                    ev.folio,
                    ev.titulo,
                    ev.estatus_evento,
                    ev.created_at,
                    ev.comprador_id,
                    u.nombres as comprador_nombre
                FROM src_eventos_sourcing ev
                INNER JOIN usuarios u ON ev.comprador_id = u.idusuario
                WHERE ev.id = ? 
                AND ev.deleted_at IS NULL";

        return $this->select($sql, [$id]) ?: null;
    }
}