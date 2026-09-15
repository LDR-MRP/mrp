import re

with open('/home/christianguarneros/proyectos/mrp/Models/Lgs_enviosModel.php', 'r') as f:
    content = f.read()

# I will add getPlaneacionEstadoByEnvio right before getEnvioCabecera
old_str = """    public function getEnvioCabecera(int $idEnvio): array"""
new_str = """    public function getPlaneacionEstadoByEnvio(int $idEnvio): ?int
    {
        $sql = "SELECT p.id_estado 
                FROM lgs_planeaciones p 
                INNER JOIN lgs_planeaciones_envios pe ON p.id_planeacion = pe.id_planeacion 
                WHERE pe.id_envio = ? 
                LIMIT 1";
        $db = $this->getConexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([$idEnvio]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res && isset($res['id_estado'])) {
            return intval($res['id_estado']);
        }
        return null;
    }

    public function getEnvioCabecera(int $idEnvio): array"""

if old_str in content:
    content = content.replace(old_str, new_str)
    with open('/home/christianguarneros/proyectos/mrp/Models/Lgs_enviosModel.php', 'w') as f:
        f.write(content)
    print("Lgs_enviosModel.php updated successfully!")
else:
    print("Could not find getEnvioCabecera signature")
