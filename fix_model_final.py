file_path = '/home/christianguarneros/proyectos/mrp/Models/Lgs_planeacionesModel.php'

with open(file_path, 'r') as f:
    content = f.read()

new_methods = '''
    /**
     * Clona una planeación rechazada como borrador nuevo
     */
    public function clonarPlaneacion(int $idPlaneacionOriginal, int $userId): array
    {
        $sqlOrig = "SELECT * FROM lgs_planeaciones WHERE id_planeacion = ?";
        $orig = $this->select($sqlOrig, [$idPlaneacionOriginal]);
        
        if (empty($orig)) {
            throw new Exception("La planeación original no existe.");
        }
        
        $nuevoTitulo = $orig['descripcion'] . " (Copia)";
        $sqlInsert = "INSERT INTO lgs_planeaciones (descripcion, obs_operador, id_estado, created_by) 
                      VALUES (?, ?, 1, ?)";
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
            $this->insert($sqlInsertEnvio, [$idNueva, $e['id_envio']]);
            $sqlUpdEnvio = "UPDATE lgs_envios SET id_estado = 2 WHERE id_envio = ?";
            $this->update($sqlUpdEnvio, [$e['id_envio']]);
        }
        
        return ['status' => true, 'id_planeacion' => $idNueva];
    }

    /**
     * Cambia el estado de una planeación y actualiza envíos según corresponda
     */
    public function changeEstado(int $idPlaneacion, int $newEstado, int $userId, string $msg): bool
    {
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
        
        // Si se aprueba (5), marcar envios como Aprobados
        if ($newEstado == 5) {
            $sqlEnvios = "UPDATE lgs_envios e
                          INNER JOIN lgs_planeaciones_envios pe ON e.id_envio = pe.id_envio
                          SET e.id_estado = 3
                          WHERE pe.id_planeacion = ?";
            $this->update($sqlEnvios, [$idPlaneacion]);
        }
        
        return $res;
    }

    /**
     * Registra un log del cambio de estado
     */
    private function logEstadoPlaneacion(int $idPlaneacion, int $newEstado, int $userId, string $msg): void
    {
        try {
            $sql = "INSERT INTO lgs_planeaciones_log (id_planeacion, id_estado, id_usuario, mensaje, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            $this->insert($sql, [$idPlaneacion, $newEstado, $userId, $msg]);
        } catch (Exception $e) {
            // Si la tabla no existe, no bloquear la operación principal
        }
    }
'''

if 'function clonarPlaneacion' not in content:
    # Find the last closing brace of the class
    last_brace = content.rfind('}')
    content = content[:last_brace] + new_methods + "\n}\n"
    
    with open(file_path, 'w') as f:
        f.write(content)
    print("OK: Methods added to model")
else:
    print("OK: Methods already exist")
