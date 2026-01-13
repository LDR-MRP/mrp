let tableDistibuidores = null;
let rowTable = "";
let divLoading = document.querySelector("#divLoading");

// Inputs del formulario
const distribuidor = document.querySelector("#iddistribuidor");
const grupo_id = document.querySelector("#grupo_id");
const nombre_comercial = document.querySelector("#nombre-distribuidores-input");
const razon_social = document.querySelector("#razon-distribuidores-input");
const rfc = document.querySelector("#rfc-distribuidores-input");
const repve = document.querySelector("#repve-distribuidores-input");
const plaza = document.querySelector("#plaza-distribuidores-input");
const estatus = document.querySelector("#estatus-select");
const tipo_negocio = document.querySelector("#tipo_negocio-select");
const telefono = document.querySelector("#telefono-distribuidores-input");
const telefono_alt = document.querySelector("#telefono_alt-input");

const listModelos = document.querySelector("#listModelos");
const modelosDisponibles = document.querySelector("#modelosDisponibles");
const modelosSeleccionados = document.querySelector("#modelosSeleccionados");

const tipo = document.querySelector("#tipo-select");
const calle = document.querySelector("#calle-distribuidores-input");
const numero_ext = document.querySelector("#numero_ext-distribuidores-input");
const numero_int = document.querySelector("#numero_int-distribuidores-input");
const colonia = document.querySelector("#colonia-distribuidores-input");
const codigo_postal = document.querySelector(
  "#codigo_postal-distribuidores-input"
);

const estado = document.querySelector("#estado-select");

const selectPais = document.querySelector("#listPaises");
const selectEstado = document.querySelector("#listEstados");
const selectMunicipio = document.querySelector("#listMunicipios");

const selectPaisFiscal = document.querySelector("#listPaisesFiscal");
const selectEstadoFiscal = document.querySelector("#listEstadosFiscal");
const selectMunicipioFiscal = document.querySelector("#listMunicipiosFiscal");

// Mis referencias globales
let primerTab; // Tab LISTA
let firstTab; // Tab NUEVO/ACTUALIZAR
let tabNuevo;
let spanBtnText = null;
let formDistribuidores = null;

document.addEventListener(
  "DOMContentLoaded",
  function () {
    // --------------------------------------------------------------------
    //  REFERENCIAS DEL FORMULARIO
    // --------------------------------------------------------------------
    formDistribuidores = document.querySelector("#formDistribuidores");
    spanBtnText = document.querySelector("#btnText");

    fntGrupos();
    fntModelos();
    fntPaises(selectPais, selectEstado, selectMunicipio);
    fntPaises(selectPaisFiscal, selectEstadoFiscal, selectMunicipioFiscal);

    // --------------------------------------------------------------------
    //  DATATABLE DISTRIBUIDORES
    // --------------------------------------------------------------------
    tableDistibuidores = $("#cli_distribuidores").dataTable({
      aProcessing: true,
      aServerSide: true,
      ajax: {
        url: " " + base_url + "/cli_clientes/index",
        dataSrc: "",
      },
      columns: [
        { data: "id" },
        { data: "nombre_grupo" },
        { data: "tipo_negocio" },
        { data: "nombre_comercial" },
        { data: "razon_social" },
        { data: "plaza" },
        { data: "estado" },
        { data: "options" },
      ],
      dom: "lBfrtip",
      buttons: [],
      resonsieve: "true",
      bDestroy: true,
      iDisplayLength: 10,
      order: [[0, "desc"]],
    });

    // --------------------------------------------------------------------
    //  TABS BOOTSTRAP
    // --------------------------------------------------------------------
    const primerTabEl = document.querySelector(
      '#nav-tab a[href="#listdistribuidores"]'
    );
    const firstTabEl = document.querySelector(
      '#nav-tab a[href="#agregardistribuidores"]'
    );

    if (primerTabEl && firstTabEl && spanBtnText) {
      //  IMPORTANTE: NO usar "let" aquí, usamos las globales
      primerTab = new bootstrap.Tab(primerTabEl); // LISTA
      firstTab = new bootstrap.Tab(firstTabEl); // NUEVO / ACTUALIZAR
      tabNuevo = firstTabEl; // elemento del tab

      // ----------------------------------------------------------------
      //  CLICK EN "NUEVO" → MODO NUEVO
      // ----------------------------------------------------------------
      tabNuevo.addEventListener("click", () => {
        tabNuevo.textContent = "NUEVO";
        spanBtnText.textContent = "REGISTRAR";

        // Limpiar formulario
        formDistribuidores.reset();
        distribuidor.value = "";
        estado.value = "2";
      });

      // ----------------------------------------------------------------
      // CLICK EN "DISTRIBUIDORES" → RESETEAR NAV A NUEVO
      // ----------------------------------------------------------------
      primerTabEl.addEventListener("click", () => {
        tabNuevo.textContent = "NUEVO";
        spanBtnText.textContent = "REGISTRAR";
        distribuidor.value = "";
        estado.value = "2";
        formDistribuidores.reset();
      });
    } else {
      console.warn("Tabs de lineas no encontrados o btnText faltante.");
    }

    // --------------------------------------------------------------------
    // FORM → CREAR / ACTUALIZAR DISTRIBUIDOR
    // --------------------------------------------------------------------
    formDistribuidores.addEventListener("submit", function (e) {
      if (modelosSeleccionados.children.length === 0) {
        Swal.fire("Atención", "Selecciona al menos un modelo", "warning");
        return;
      }
      e.preventDefault();

      divLoading.style.display = "flex";

      let request = window.XMLHttpRequest
        ? new XMLHttpRequest()
        : new ActiveXObject("Microsoft.XMLHTTP");
      let ajaxUrl = base_url + "/cli_clientes/setDistribuidor/";
      let formData = new FormData(formDistribuidores);

      request.open("POST", ajaxUrl, true);
      request.send(formData);

      request.onreadystatechange = function () {
        if (request.readyState !== 4) return;

        divLoading.style.display = "none";

        if (request.status !== 200) {
          Swal.fire(
            "Error",
            "Ocurrió un error en el servidor. Inténtalo de nuevo.",
            "error"
          );
          return;
        }

        let objData = JSON.parse(request.responseText);

        if (objData.status) {
          if (objData.tipo == "insert") {
            Swal.fire({
              title: objData.msg,
              text: "¿Deseas ingresar un nuevo registro?",
              icon: "success",
              showCancelButton: true,
              confirmButtonText: "Sí",
              cancelButtonText: "No",
              confirmButtonColor: "#28a745",
              cancelButtonColor: "#dc3545",
              allowOutsideClick: false,
              allowEscapeKey: false,
            }).then((result) => {
              if (result.isConfirmed) {
                // Seguir en modo NUEVO
                formDistribuidores.reset();
                distribuidor.value = "";
                estado.value = "2";
                tabNuevo.textContent = "NUEVO";
                spanBtnText.textContent = "REGISTRAR";
                tableDistibuidores.api().ajax.reload();
              } else {
                // Regresar al listado
                formDistribuidores.reset();
                distribuidor.value = "";
                estado.value = "2";
                tabNuevo.textContent = "NUEVO";
                spanBtnText.textContent = "REGISTRAR";
                primerTab.show();
                tableDistibuidores.api().ajax.reload();
              }
            });
          } else {
            Swal.fire({
              title: objData.msg,
              icon: "success",
              confirmButtonText: "OK",
              confirmButtonColor: "#28a745",
              allowOutsideClick: false,
              allowEscapeKey: false,
            }).then(() => {
              // Acción final después de OK (opcional)
              formDistribuidores.reset();
              distribuidor.value = "";
              estado.value = "2";
              tabNuevo.textContent = "NUEVO";
              spanBtnText.textContent = "REGISTRAR";
              primerTab.show();
              tableDistibuidores.api().ajax.reload();
            });
          }
        } else {
          Swal.fire("Error", objData.msg, "error");
        }
      };
    });
  },
  false
);

function fntEditDistribuidor(iddistribuidor) {
  // Cambiar UI a modo editar
  tabNuevo.textContent = "ACTUALIZAR";
  spanBtnText.textContent = "ACTUALIZAR";

  let request = new XMLHttpRequest();
  let ajaxUrl = base_url + "/cli_clientes/show/" + iddistribuidor;
  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState !== 4 || request.status !== 200) return;

    let objData = JSON.parse(request.responseText);
    if (!objData.status) {
      Swal.fire("Error", objData.msg, "error");
      return;
    }

    // DATOS DEL DISTRIBUIDOR
    const data = objData.data;

    distribuidor.value = data.id;
    grupo_id.value = data.grupo_id;
    nombre_comercial.value = data.nombre_comercial;
    razon_social.value = data.razon_social;
    rfc.value = data.rfc;
    repve.value = data.repve;
    plaza.value = data.plaza;
    estatus.value = data.estatus;
    tipo_negocio.value = data.tipo_negocio;
    telefono.value = data.telefono;
    telefono_alt.value = data.telefono_alt;
    estado.value = data.estado;

    modelosDisponibles.innerHTML = "";
    modelosSeleccionados.innerHTML = "";

    Array.from(listModelos.options).forEach((opt) => {
      let li = document.createElement("li");
      li.className = "list-group-item";
      li.textContent = opt.text;
      li.dataset.id = opt.value;

      const seleccionado = data.modelos.some(
        (m) => String(m.idlineaproducto) === opt.value
      );

      if (seleccionado) {
        modelosSeleccionados.appendChild(li);
        opt.selected = true;
      } else {
        modelosDisponibles.appendChild(li);
      }
    });

    // DIRECCION
    const dir = data.direccion;

    tipo.value = dir.tipo;
    calle.value = dir.calle;
    numero_ext.value = dir.numero_ext;
    numero_int.value = dir.numero_int;
    colonia.value = dir.colonia;
    codigo_postal.value = dir.codigo_postal;

    selectPais.value = dir.pais_id;
    fntEstados(dir.pais_id, selectEstado, selectMunicipio, dir.estado_id);
    fntMunicipios(dir.estado_id, selectMunicipio, dir.municipio_id);

    // DIRECCION FISCAL
    const dirFiscal = data.direccion_fiscal;

    if (data.misma_direccion === true) {
      chkMisma.checked = true;
      wrapperFiscal.classList.add("d-none");
    } else {
      chkMisma.checked = false;
      wrapperFiscal.classList.remove("d-none");

      document.querySelector("#tipo_fiscal").value = dirFiscal.tipo;
      document.querySelector("#calle_fiscal").value = dirFiscal.calle;
      document.querySelector("#numero_ext_fiscal").value = dirFiscal.numero_ext;
      document.querySelector("#numero_int_fiscal").value = dirFiscal.numero_int;
      document.querySelector("#colonia_fiscal").value = dirFiscal.colonia;
      document.querySelector("#codigo_postal_fiscal").value =
        dirFiscal.codigo_postal;

      selectPaisFiscal.value = dirFiscal.pais_id;
      fntEstados(
        dirFiscal.pais_id,
        selectEstadoFiscal,
        selectMunicipioFiscal,
        dirFiscal.estado_id
      );
      fntMunicipios(
        dirFiscal.estado_id,
        selectMunicipioFiscal,
        dirFiscal.municipio_id
      );
    }

    firstTab.show();
  };
}

// ------------------------------------------------------------------------
//  ELIMINAR UN REGISTRO DEL LISTADO
// ------------------------------------------------------------------------
function fntDelDistribuidor(iddistribuidor) {
  Swal.fire({
    html: `
        <div class="mt-3">
            <lord-icon 
                src="https://cdn.lordicon.com/gsqxdxog.json" 
                trigger="loop" 
                colors="primary:#f7b84b,secondary:#f06548" 
                style="width:100px;height:100px">
            </lord-icon>

            <div class="mt-4 pt-2 fs-15 mx-5">
                <h4>Confirmar eliminación</h4>
                <p class="text-muted mx-4 mb-0">
                    ¿Estás seguro de que deseas eliminar este registro? 
                    Esta acción no se puede deshacer.
                </p>
            </div>
        </div>
    `,
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    customClass: {
      confirmButton: "btn btn-primary w-xs me-2 mb-1",
      cancelButton: "btn btn-danger w-xs mb-1",
    },
    buttonsStyling: false,
    showCloseButton: true,
  }).then((result) => {
    if (!result.isConfirmed) {
      return;
    }

    let request = window.XMLHttpRequest
      ? new XMLHttpRequest()
      : new ActiveXObject("Microsoft.XMLHTTP");
    let ajaxUrl = base_url + "/cli_clientes/destroy";
    let strData = "iddistribuidor=" + iddistribuidor;

    request.open("POST", ajaxUrl, true);
    request.setRequestHeader(
      "Content-type",
      "application/x-www-form-urlencoded"
    );
    request.send(strData);

    request.onreadystatechange = function () {
      if (request.readyState == 4 && request.status == 200) {
        let objData = JSON.parse(request.responseText);
        if (objData.status) {
          Swal.fire("¡Operación exitosa!", objData.msg, "success");
          tableDistibuidores.api().ajax.reload();
        } else {
          Swal.fire("Atención!", objData.msg, "error");
        }
      }
    };
  });
}

// ------------------------------------------------------------------------
//  VER EL DETALLE DEl DISTRIBUIDOR
// ------------------------------------------------------------------------
function fntViewDistribuidor(iddistribuidor) {
  let request = window.XMLHttpRequest
    ? new XMLHttpRequest()
    : new ActiveXObject("Microsoft.XMLHTTP");
  let ajaxUrl = base_url + "/cli_clientes/show/" + iddistribuidor;

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      let objData = JSON.parse(request.responseText);

      if (objData.status) {
        let estadoUsuario =
          objData.data.estado == 2
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-danger">Inactivo</span>';

        document.querySelector("#iddistri").innerHTML = objData.data.id;
        document.querySelector("#nombregrupo").innerHTML =
          objData.data.nombre_grupo;
        document.querySelector("#tiponegocio").innerHTML =
          objData.data.tipo_negocio;
        document.querySelector("#nombrecomercial").innerHTML =
          objData.data.nombre_comercial;
        document.querySelector("#razonsocial").innerHTML =
          objData.data.razon_social;
        document.querySelector("#plaza").innerHTML = objData.data.plaza;
        document.querySelector("#rfc").innerHTML = objData.data.rfc;
        document.querySelector("#repve").innerHTML = objData.data.repve;
        document.querySelector("#telefono").innerHTML = objData.data.telefono;
        document.querySelector("#telefonoalt").innerHTML =
          objData.data.telefono_alt;
        document.querySelector("#fecharegistro").innerHTML =
          objData.data.fecha_registro;
        document.querySelector("#celEstado").innerHTML = estadoUsuario;

        const direccion = objData.data.direccion;
        document.querySelector("#tipo").innerHTML = direccion.tipo;
        document.querySelector("#calle").innerHTML = direccion.calle;
        document.querySelector("#numero_ext").innerHTML = direccion.numero_ext;
        document.querySelector("#numero_int").innerHTML = direccion.numero_int;
        document.querySelector("#colonia").innerHTML = direccion.colonia;
        document.querySelector("#codigo_postal").innerHTML =
          direccion.codigo_postal;
        document.querySelector("#pais").innerHTML = direccion.pais;
        document.querySelector("#estado").innerHTML = direccion.estado;
        document.querySelector("#municipio").innerHTML = direccion.municipio;

        const direccionFiscal = objData.data.direccion_fiscal;
        document.querySelector("#tipofiscal").innerHTML = direccionFiscal.tipo;
        document.querySelector("#callefiscal").innerHTML =
          direccionFiscal.calle;
        document.querySelector("#numeroext_fiscal").innerHTML =
          direccionFiscal.numero_ext;
        document.querySelector("#numeroint_fiscal").innerHTML =
          direccionFiscal.numero_int;
        document.querySelector("#coloniafiscal").innerHTML =
          direccionFiscal.colonia;
        document.querySelector("#codigopostal_fiscal").innerHTML =
          direccionFiscal.codigo_postal;
        document.querySelector("#pais_fiscal").innerHTML = direccionFiscal.pais;
        document.querySelector("#estado_fiscal").innerHTML =
          direccionFiscal.estado;
        document.querySelector("#municipio_fiscal").innerHTML =
          direccionFiscal.municipio;

        let htmlContactos = "";
        let contactos = objData.data.contactos;

        if (contactos.length > 0) {
          contactos.forEach((contacto) => {
            htmlContactos += `
              <tr>
                <td>${contacto.puesto} 
                    <br/>
                    ${contacto.departamento}
                </td>
                <td>${contacto.nombre}</td>
                <td>${contacto.correo}</td>
                <td>${contacto.telefono}</td>
                <td>${contacto.estatus}</td>
                <td>${contacto.fecha_registro}</td>
              </tr>
            `;
          });
        } else {
          htmlContactos = `
            <tr>
              <td colspan="7" class="text-center">No hay contactos registrados</td>
            </tr>
          `;
        }

        document.querySelector("#tbodyContactos").innerHTML = htmlContactos;

        let htmlModelos = "";
        let modelos = objData.data.modelos;

        if (modelos.length > 0) {
          modelos.forEach((modelo) => {
            htmlModelos += `
              <tr>
                <td>${modelo.idlineaproducto}</td>
                <td>${modelo.cve_linea_producto}</td>
                <td>${modelo.descripcion}</td>
              </tr>
            `;
          });
        } else {
          htmlModelos = `
            <tr>
              <td colspan="3" class="text-center">No hay modelos registrados</td>
            </tr>
          `;
        }

        document.querySelector("#tbodyModelos").innerHTML = htmlModelos;

        $("#modalViewDistribuidor").modal("show");
      } else {
        Swal.fire("Error", objData.msg, "error");
      }
    }
  };
}

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE GRUPOS
// ------------------------------------------------------------------------
function fntGrupos(selectedValue = "") {
  if (document.querySelector("#grupo_id")) {
    let ajaxUrl = base_url + "/cli_clientes/getSelectGrupos";
    let request = window.XMLHttpRequest
      ? new XMLHttpRequest()
      : new ActiveXObject("Microsoft.XMLHTTP");
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
      if (request.readyState == 4 && request.status == 200) {
        document.querySelector("#grupo_id").innerHTML = request.responseText;

        if (selectedValue !== "") {
          document.querySelector("#grupo_id").value = selectedValue;
        }
      }
    };
  }
}

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE PAISES
// ------------------------------------------------------------------------
function fntPaises(selectPais, selectEstado, selectMunicipio, selected = "") {
  let ajaxUrl = base_url + "/cli_clientes/getSelectPaises";
  let request = new XMLHttpRequest();

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState === 4 && request.status === 200) {
      selectPais.innerHTML = request.responseText;
      if (selected) selectPais.value = selected;
    }
  };

  selectPais.onchange = function () {
    fntEstados(this.value, selectEstado, selectMunicipio);
    selectMunicipio.innerHTML =
      '<option value="">--Seleccione municipio--</option>';
  };
}

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE ESTADOS
// ------------------------------------------------------------------------
function fntEstados(pais_id, selectEstado, selectMunicipio, selected = "") {
  if (!pais_id) {
    selectEstado.innerHTML = '<option value="">--Seleccione estado--</option>';
    return;
  }

  let ajaxUrl = base_url + "/cli_clientes/getSelectEstados/" + pais_id;
  let request = new XMLHttpRequest();

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState === 4 && request.status === 200) {
      selectEstado.innerHTML = request.responseText;
      if (selected) selectEstado.value = selected;
    }
  };

  selectEstado.onchange = function () {
    fntMunicipios(this.value, selectMunicipio);
  };
}

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE MUNICIPIOS
// ------------------------------------------------------------------------
function fntMunicipios(estado_id, selectMunicipio, selected = "") {
  if (!estado_id) {
    selectMunicipio.innerHTML =
      '<option value="">--Seleccione municipio--</option>';
    return;
  }

  let ajaxUrl = base_url + "/cli_clientes/getSelectMunicipios/" + estado_id;
  let request = new XMLHttpRequest();

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState === 4 && request.status === 200) {
      selectMunicipio.innerHTML = request.responseText;
      if (selected) selectMunicipio.value = selected;
    }
  };
}

const chkMisma = document.querySelector("#mismaDireccion");
const wrapperFiscal = document.querySelector("#direccionFiscalWrapper");

function toggleDireccionFiscal() {
  const inputs = wrapperFiscal.querySelectorAll("input, select");

  if (chkMisma.checked) {
    wrapperFiscal.classList.add("d-none");

    inputs.forEach((el) => {
      el.required = false;
      el.value = "";
    });
  } else {
    wrapperFiscal.classList.remove("d-none");

    inputs.forEach((el) => {
      el.required = true;
    });
  }
}

chkMisma.addEventListener("change", toggleDireccionFiscal);

document.addEventListener("DOMContentLoaded", toggleDireccionFiscal);

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE MODELOS
// ------------------------------------------------------------------------
function fntModelos() {
  let ajaxUrl = base_url + "/cli_clientes/getSelectModelos";
  let request = new XMLHttpRequest();

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState === 4 && request.status === 200) {
      listModelos.innerHTML = request.responseText;

      modelosDisponibles.innerHTML = "";
      modelosSeleccionados.innerHTML = "";

      Array.from(listModelos.options).forEach((opt) => {
        let li = document.createElement("li");
        li.className = "list-group-item";
        li.textContent = opt.text;
        li.dataset.id = opt.value;

        modelosDisponibles.appendChild(li);
      });

      initDragModelos();
    }
  };
}

function initDragModelos() {
  Sortable.create(modelosDisponibles, {
    group: "modelos",
    animation: 150,
  });

  Sortable.create(modelosSeleccionados, {
    group: "modelos",
    animation: 150,
    onAdd: syncSelectModelos,
    onRemove: syncSelectModelos,
    onSort: syncSelectModelos,
  });
}

function syncSelectModelos() {
  Array.from(listModelos.options).forEach((o) => (o.selected = false));

  Array.from(modelosSeleccionados.children).forEach((li) => {
    const opt = listModelos.querySelector(`option[value="${li.dataset.id}"]`);
    if (opt) opt.selected = true;
  });
}
