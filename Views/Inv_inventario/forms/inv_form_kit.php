<form id="formInventarioKit" autocomplete="off" class="form-steps was-validated" autocomplete="off">
    <input type="hidden" id="idinventario" name="idinventario">
    <input type="hidden" name="tipo_elemento" value="K">
    <div class="row">

        <!-- CLAVE -->
        <div class="col-lg-4 col-sm-6">
            <div class="mb-3">
                <label class="form-label" for="cve_articulo">CLAVE</label>
                <div class="input-group mb-3">
                    <span class="input-group-text" id="clave-inventario-addon">Calve</span>
                    <input type="text" class="form-control"
                        placeholder="Ingresa la clave" id="cve_articulo" name="cve_articulo"
                        aria-describedby="clave-inventario-addon" required>
                    <div class="invalid-feedback">El campo clave es obligatorio</div>
                </div>
            </div>
        </div>

        <!-- LINEA DE PRODUCTO -->
        <div class="col-lg-4 col-sm-6">
            <div class="mb-3">
                <label class="form-label" for="lineaproductoid_kit">LÍNEA DE PRODUCTO</label>
                <div class="input-group mb-3">
                    <span class="input-group-text" id="nombre-linea-addon">Lin. Prod.</span>
                    <select class="form-control" id="lineaproductoid_kit" name="lineaproductoid" required></select>
                    <div class="invalid-feedback">El campo de precios es obligatorio</div>
                </div>
            </div>
        </div>

        <!-- UNIDAD DE ENTRADA -->
        <div class="col-lg-4 col-sm-6">
            <div class="mb-3">
                <label class="form-label" for="unidad_entrada">UNIDAD DE ENTRADA</label>
                <div class="input-group has-validation mb-3">
                    <span class="input-group-text" id="unidad-entrada-addon">Un. Ent.</span>
                    <select class="form-select" id="unidad_entrada" name="unidad_entrada"
                        aria-describedby="unidad-entrada-addon" required>
                        <option value="1">Pieza</option>
                        <option value="2">Kilogramo</option>
                        <option value="3">Litro</option>
                        <option value="4">Caja</option>
                    </select>
                    <div class="invalid-feedback">El campo estado es obligatorio</div>
                </div>
            </div>
        </div>

        <!-- UNIDAD DE SALIDA -->
        <div class="col-lg-4 col-sm-6">
            <div class="mb-3">
                <label class="form-label" for="unidad_salida">UNIDAD DE SALIDA</label>
                <div class="input-group has-validation mb-3">
                    <span class="input-group-text" id="unidad-salida-addon">Un. Sal.</span>
                    <select class="form-select" id="unidad_salida" name="unidad_salida"
                        aria-describedby="unidad-salida-addon" required>
                        <option value="1">Pieza</option>
                        <option value="2">Kilogramo</option>
                        <option value="3">Litro</option>
                        <option value="4">Caja</option>
                    </select>
                    <div class="invalid-feedback">El campo estado es obligatorio</div>
                </div>
            </div>
        </div>

        <!-- PESO -->
        <div class="col-lg-4 col-sm-6">
            <div class="mb-3">
                <label class="form-label" for="peso">PESO</label>
                <div class="input-group mb-3">
                    <span class="input-group-text" id="telefono-addon">Peso</span>
                    <input type="number" class="form-control"
                        placeholder="Ingresa el peso" id="peso" name="peso"
                        aria-describedby="telefono-addon" required>
                    <div class="invalid-feedback">El campo peso es obligatorio</div>
                </div>
            </div>
        </div>

        <!-- Estado -->
        <div class="col-lg-4 col-sm-6">
            <div class="mb-3">
                <label class="form-label" for="estado">ESTADO</label>
                <div class="input-group has-validation mb-3">
                    <span class="input-group-text" id="estado-addon">Est</span>
                    <select class="form-select" id="estado" name="estado"
                        aria-describedby="estado-addon" required>
                        <option value="2" selected>Activo</option>
                        <option value="1">Inactivo</option>
                    </select>
                    <div class="invalid-feedback">El campo estado es obligatorio</div>
                </div>
            </div>
        </div>

        <!-- DESCRIPCIÓN -->
        <div class="mb-3">
            <label class="form-label" for="descripcion">DESCRIPCIÓN</label>
            <div class="input-group mb-3">
                <span class="input-group-text" id="descripcion-inventario-addon">Desc.</span>
                <textarea class="form-control" id="descripcion" name="descripcion"
                    placeholder="Ingresa una descripción" rows="3"
                    aria-describedby="descripcion-inventario-addon"></textarea>
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