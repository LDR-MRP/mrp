<?php headerAdmin($data); ?>
<div id="contentAjax"></div>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0"><?= $data['page_title'] ?></h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">MRP</a></li>
                                <li class="breadcrumb-item active"><?= $data['page_tag'] ?></li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>




            <div class="card">

                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Listados</h5>
                </div>

                <ul class="nav nav-tabs mb-3" id="tabsSustitutos">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabListas">Listas</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabProductos">Productos por lista</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabMatriz">Mover productos entre listas</a></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tabListas">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Listados</h5>
                            <button class="btn btn-primary" id="btnNuevaLista">+ Nueva Lista</button>
                        </div>
                        <table class="table table-bordered" id="tableListas">
                            <thead>
                                <tr>
                                    <th>CLAVE</th>
                                    <th>NOMBRE LISTA</th>
                                    <th>ESTADO</th>
                                    <th>ACCIONES</th>
                                </tr>
                            </thead>
                        </table>
                    </div>

                    <div class="tab-pane fade" id="tabProductos">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Lista</label>
                                <select id="listaSelector" class="form-control"></select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Tipo de producto</label>
                                <select id="tipoProducto" class="form-control">
                                    <option value="">Selecciona tipo</option>
                                    <option value="C">Componente</option>
                                    <option value="H">Herramienta</option>
                                    <option value="R">Refacción</option>
                                </select>
                            </div>
                        </div>

                        <hr>

                        <div id="productosContainer">
                            <div class="row mb-2 producto-item">
                                <div class="col-md-12">
                                    <label class="form-label">Producto</label>
                                    <input type="text" class="form-control productoPredictivo" placeholder="Buscar producto...">
                                    <input type="hidden" class="productoId">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="mt-3 d-flex">
                                <button type="button" class="btn btn-success me-2" id="btnGuardarProductos">
                                    Guardar productos
                                </button>

                                <button type="button" class="btn btn-info me-2" id="btnAddInputProducto">
                                    + Agregar otro producto
                                </button>

                                <button class="btn btn-secondary" id="btnLimpiarProductos">Limpiar</button>
                            </div>
                        </div>

                        <hr>

                        <table class="table table-bordered" id="tableProductosLista">
                            <thead>
                                <tr>
                                    <th>Clave</th>
                                    <th>Descripción</th>
                                    <th>Tipo</th>
                                    <th>Fecha</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                        </table>
                    </div>

                    <div class="tab-pane fade" id="tabMatriz">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Lista origen</label>
                                        <select id="listaOrigen" class="form-control"></select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Lista destino</label>
                                        <select id="listaDestino" class="form-control"></select>
                                    </div>
                                </div>

                                <div class="row align-items-center">
                                    <div class="col-md-5">
                                        <label class="form-label fw-bold">Productos origen</label>
                                        <select id="productosOrigen" class="form-control" multiple style="height: 320px;"></select>
                                    </div>

                                    <div class="col-md-2 text-center">
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-outline-primary" id="btnMoverDerecha">
                                                <i data-feather="arrow-right"></i>
                                            </button>
                                            <button class="btn btn-outline-primary" id="btnMoverIzquierda">
                                                <i data-feather="arrow-left"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-md-5">
                                        <label class="form-label fw-bold">Productos destino</label>
                                        <select id="productosDestino" class="form-control" multiple style="height: 320px;"></select>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<?php footerAdmin($data); ?>