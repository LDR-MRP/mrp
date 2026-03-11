<?php

class Prv_detExpedienteModel extends Mysql
{
    public const DOCUMENTOS_REQUERIDOS = [
        'CONSTITUTIVA' => ['name' => 'Acta Constitutiva', 'required' => true, 'ext' => 'pdf'],
        'CSF' => ['name' => 'Constancia de Situación Fiscal', 'required' => true, 'ext' => 'pdf'],
        'RFC' => ['name' => 'RFC', 'required' => true, 'ext' => 'pdf'],
        'INE' => ['name' => 'INE', 'required' => true, 'ext' => 'pdf'], 
    ];

    public function __construct()
    {
        parent::__construct();
    }
}