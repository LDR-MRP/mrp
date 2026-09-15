import sys

file_path = '/home/christianguarneros/proyectos/mrp/Services/Lgs_aprobacionesService.php'
with open(file_path, 'r') as f:
    content = f.read()

target = """    public function getDetallePlan(int $idPlaneacion): array {
        return $this->model->getEnviosPorPlaneacion($idPlaneacion);
    }"""

replacement = """    public function getDetallePlan(int $idPlaneacion): array {
        require_once('Services/Lgs_planeacionesService.php');
        $planService = new Lgs_planeacionesService();
        $planCompleto = $planService->getDetalleCompletoPlan($idPlaneacion);
        return $planCompleto['envios'] ?? [];
    }"""

if target in content:
    content = content.replace(target, replacement)
    with open(file_path, 'w') as f:
        f.write(content)
    print("Patched service successfully")
else:
    print("Target not found in service")
