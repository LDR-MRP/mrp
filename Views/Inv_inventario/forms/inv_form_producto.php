<form id="formInventarioProducto" autocomplete="off" class="form-steps was-validated" autocomplete="off">
    <input type="hidden" id="idinventario" name="idinventario">
    <div class="container d-flex justify-content-center align-items-center p-3 mb-3 rounded">

        <!-- PRODUCTO -->
        <div class="col-md-4 form-check d-flex align-items-center">
            <input class="form-check-input" type="radio"
                name="tipo_elemento" id="producto" value="P" checked>
            <label class="form-label fw-semibold ms-2" for="producto">
                PRODUCTO
            </label>
        </div>

        <!-- COMPONENTE -->
        <div class="col-md-4 form-check d-flex align-items-center">
            <input class="form-check-input" type="radio"
                name="tipo_elemento" id="componente" value="C">
            <label class="form-label fw-semibold ms-2" for="componente">
                COMPONENTE
            </label>
        </div>

        <!-- HERRAMIENTA -->
        <div class="col-md-4 form-check d-flex align-items-center">
            <input class="form-check-input" type="radio"
                name="tipo_elemento" id="herramienta" value="H">
            <label class="form-label fw-semibold ms-2" for="herramienta">
                HERRAMIENTA
            </label>
        </div>
    </div>

    <div class="row">


        <!-- CLAVE -->
        <div class="col-lg-4 col-sm-6">
            <div class="mb-3">
                <label class="form-label" for="cve_articulo">CLAVE</label>
                <div class="input-group mb-3">
                    <span class="input-group-text" id="clave-inventario-addon">Clave</span>
                    <input type="text" class="form-control"
                        placeholder="Ingresa la clave" id="cve_articulo" name="cve_articulo"
                        aria-describedby="clave-inventario-addon" required>
                    <div class="invalid-feedback">El campo clave es obligatorio</div>
                </div>
            </div>
        </div>

        <!-- CLAVE ALTERNA -->
        <div class="col-lg-4 col-sm-6">
            <div class="mb-3">
                <label class="form-label" for="clave_alterna">CLAVE ALTERNA</label>
                <div class="input-group mb-3">
                    <span class="input-group-text" id="clave_alterna_addon">Cve. Alt.</span>
                    <input type="text" class="form-control"
                        placeholder="Ingresa la clave" id="clave_alterna" name="clave_alterna"
                        aria-describedby="clave_alterna_addon" disabled>
                    <button type="button" class="btn btn-warning" id="btn_habilitar_clave_alterna">
                        <i class="bi bi-unlock"></i>
                    </button>
                    <div class="invalid-feedback">El campo clave es obligatorio</div>
                </div>
            </div>

            <!-- Select oculto (sirve para seleccionar y asociar a que tipo se le esta dando la clave alterna Cliente, Proveedor o Interna)-->
            <div id="tipo_asignacion_container" class="mt-2" style="display: none;">
                <label for="tipo_asignacion" class="form-label fw-semibold">
                    <i class="bi bi-diagram-3 me-1 text-primary"></i> Tipo de asignación
                </label>
                <select class="form-select" id="tipo_asignacion" name="tipo_asignacion">
                    <option value="">Seleccione...</option>
                    <option value="C">Cliente</option>
                    <option value="V">Proveedor</option>
                    <option value="I">Interna</option>
                </select>
            </div>
        </div>

        <!-- LISTA LINEAS DE PRODUCTO -->
        <div class="col-lg-4 col-sm-6">
            <div class="mb-3">
                <label class="form-label" for="lineaproductoid_producto">LÍNEA DE PRODUCTO</label>
                <div class="input-group mb-3">
                    <span class="input-group-text" id="nombre-linea-addon">Lín. Prod.</span>
                    <select class="form-control" id="lineaproductoid_producto" name="lineaproductoid"></select>
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
                    aria-describedby="descripcion-inventario-addon" required></textarea>
                <div class="invalid-feedback">El campo descripción es obligatorio</div>

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
                        <option value="PIEZA">Pieza</option>
                        <option value="KILOGRAMO">Kilogramo</option>
                        <option value="LITRO">Litro</option>
                        <option value="CAJA">Caja</option>
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
                        <option value="PIEZA">Pieza</option>
                        <option value="KILOGRAMO">Kilogramo</option>
                        <option value="LITRO">Litro</option>
                        <option value="CAJA">Caja</option>
                    </select>
                    <div class="invalid-feedback">El campo estado es obligatorio</div>
                </div>
            </div>
        </div>

        <!-- UBICACIÓN -->
        <div class="col-lg-4 col-sm-6">
            <div class="mb-3">
                <label class="form-label" for="ubicacion">UBICACIÓN</label>
                <div class="input-group mb-3">
                    <span class="input-group-text" id="ubicacion-addon">Ub.</span>
                    <input type="text" class="form-control"
                        placeholder="Ingresa la ubicación" id="ubicacion" name="ubicacion"
                        aria-describedby="ubicacion-addon" required>
                    <div class="invalid-feedback">El campo ubicación es obligatorio</div>
                </div>
            </div>
        </div>

        <!-- FACTOR ENTRE UNIDADES -->
        <div class="col-lg-4 col-sm-6">
            <div class="mb-3">
                <label class="form-label" for="factor_unidades">FACTOR ENTRE UNIDADES</label>
                <div class="input-group mb-3">
                    <span class="input-group-text" id="factor-unidades-addon">Fac. Un</span>
                    <input type="number" class="form-control"
                        placeholder="Ingresa el factor entre unidades" id="factor_unidades" name="factor_unidades"
                        aria-describedby="factor-unidades-addon" required>
                    <div class="invalid-feedback">El campo factor entre unidades es obligatorio</div>
                </div>
            </div>
        </div>

        <!-- TIEMPO DE SURTIDO -->
        <div class="col-lg-4 col-sm-6">
            <div class="mb-3">
                <label class="form-label" for="tiempo_surtido">TIEMPO DE SURTIDO</label>
                <div class="input-group mb-3">
                    <span class="input-group-text" id="tiempo-surtido-addon">Tiem. Sur</span>
                    <input type="number" class="form-control"
                        placeholder="Ingresa el tiempo de surtido" id="tiempo_surtido" name="tiempo_surtido"
                        aria-describedby="tiempo-surtido-addon" required>
                    <div class="invalid-feedback">El campo tiempo de surtido es obligatorio</div>
                </div>
            </div>
        </div>

        <!-- VOLUMEN -->
        <div class="col-lg-4 col-sm-6">
            <div class="mb-3">
                <label class="form-label" for="volumen">VOLUMEN</label>
                <div class="input-group mb-3">
                    <span class="input-group-text" id="volumen-addon">Vol.</span>
                    <input type="number" class="form-control"
                        placeholder="Ingresa el volumen" id="volumen" name="volumen"
                        aria-describedby="volumen-addon" required>
                    <div class="invalid-feedback">El campo volumen es obligatorio</div>
                </div>
            </div>
        </div>

        <!-- PESO -->
        <div class="col-lg-4 col-sm-6">
            <div class="mb-3">
                <label class="form-label" for="peso">PESO</label>
                <div class="input-group mb-3">
                    <span class="input-group-text" id="peso-addon">Peso</span>
                    <input type="number" class="form-control"
                        placeholder="Ingresa el peso" id="peso" name="peso"
                        aria-describedby="peso-addon" required>
                    <div class="invalid-feedback">El campo peso es obligatorio</div>
                </div>
            </div>
        </div>

        <!-- UNIDAD DE EMPAQUE -->
        <div class="col-lg-4 col-sm-6">
            <div class="mb-3">
                <label class="form-label" for="unidad_empaque">UNIDAD DE EMPAQUE</label>
                <div class="input-group mb-3">
                    <span class="input-group-text" id="unidad_empaque-addon">Unidad</span>
                    <input type="number" class="form-control"
                        placeholder="Ingresa la unidad de empaque" id="unidad_empaque" name="unidad_empaque"
                        aria-describedby="unidad_empaque-addon" required>
                    <div class="invalid-feedback">El campo unidad de empaque es obligatorio</div>
                </div>
            </div>
        </div>

        <!-- ULTIMO COSTO -->
        <div class="col-lg-4 col-sm-6">
            <div class="mb-3">
                <label class="form-label" for="ultimo_costo">ULTIMO COSTO</label>
                <div class="input-group mb-3">
                    <span class="input-group-text" id="ultimo_costo-addon">Costo</span>
                    <input type="number" class="form-control"
                        placeholder="Ingresa el ultimo costo" id="ultimo_costo" name="ultimo_costo"
                        aria-describedby="ultimo_costo-addon" required>
                    <div class="invalid-feedback">El campo ultimo costo es obligatorio</div>
                </div>
            </div>
        </div>

        <!-- SELECT IMPUESTO -->
        <div class="col-lg-4 col-sm-6">
            <div class="mb-3">
                <label class="form-label" for="impuesto">IMPUESTO</label>
                <div class="input-group mb-3">
                    <span class="input-group-text" id="nombre-linea-addon">Imp.</span>
                    <select class="form-control" id="idimpuesto" name="idimpuesto"></select>
                </div>
            </div>
        </div>



        <!-- IMAGEN -->
        <div class="col-lg-12 col-sm-6">
            <div class="mb-3">
                <label class="form-label" for="imagen_producto-input">IMAGEN</label>
                <div class="input-group mb-3">
                    <span class="input-group-text" id="imagen_producto-addon">Img</span>
                    <input type="file" class="form-control" id="imagen_producto-input" name="imagen_producto" accept="image/*">
                    <div class="invalid-feedback">El campo clave es obligatorio</div>
                </div>
            </div>
        </div>

        <div class="container d-flex justify-content-center align-items-center p-3 mb-3 rounded">

            <!-- CHECK NÚMERO DE SERIE -->
            <div class="col-md-4 form-check d-flex align-items-center">
                <input type="hidden" name="serie" value="N">
                <label class="form-label fw-semibold" for="serie">
                    <i class="bi bi-check-circle me-1 text-primary"></i>
                    <input class="form-check-input ms-2" type="checkbox" value="S" id="serie" name="serie">
                    <span class="ms-2">NÚMERO DE SERIE</span>
                </label>
            </div>

            <!-- CHECK LOTES -->
            <div class="col-md-4 form-check d-flex align-items-center">
                <input type="hidden" name="lote" value="N">
                <label class="form-label fw-semibold" for="lote">
                    <i class="bi bi-check-circle me-1 text-primary"></i>
                    <input class="form-check-input ms-2" type="checkbox" value="S" id="lote" name="lote">
                    <span class="ms-2">LOTES</span>
                </label>
            </div>

            <!-- CHECK PEDIMENTOS ADUANALES -->
            <div class="col-md-4 form-check d-flex align-items-center">
                <input type="hidden" name="pedimiento" value="N">
                <label class="form-label fw-semibold" for="pedimiento">
                    <i class="bi bi-check-circle me-1 text-primary"></i>
                    <input class="form-check-input ms-2" type="checkbox" value="S" id="pedimiento" name="pedimiento">
                    <span class="ms-2">PEDIMENTOS ADUANALES</span>
                </label>
            </div>

        </div>


        <div class="row mb-3">
            <div class="col-lg-12 col-sm-12 ">
                <h5 class="mb-4">¿DESEAS AGREGAR EL PRODUCTO A UN ALMACÉN?</h5>
            </div>
        </div>

        <div class="row" id="bloqueMovimientoInicial">
            <!-- ALMACEN -->
            <div class="col-lg-4 col-sm-6">
                <div class="mb-3">
                    <label class="form-label" for="almacenid">ALMACÉN INICIAL</label>
                    <div class="input-group mb-3">
                        <span class="input-group-text" id="almacen-addon">Alm.</span>
                        <select class="form-control" id="almacenid" name="almacenid"></select>
                        <div class="invalid-feedback">El campo almacén es obligatorio</div>
                    </div>
                </div>
            </div>

            <!-- CANTIDAD -->
            <div class="col-lg-4 col-sm-6">
                <div class="mb-3">
                    <label class="form-label" for="cantidad_inicial">CANTIDAD INICIAL</label>
                    <div class="input-group mb-3">
                        <span class="input-group-text" id="cantidad_inicial-addon">Can.</span>
                        <input type="number" class="form-control"
                            placeholder="Ingresa la cantidad inicial" id="cantidad_inicial" name="cantidad_inicial"
                            aria-describedby="cantidad_inicial-addon">
                        <div class="invalid-feedback">El campo cantidad inicial es obligatorio</div>
                    </div>
                </div>
            </div>

            <!-- COSTO CANTIDAD -->
            <div class="col-lg-4 col-sm-6">
                <div class="mb-3">
                    <label class="form-label" for="costo">COSTO CANTIDAD</label>
                    <div class="input-group mb-3">
                        <span class="input-group-text" id="costo-addon">Costo</span>
                        <input type="number" class="form-control"
                            placeholder="Ingresa el costo" id="costo" name="costo"
                            aria-describedby="costo-addon">
                        <div class="invalid-feedback">El campo costo es obligatorio</div>
                    </div>
                </div>
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