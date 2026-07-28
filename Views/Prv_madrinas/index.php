<?php headerAdmin($data); ?>
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="ri-truck-fill"></i> <?= $data['page_title']; ?></h1>
      <p>Administración de Unidades Madrina por Empresa Trasladista</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="ri-home-line"></i></li>
      <li class="breadcrumb-item">Logística</li>
      <li class="breadcrumb-item active"><a href="<?= base_url(); ?>/prv_madrinas">Madrinas</a></li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>Catálogo de Madrinas</h5>
            <button class="btn btn-primary" type="button" onclick="openModal();"><i class="ri-add-line"></i> Nueva Madrina</button>
          </div>
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="tableMadrinas">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Trasladista</th>
                  <th>No. Económico</th>
                  <th>Placas</th>
                  <th>Marca / Modelo</th>
                  <th>Capacidad</th>
                  <th>Estatus</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Modal Formulario Madrina -->
<div class="modal fade" id="modalFormMadrina" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header headerRegister">
        <h5 class="modal-title" id="titleModal">Nueva Madrina</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formMadrina" name="formMadrina">
          <input type="hidden" id="id_madrina" name="id_madrina" value="">
          
          <div class="mb-3">
            <label for="id_proveedor" class="form-label">Empresa Trasladista <span class="text-danger">*</span></label>
            <select class="form-select" id="id_proveedor" name="id_proveedor" required>
              <option value="">-- Seleccionar Trasladista --</option>
              <?php foreach ($data['trasladistas'] as $t) { ?>
                <option value="<?= $t['id_proveedor']; ?>"><?= $t['razon_social']; ?> (<?= $t['rfc']; ?>)</option>
              <?php } ?>
            </select>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="numero_economico" class="form-label">Número Económico <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="numero_economico" name="numero_economico" required placeholder="ej. M-101">
            </div>

            <div class="col-md-6 mb-3">
              <label for="placas" class="form-label">Placas <span class="text-danger">*</span></label>
              <input type="text" class="form-control text-uppercase" id="placas" name="placas" required placeholder="ej. 64-AA-1B">
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="marca" class="form-label">Marca</label>
              <input type="text" class="form-control" id="marca" name="marca" placeholder="ej. Freightliner">
            </div>

            <div class="col-md-4 mb-3">
              <label for="modelo" class="form-label">Modelo</label>
              <input type="text" class="form-control" id="modelo" name="modelo" placeholder="ej. Cascadia 2024">
            </div>

            <div class="col-md-4 mb-3">
              <label for="capacidad_vehiculos" class="form-label">Capacidad de Vehículos</label>
              <input type="number" class="form-control" id="capacidad_vehiculos" name="capacidad_vehiculos" value="8" min="1" max="20">
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary" id="btnActionForm"><span id="btnText">Guardar</span></button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php footerAdmin($data); ?>
