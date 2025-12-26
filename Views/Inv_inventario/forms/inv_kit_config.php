<form id="formKitConfig" onsubmit="return false;">
    <input type="hidden" name="kitid" id="kitid" value="<?= $idinventario ?>">
    <!-- === TITULO === -->
    <div class="col-12 mb-2">
        <h6 class="fw-bold text-primary border-bottom pb-2">
            <i class="bi bi-boxes me-2"></i>Configuración de kit
        </h6>
    </div>

    <!-- ====== BLOQUE DE PRECIOS (ESTILO SAE) ====== -->
    <div class="col-12 border rounded p-3 bg-light">

        <div class="row">

            <!-- PRECIO -->
            <div class="col-md-3">
                <label class="fw-semibold">Precio</label>
                <input type="number" step="0.00001" class="form-control" id="precio" placeholder="0.00000">
            </div>

            <!-- PRECIO REAL -->
            <div class="col-md-3">
                <label class="fw-semibold">Precio real</label>
                <input type="text" class="form-control bg-secondary-subtle" id="precio_real" readonly value="0.00000">
            </div>

            <!-- COSTO -->
            <div class="col-md-3">
                <label class="fw-semibold">Costo</label>
                <input type="text" class="form-control bg-secondary-subtle" id="costo" readonly value="0.00000">
            </div>

            <!-- PRECIO MÍNIMO REAL -->
            <div class="col-md-3">
                <label class="fw-semibold">Precio mín. real</label>
                <input type="text" class="form-control bg-secondary-subtle" id="precio_min_real" readonly value="0.00000">
            </div>

        </div>
    </div>


    <!-- ====== TABLA DE COMPONENTES ====== -->
    <div class="col-12 mt-3">
        <table class="table table-bordered table-striped" id="tabla_componentes">
            <thead class="table-primary">
                <tr>
                    <th style="width: 100px;">Cantidad</th>
                    <th>Producto</th>
                    <th style="width: 120px;">Porcentaje</th>
                    <th style="width: 60px;">-</th>
                </tr>
            </thead>
            <tbody>
                <!-- Aquí JS insertará filas dinámicamente -->
            </tbody>
        </table>

        <button type="button" class="btn btn-sm btn-success" id="btn_agregar_fila">
            <i class="bi bi-plus-lg"></i> Agregar producto
        </button>
    </div>

    <!-- ====== TOTALES ====== -->
    <div class="col-12 mt-3">
        <div class="d-flex justify-content-between fw-bold">
            <span>Total de partidas: <span id="total_partidas">0</span></span>
            <span>Total: <span id="total_kit">0.00000</span></span>
        </div>
    </div>

    <!-- ====== DESCRIPCIÓN ====== -->
    <div class="col-12 mt-3">
        <label class="fw-semibold">Descripción del producto</label>
        <textarea class="form-control" id="descripcion_kit" rows="2"></textarea>
    </div>
    <!-- ===== BOTÓN GUARDAR ===== -->
    <div class="col-12 mt-4 text-end">
        <button type="button" id="btnGuardarKit" class="btn btn-success">
            Guardar configuración del kit
        </button>
    </div>
</form>