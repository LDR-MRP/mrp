<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Generación de QR por Producto</h4>
                        </div>
                        <div class="row g-3">

                            <div class="col-md-3">
                                <label>Clave Desde</label>
                                <input type="text" id="claveDesde" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <label>Clave Hasta</label>
                                <input type="text" id="claveHasta" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <label>No. etiquetas por producto</label>
                                <input type="number" id="numEtiquetas" class="form-control" value="1">
                            </div>

                            <div class="col-md-3">
                                <label>Línea</label>
                                <select id="lineaProducto" class="form-select">
                                    <option value="">Todas</option>
                                    <?php foreach ($data['lineas'] as $linea) { ?>
                                        <option value="<?= $linea['idlineaproducto'] ?>">
                                            <?= $linea['descripcion'] ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                        </div>
                        <hr>

                        <button class="btn btn-dark mt-3" onclick="generarEtiquetasMasivas()">
                            Generar Etiquetas
                        </button>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="<?= media(); ?>/js/functions_inv_reportes.js"></script>
<?php footerAdmin($data); ?>