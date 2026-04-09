<?php

class Inv_ubicacionesService
{
    public $model;

    public function getUbicaciones(): ServiceResponse
    {
        try {

            $data = $this->model->selectUbicaciones();
            foreach ($data as &$item) {
                switch ($item['estado']) {
                    case 2:
                        $item['estado'] = '<span class="badge bg-success">Disponible</span>';
                        break;
                    case 1:
                        $item['estado'] = '<span class="badge bg-danger">Asignada</span>';
                        break;
                }
            }

            return ServiceResponse::success(
                $data,
                "Listado obtenido correctamente"
            );
        } catch (Exception $e) {

            return ServiceResponse::error(
                $e->getMessage(),
                500
            );
        }
    }

    public function store($data): ServiceResponse
    {
        try {

            $zonaid = intval($data['zonaid']);
            $pasillo = strClean($data['pasillo']);
            $seccion = strClean($data['seccion']);
            $nivel = strClean($data['nivel']);
            $descripcion = strClean($data['descripcion']);

            $codigoBase = strtoupper(strClean($data['codigo_base']));
            $cantidad = intval($data['cantidad']);

            preg_match('/([A-Z]+)([0-9]+)/', $codigoBase, $match);

            if (!$match) {
                throw new Exception("El código inicial no es válido. Ejemplo: B01");
            }

            $prefijo = $match[1];
            $numeroInicial = intval($match[2]);
            $padding = strlen($match[2]);

            $insertadas = [];
            $duplicadas = [];

            for ($i = 0; $i < $cantidad; $i++) {

                $numero = $numeroInicial + $i;

                $lugar = $prefijo . str_pad($numero, $padding, "0", STR_PAD_LEFT);

                /* VALIDAR DUPLICADO */

                $existe = $this->model->existeUbicacion($zonaid, $lugar);

                if ($existe) {

                    $duplicadas[] = $lugar;
                    continue;
                }

                $this->model->insertUbicacion([
                    'zonaid' => $zonaid,
                    'pasillo' => $pasillo,
                    'seccion' => $seccion,
                    'nivel' => $nivel,
                    'lugar' => $lugar,
                    'descripcion' => $descripcion
                ]);

                $insertadas[] = $lugar;
            }

            $mensaje = "Ubicaciones insertadas: $insertadas";

            if (!empty($duplicadas)) {

                $mensaje .= "\nDuplicadas omitidas: " . implode(", ", $duplicadas);
            }

            return ServiceResponse::success([
                "insertadas" => $insertadas,
                "duplicadas" => $duplicadas
            ], "Proceso finalizado");
        } catch (Exception $e) {

            return ServiceResponse::error($e->getMessage());
        }
    }
}
