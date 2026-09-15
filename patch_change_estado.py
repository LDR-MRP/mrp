import re

with open('/home/christianguarneros/proyectos/mrp/Models/Lgs_planeacionesModel.php', 'r') as f:
    model_content = f.read()

func_to_add_model = """    public function changeEstado(int $idPlaneacion, int $newEstado, int $userId, string $msg): bool
    {
        // Actualizar estado planeacion
        $sql = "UPDATE lgs_planeaciones SET id_estado = ?, updated_at = NOW() WHERE id_planeacion = ?";
        $res = $this->update($sql, [$newEstado, $idPlaneacion]);
        
        $this->logEstadoPlaneacion($idPlaneacion, $newEstado, $userId, $msg);
        
        // Si se rechaza (4), liberar envios a estado 1
        if ($newEstado == 4) {
            $sqlEnvios = "UPDATE lgs_envios e
                          INNER JOIN lgs_planeaciones_envios pe ON e.id_envio = pe.id_envio
                          SET e.id_estado = 1
                          WHERE pe.id_planeacion = ?";
            $this->update($sqlEnvios, [$idPlaneacion]);
        }
        
        // Si se aprueba (3), marcar envios como Aprobados (3)
        if ($newEstado == 3) {
            $sqlEnvios = "UPDATE lgs_envios e
                          INNER JOIN lgs_planeaciones_envios pe ON e.id_envio = pe.id_envio
                          SET e.id_estado = 3
                          WHERE pe.id_planeacion = ?";
            $this->update($sqlEnvios, [$idPlaneacion]);
        }
        
        return $res;
    }
"""

if "function changeEstado(" not in model_content:
    model_content = re.sub(r'}(?!.*\})', func_to_add_model + '\n}', model_content, flags=re.DOTALL)
    with open('/home/christianguarneros/proyectos/mrp/Models/Lgs_planeacionesModel.php', 'w') as f:
        f.write(model_content)
    print("Added changeEstado to Model.")

with open('/home/christianguarneros/proyectos/mrp/Controllers/Lgs_planeaciones.php', 'r') as f:
    ctrl_content = f.read()

func_to_add_ctrl = """    public function changeEstado(): void
    {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            $idPlaneacion = intval($_POST['id_planeacion'] ?? 0);
            $newEstado = intval($_POST['id_estado'] ?? 0);
            $msg = $_POST['msg'] ?? '';

            if ($idPlaneacion <= 0 || $newEstado <= 0) {
                throw new Exception("Datos no válidos.");
            }

            $model = new Lgs_planeacionesModel();
            $model->changeEstado($idPlaneacion, $newEstado, $userId, $msg);
            
            echo $this->successResponse([], "Estado actualizado correctamente.");
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }
"""

if "function changeEstado(" not in ctrl_content:
    ctrl_content = re.sub(r'}(?!.*\})', func_to_add_ctrl + '\n}', ctrl_content, flags=re.DOTALL)
    with open('/home/christianguarneros/proyectos/mrp/Controllers/Lgs_planeaciones.php', 'w') as f:
        f.write(ctrl_content)
    print("Added changeEstado to Controller.")

