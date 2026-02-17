<?php

class Com_requisicionStatusRequest extends Requests
{
    public $model;

    public function rules()
    {
        $idRequisicion = $this->data['idrequisicion'] ?? null;
        $comentario = $this->data['comentario'] ?? null;

        if (empty($idRequisicion)) {
            $this->addError('idrequisicion', 'El ID de la requisición es obligatorio.');
            return; 
        }

        if (empty($comentario)) {
            $this->addError('comentario', 'El comentario de la requisición es obligatorio.');
        }

        $requisition = $this->model->requisition($this->data['idrequisicion']);

        if (!$requisition) {
            $this->addError('idrequisicion', 'La requisición no existe.');
            return;
        }

        $estatusActual = strtoupper($requisition['estatus']);

        if (mb_strtolower($estatusActual, 'UTF-8') !== mb_strtolower(Com_requisicionModel::ESTATUS_PENDIENTE, 'UTF-8')) {
            $this->addError('idrequisicion', "Esta requisición ya ha sido procesada anteriormente (Estado: {$estatusActual}).");
        }
    }
}
