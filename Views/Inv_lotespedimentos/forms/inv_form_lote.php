<form id="formLote" autocomplete="off" class="form-steps was-validated" autocomplete="off">
    <input type="hidden" id="id_ltpd" name="id_ltpd" value="0">
    <div class="row">

        <!-- BLOQUE 1 -->
        <div class="row mb-3">
            <!-- ALMACEN -->
            <div class="col-md-4">
                <label>Almacén</label>
                <div class="input-group">
                    <select class="form-select almacen-select" name="almacenid">
                        <option value="">--Seleccione--</option>
                    </select>
                </div>
            </div>

            <!-- PRODUCTO -->
            <div class="col-md-4">
                <label>Producto</label>
                <div class="input-group">
                    <select class="form-control producto-select" name="inventarioid">
                        <option value="">Seleccione producto</option>
                    </select>
                </div>
            </div>

            <!-- CANTIDAD -->
            <div class="col-md-4">
                <label>Cantidad</label>
                <input type="number" step="any" class="form-control" id="lote_cantidad" name="lote_cantidad">
            </div>
        </div>

        <!-- BLOQUE 2 -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label>Lote</label>
                <input type="text" class="form-control" id="lote_lote" name="lote_lote">
            </div>

            <div class="col-md-3">
                <label>Fecha producción</label>
                <input type="date" class="form-control" id="lote_fecha_produccion" name="lote_fecha_produccion">
            </div>

            <div class="col-md-3">
                <label>Fecha Caducidad</label>
                <input type="date" class="form-control" id="lote_fecha_caducidad" name="lote_fecha_caducidad">
            </div>
        </div>


        <!-- OBSERVACIONES -->
        <div class="row mb-4">
            <div class="col-md-12">
                <label>Observaciones</label>
                <textarea class="form-control" id="cve_observacion" name="cve_observacion" rows="3"></textarea>
            </div>
        </div>
    </div>
    <!-- end row -->

    <div class="d-flex align-items-start gap-3 mt-4">
        <button type="submit" id="btnActionForm"
            class="btn btn-success btn-label right ms-auto nexttab nexttab"
            data-nexttab="steparrow-description-info-tab"><i
                class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i><span id="btnText">REGISTRAR</span></button>
    </div>


</form>