<!-- Modal -->
<!-- <div class="modal fade" id="modalFormUsuario" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" >
    <div class="modal-content">
      <div class="modal-header headerRegister">
        <h5 class="modal-title" id="titleModal">Nuevo Usuario</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
            <form id="formUsuario" name="formUsuario" class="form-horizontal">
              <input type="hidden" id="idUsuario" name="idUsuario" value="">
              <p class="text-primary">Todos los campos son obligatorios.</p>

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="txtIdentificacion">Identificación</label>
                  <input type="text" class="form-control" id="txtIdentificacion" name="txtIdentificacion" required="">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="txtNombre">Nombres</label>
                  <input type="text" class="form-control valid validText" id="txtNombre" name="txtNombre" required="">
                </div>
                <div class="form-group col-md-6">
                  <label for="txtApellido">Apellidos</label>
                  <input type="text" class="form-control valid validText" id="txtApellido" name="txtApellido" required="">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="txtTelefono">Teléfono</label>
                  <input type="text" class="form-control valid validNumber" id="txtTelefono" name="txtTelefono" required="" onkeypress="return controlTag(event);">
                </div>
                <div class="form-group col-md-6">
                  <label for="txtEmail">Email</label>
                  <input type="email" class="form-control valid validEmail" id="txtEmail" name="txtEmail" required="">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="listRolid">Tipo usuario</label>
                    <select class="form-control" data-live-search="true" id="listRolid" name="listRolid" required >
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label for="listStatus">Status</label>
                    <select class="form-control selectpicker" id="listStatus" name="listStatus" required >
                        <option value="1">Activo</option>
                        <option value="2">Inactivo</option>
                    </select>
                </div>
             </div>
             <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="txtPassword">Password</label>
                  <input type="password" class="form-control" id="txtPassword" name="txtPassword" >
                </div>
             </div>
              <div class="tile-footer">
                <button id="btnActionForm" class="btn btn-primary" type="submit"><i class="fa fa-fw fa-lg fa-check-circle"></i><span id="btnText">Guardar</span></button>&nbsp;&nbsp;&nbsp;
                <button class="btn btn-danger" type="button" data-dismiss="modal"><i class="fa fa-fw fa-lg fa-times-circle"></i>Cerrar</button>
              </div>
            </form>
      </div>
    </div>
  </div>
</div> -->


<div class="modal fade" id="modalFormUsuario" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0">
      <div class="modal-header bg-primary-subtle p-3">
        <h5 class="modal-title" id="titleModal"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="close-modal"></button>
      </div>
     <form id="formUsuario" name="formUsuario" class="form-horizontal" autocomplete="off">
  <input type="hidden" id="idUsuario" name="idUsuario" value="">
  <div class="modal-body">
    <input type="hidden" id="id-field" />
    <div class="row g-3">
      <div class="col-lg-6">
        <div>
          <label for="owner-field" class="form-label">Nombres</label>
          <input type="text" class="form-control valid validText" id="txtNombre" name="txtNombre" required="">
        </div>
      </div>

      <div class="col-lg-6">
        <div>
          <label for="industry_type-field" class="form-label">Apellidos</label>
          <input type="text" class="form-control valid validText" id="txtApellido" name="txtApellido" required="">
        </div>
      </div>

      <div class="col-lg-4">
        <div>
          <label for="star_value-field" class="form-label">Teléfono</label>
          <input type="text" id="txtTelefono" name="txtTelefono" class="form-control" required />
        </div>
      </div>

      <div class="col-lg-4">
        <div>
          <label for="location-field" class="form-label">Email</label>
          <input type="email" id="txtEmail" name="txtEmail" class="form-control" required />
        </div>
      </div>

      <div class="col-lg-4">
        <div>
          <label for="employee-field" class="form-label">Estatus</label>
          <select class="form-control selectpicker" id="listStatus" name="listStatus" required>
            <option value="1">Activo</option>
            <option value="2">Inactivo</option>
          </select>
        </div>
      </div>

      <div class="col-lg-6">
        <div>
          <label for="website-field" class="form-label">Tipo de usuario</label>
          <select class="form-control" data-live-search="true" id="listRolid" name="listRolid" required>
          </select>
        </div>
      </div>

      <!-- PASSWORD + BOTÓN GENERAR -->
      <div class="col-lg-6">
        <div>
          <label for="contact_email-field" class="form-label">Password</label>
          <div class="input-group">
            <input type="text" id="txtPassword" name="txtPassword" class="form-control" placeholder="******" required />
            <button type="button" class="btn btn-outline-warning" id="btnGenerarPass">
              Generar
            </button>
          </div>
          <small class="form-text text-muted">
            Se generará una contraseña aleatoria de 15 caracteres.
          </small>
        </div>
      </div>

      <!-- CHECKBOX ENVIAR POR CORREO -->
      <div class="col-lg-12">
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" id="chkEnviarPass" name="chkEnviarPass" value="1">
          <label class="form-check-label" for="chkEnviarPass">
            Enviar los accesos al usuario (incluye enlace y credenciales de ingreso).
          </label>
        </div>
      </div>

    </div>
  </div>

  <div class="modal-footer">
    <div class="hstack gap-2 justify-content-end">
      <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
      <button type="submit" id="btnActionForm" class="btn btn-success">
        <span id="btnText">Guardar</span>
      </button>
    </div>
  </div>
</form>

    </div>
  </div>
</div>




<!-- Modal -->
<div class="modal fade" id="modalViewUser" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header header-primary">
        <h5 class="modal-title" id="titleModal">Datos del usuario</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered">
          <tbody>
            <tr>
              <td>Identificación:</td>
              <td id="celIdentificacion">654654654</td>
            </tr>
            <tr>
              <td>Nombres:</td>
              <td id="celNombre">Jacob</td>
            </tr>
            <tr>
              <td>Apellidos:</td>
              <td id="celApellido">Jacob</td>
            </tr>
            <tr>
              <td>Teléfono:</td>
              <td id="celTelefono">Larry</td>
            </tr>
            <tr>
              <td>Email (Usuario):</td>
              <td id="celEmail">Larry</td>
            </tr>
            <tr>
              <td>Tipo Usuario:</td>
              <td id="celTipoUsuario">Larry</td>
            </tr>
            <tr>
              <td>Estado:</td>
              <td id="celEstado">Larry</td>
            </tr>
            <tr>
              <td>Fecha registro:</td>
              <td id="celFechaRegistro">Larry</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>