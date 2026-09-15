import sys

file_path = '/home/christianguarneros/proyectos/mrp/Views/Lgs_aprobaciones/index.php'
with open(file_path, 'r') as f:
    content = f.read()

target = """                <!-- Tabla de Envíos/Rutas Agrupadas -->
                <h6 class="fw-bold text-uppercase fs-12 text-muted mb-2"><i class="ri-truck-line me-1 text-primary"></i> Envíos y Madrinas Incluidas en este Plan</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light fs-11 text-uppercase text-muted">
                            <tr>
                                <th>Folio Envío</th>
                                <th>Modalidad</th>
                                <th>Origen</th>
                                <th>Trasladista</th>
                                <th class="text-center">Total VINs</th>
                                <th>Costo Envío</th>
                            </tr>
                        </thead>
                        <tbody id="bodyDetalleRutas" class="fs-12">
                            <!-- Inyectado dinámicamente -->
                        </tbody>
                    </table>
                </div>"""

replacement = """                <!-- Tabla de Envíos/Rutas Agrupadas con Detalle de VINs -->
                <h6 class="fw-bold text-uppercase fs-12 text-muted mb-2"><i class="ri-truck-line me-1 text-primary"></i> Desglose de Envíos, Madrinas y VINs Asignados</h6>
                <div id="vdp_contenedor_envios" class="mb-4">
                    <!-- Inyectado dinámicamente -->
                </div>"""

if target in content:
    content = content.replace(target, replacement)
    with open(file_path, 'w') as f:
        f.write(content)
    print("Patched view successfully")
else:
    print("Target not found in view")
