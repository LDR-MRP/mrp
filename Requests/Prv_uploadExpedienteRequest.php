<?php

class Prv_uploadExpedienteRequest extends Requests
{
    public function rules(): void
    {
        $docsPermitidos = implode(',', array_keys(Prv_detExpedienteModel::REQUIRED_DOCUMENTS));
    }
}
