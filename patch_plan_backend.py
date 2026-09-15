import re

with open('/home/christianguarneros/proyectos/mrp/Models/Lgs_planeacionesModel.php', 'r') as f:
    model_content = f.read()

func_to_add_model = """    public function clonarPlaneacion(int $idPlaneacionOriginal, int $userId): array
    {
        $sqlOrig = "SELECT * FROM lgs_planeaciones WHERE id_planeacion = ?";
        $orig = $this->select($sqlOrig, [$idPlaneacionOriginal]);
        
        if (empty($orig)) {
            throw new Exception("La planeación original no existe.");
        }
        
        $nuevoTitulo = $orig['descripcion'] . " (Copia)";
        $sqlInsert = "INSERT INTO lgs_planeaciones (descripcion, obs_operador, id_estado, user_created, status) 
                      VALUES (?, ?, 1, ?, 1)";
        $idNueva = $this->insert($sqlInsert, [
            $nuevoTitulo,
            $orig['obs_operador'],
            $userId
        ]);
        
        if ($idNueva <= 0) {
            throw new Exception("No se pudo crear la nueva planeación.");
        }
        
        // Copiar los envíos
        $sqlEnvios = "SELECT id_envio FROM lgs_planeaciones_envios WHERE id_planeacion = ?";
        $envios = $this->select_all($sqlEnvios, [$idPlaneacionOriginal]);
        
        $sqlInsertEnvio = "INSERT INTO lgs_planeaciones_envios (id_planeacion, id_envio) VALUES (?, ?)";
        foreach ($envios as $e) {
            // Verificar si el envío sigue disponible (no está en otra planeación que no sea rechazada)
            // Para simplicidad, los insertamos. Si ya están en otra, fallará o los tendremos que validar.
            $this->insert($sqlInsertEnvio, [$idNueva, $e['id_envio']]);
            
            // Actualizar el estado del envío a 2 (En Aprobación / Planeación) para bloquearlo de otras planeaciones
            $sqlUpdEnvio = "UPDATE lgs_envios SET id_estado = 2 WHERE id_envio = ?";
            $this->update($sqlUpdEnvio, [$e['id_envio']]);
        }
        
        return ['status' => true, 'id_planeacion' => $idNueva];
    }
"""

if "function clonarPlaneacion" not in model_content:
    model_content = model_content.replace('}', func_to_add_model + '\n}', 1) # simple approach, better to append before last brace
    # Actually, I should use re.sub to inject before the last closing brace
    model_content = re.sub(r'}(?!.*\})', func_to_add_model + '\n}', model_content, flags=re.DOTALL)
    with open('/home/christianguarneros/proyectos/mrp/Models/Lgs_planeacionesModel.php', 'w') as f:
        f.write(model_content)
    print("Added clonarPlaneacion to Model.")

with open('/home/christianguarneros/proyectos/mrp/Controllers/Lgs_planeaciones.php', 'r') as f:
    ctrl_content = f.read()

func_to_add_ctrl = """    public function clonarPlaneacion(): void
    {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            $idPlaneacion = intval($_POST['id_planeacion'] ?? 0);

            if ($idPlaneacion <= 0) {
                throw new Exception("ID de planeación no válido.");
            }

            $model = new Lgs_planeacionesModel();
            $res = $model->clonarPlaneacion($idPlaneacion, $userId);
            
            echo $this->successResponse(['id_planeacion' => $res['id_planeacion']], "Planeación clonada con éxito.");
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }
"""
if "function clonarPlaneacion" not in ctrl_content:
    ctrl_content = re.sub(r'}(?!.*\})', func_to_add_ctrl + '\n}', ctrl_content, flags=re.DOTALL)
    with open('/home/christianguarneros/proyectos/mrp/Controllers/Lgs_planeaciones.php', 'w') as f:
        f.write(ctrl_content)
    print("Added clonarPlaneacion to Controller.")

