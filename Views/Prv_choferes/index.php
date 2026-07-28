<?php headerAdmin($data); ?>
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="ri-steering-2-line"></i> <?= $data['page_title']; ?></h1>
      <p>Administración de Operadores / Conductores por Empresa Trasladista</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="ri-home-line"></i></li>
      <li class="breadcrumb-item">Logística</li>
      <li class="breadcrumb-item active"><a href="<?= base_url(); ?>/prv_choferes">Choferes</a></li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>Catálogo de Choferes</h5>
            <button class="btn btn-primary" type="button" onclick="openModal();"><i class="ri-add-line"></i> Nuevo Chofer</button>
          </div>
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="tableChoferes">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Trasladista</th>
                  <th>Nombre Completo</th>
                  <th>No. Licencia</th>
                  <th>Tipo Licencia</th>
                  <th>Vigencia</th>
                  <th>Teléfono</th>
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

<!-- Modal Formulario Chofer -->
<div class="modal fade" id="modalFormChofer" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header headerRegister">
        <h5 class="modal-title" id="titleModal">Nuevo Chofer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formChofer" name="formChofer">
          <input type="hidden" id="id_chofer" name="id_chofer" value="">
          
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
              <label for="nombre" class="form-label">Nombre(s) <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="nombre" name="nombre" required placeholder="ej. Juan Carlos">
            </div>

            <div class="col-md-6 mb-3">
              <label for="apellidos" class="form-label">Apellidos <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="apellidos" name="apellidos" required placeholder="ej. Pérez Gómez">
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="num_licencia" class="form-label">Número de Licencia <span class="text-danger">*</span></label>
              <input type="text" class="form-control text-uppercase" id="num_licencia" name="num_licencia" required placeholder="ej. LIC-884920">
            </div>

            <div class="col-md-4 mb-3">
              <label for="tipo_licencia" class="form-label">Tipo de Licencia</label>
              <select class="form-select" id="tipo_licencia" name="tipo_licencia">
                <option value="A">Tipo A (Particular)</option>
                <option value="B">Tipo B (Carga)</option>
                <option value="C">Tipo C (Pesado / Articulado)</option>
                <option value="E">Tipo E (Federal Carga)</option>
              </select>
            </div>

            <div class="col-md-4 mb-3">
              <label for="vigencia_licencia" class="form-label">Vigencia Licencia</label>
              <input type="date" class="form-control" id="vigencia_licencia" name="vigencia_licencia">
            </div>
          </div>

          <div class="mb-3">
            <label for="telefono" class="form-label">Teléfono de Contacto</label>
            <input type="text" class="form-control" id="telefono" name="telefono" placeholder="ej. 55 1234 5678">
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
