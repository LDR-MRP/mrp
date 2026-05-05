<?php

class Inv_recepcionModel extends Mysql
{
    protected string $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = "inv_recepciones";
    }

    public function insertHeader(array $data): int
    {
        $sql = "INSERT INTO inv_recepciones (idcompra, plantaid, usuarioid, num_remision, observaciones, created_by) VALUES (?,?,?,?,?,?)";
        return $this->insert($sql, [
            $data['idcompra'],
            $data['plantaid'],
            $data['usuarioid'],
            $data['num_remision'],
            $data['observaciones'],
            $data['created_by']
        ]);
    }

    public function insertDetail(int $recepcionId, array $item): int
    {
        $sql = "INSERT INTO inv_recepcion_detalle (recepcionid, idrequisicionarticulo, inventarioid, cantidad_recibida) VALUES (?,?,?,?)";
        return $this->insert($sql, [
            $recepcionId,
            $item['idrequisicionarticulo'],
            $item['inventarioid'],
            $item['cantidad_recibida']
        ]);
    }
}