<?php headerAdmin($data); ?>
<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="ri-user-shared-line"></i> <?= $data['page_title']; ?></h1>
      <p>Administración de Empresas Trasladistas</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="ri-home-line"></i></li>
      <li class="breadcrumb-item">Logística</li>
      <li class="breadcrumb-item active"><a href="<?= base_url(); ?>/prv_trasladistas">Trasladistas</a></li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>Catálogo de Trasladistas</h5>
            <button class="btn btn-primary" type="button" onclick="openModal();"><i class="ri-add-line"></i> Nuevo Trasladista</button>
          </div>
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="tableTrasladistas">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>RFC</th>
                  <th>Razón Social</th>
                  <th>Nombre Comercial</th>
                  <th>Madrinas</th>
                  <th>Choferes</th>
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

<!-- Modal Formulario Trasladista -->
<div class="modal fade" id="modalFormTrasladista" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header headerRegister">
        <h5 class="modal-title" id="titleModal">Nuevo Trasladista</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formTrasladista" name="formTrasladista">
          <input type="hidden" id="id_proveedor" name="id_proveedor" value="">
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="rfc" class="form-label">RFC <span class="text-danger">*</span></label>
              <input type="text" class="form-control text-uppercase" id="rfc" name="rfc" required placeholder="ej. LOG120304ABC">
            </div>

            <div class="col-md-6 mb-3">
              <label for="id_tipo_persona" class="form-label">Tipo de Persona</label>
              <select class="form-select" id="id_tipo_persona" name="id_tipo_persona">
                <option value="M">Persona Moral</option>
                <option value="F">Persona Física</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label for="razon_social" class="form-label">Razón Social <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="razon_social" name="razon_social" required placeholder="Razón Social Completa">
          </div>

          <div class="mb-3">
            <label for="nombre_comercial" class="form-label">Nombre Comercial <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="nombre_comercial" name="nombre_comercial" required placeholder="Nombre Comercial o Marca">
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="tipo" class="form-label">Tipo</label>
              <select class="form-select" id="tipo" name="tipo">
                <option value="Externo">Externo</option>
                <option value="Interno">Interno</option>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label for="origen" class="form-label">Origen</label>
              <select class="form-select" id="origen" name="origen">
                <option value="Nacional">Nacional</option>
                <option value="Extranjero">Extranjero</option>
              </select>
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
