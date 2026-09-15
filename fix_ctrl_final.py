# Restore original + add new methods properly (no require_once!)
file_path = '/home/christianguarneros/proyectos/mrp/Controllers/Lgs_planeaciones.php'

with open(file_path, 'r') as f:
    content = f.read()

# Only add if methods don't exist yet
new_methods = """
    /**
     * POST: Clona una planeación rechazada como borrador nuevo
     */
    public function clonarPlaneacion(): void
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

    /**
     * POST: Cambia el estado de una planeación
     */
    public function changeEstado(): void
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

if 'function clonarPlaneacion' not in content:
    # Insert before the final closing brace
    last_brace = content.rfind('}')
    content = content[:last_brace] + new_methods + "\n}\n"
    
    with open(file_path, 'w') as f:
        f.write(content)
    print("OK: Methods added successfully")
else:
    print("OK: Methods already exist")
