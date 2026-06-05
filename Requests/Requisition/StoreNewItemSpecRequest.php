<?php

declare(strict_types=1);

namespace Requests\Requisition;

use Requests;

/**
 * Validador para artículos nuevos (no catalogados) en una requisición.
 * HU: Captura de ficha técnica y control de negociación.
 */
class StoreNewItemSpecRequest extends Requests
{
    /**
     * Reglas de validación para la especificación técnica de artículos nuevos.
     */
    public function rules(): void
    {
        // Añadimos la validación del ID Padre
        if (empty($this->data['requisicionid']) || !is_numeric($this->data['requisicionid'])) {
            $this->addError('requisicionid', 'El identificador de la requisición es obligatorio.');
        }
        
        // 1. Vinculación con la partida
        if (empty($this->data['idrequisicionarticulo']) || !is_numeric($this->data['idrequisicionarticulo'])) {
            $this->addError('idrequisicionarticulo', 'El identificador de la partida es obligatorio.');
        }

        // 2. Ficha Técnica (Datos de la Tabla de Especificaciones)
        $requiredText = [
            'justificacion_proyecto'    => 'La justificacion o nombre del proyecto es obligatoria.',
            'categoria'                 => 'La categoría del componente es obligatoria.',
            'especificaciones_tecnicas' => 'Debe detallar las especificaciones técnicas del artículo.',
            'dimensiones_principales'   => 'Las dimensiones y características principales son requeridas.',
            'volumen_anual'             => 'El volumen anual estimado es necesario para la negociación.'
        ];

        foreach ($requiredText as $field => $message) {
            $val = (string)($this->data[$field] ?? '');
            if (empty(trim($val))) {
                $this->addError($field, $message);
            }
        }

        // 3. Control de Negociación (Sugerencias de Tito)
        // Precio Objetivo
        $targetPrice = $this->data['precio_objetivo'] ?? null;
        if (!isset($targetPrice) || (float)$targetPrice <= 0) {
            $this->addError('precio_objetivo', 'El Precio Objetivo es vital para evitar compras con sobrecosto.');
        }

        // Fechas de Negociación
        $fechaInicio = $this->data['fecha_inicio_negociacion'] ?? '';
        $fechaLimite = $this->data['fecha_limite_acuerdo'] ?? '';

        if (empty($fechaInicio)) {
            $this->addError('fecha_inicio_negociacion', 'Debe definir cuándo inicia la negociación.');
        }

        if (empty($fechaLimite)) {
            $this->addError('fecha_limite_acuerdo', 'La fecha límite de acuerdo es obligatoria.');
        }

        // Validación Lógica de Fechas
        if (!empty($fechaInicio) && !empty($fechaLimite)) {
            if (strtotime($fechaLimite) < strtotime($fechaInicio)) {
                $this->addError('fecha_limite_acuerdo', 'La fecha límite no puede ser anterior a la fecha de inicio.');
            }
        }

        // 4. Normas (Opcional pero validado en longitud)
        if (!empty($this->data['normas_requeridas']) && strlen((string)$this->data['normas_requeridas']) < 3) {
            $this->addError('normas_requeridas', 'Especifique normas válidas (ej. NOM, ISO).');
        }
    }
}