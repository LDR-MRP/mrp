let tableDistibuidores = null;
let rowTable = "";
let divLoading = document.querySelector("#divLoading");

// Inputs del formulario
const distribuidor = document.querySelector("#iddistribuidor");
const grupo_id = document.querySelector("#grupo_id");
const nombre_fisica = document.querySelector(
  "#nombre_fisica-distribuidores-input",
);
const apelldio_paterno = document.querySelector(
  "#apellido_paterno-distribuidores-input",
);
const apelldio_materno = document.querySelector(
  "#apellido_materno-distribuidores-input",
);
const fecha_nacimiento = document.querySelector(
  "#fecha_nacimiento-distribuidores-input",
);
const curp = document.querySelector("#curp-distribuidores-input");
const razon_social = document.querySelector("#razon-distribuidores-input");
const representante_legal = document.querySelector(
  "#representante_legal-distribuidores-input",
);
const domicilio_fiscal = document.querySelector(
  "#domicilio_fiscal-distribuidores-input",
);
const correo = document.querySelector("#correo-distribuidores-input");
const rfc = document.querySelector("#rfc-distribuidores-input");
const nombre_comercial = document.querySelector("#nombre-distribuidores-input");
const repve = document.querySelector("#repve-distribuidores-input");
const plaza = document.querySelector("#plaza-distribuidores-input");
const clasificacion = document.querySelector(
  "#clasificacion-distribuidores-input",
);
const estatus = document.querySelector("#estatus-select");
const tipo_negocio = document.querySelector("#tipo_negocio-select");
const telefono = document.querySelector("#telefono-distribuidores-input");
const telefono_alt = document.querySelector("#telefono_alt-input");

const listModelos = document.querySelector("#listModelos");
const modelosDisponibles = document.querySelector("#modelosDisponibles");
const modelosSeleccionados = document.querySelector("#modelosSeleccionados");

const regional_id = document.querySelector("#regional_id");
const regionalesDisponibles = document.querySelector("#regionalesDisponibles");
const regionalesSeleccionados = document.querySelector(
  "#regionalesSeleccionados",
);

const tipo = document.querySelector("#tipo-select");
const calle = document.querySelector("#calle-distribuidores-input");
const numero_ext = document.querySelector("#numero_ext-distribuidores-input");
const numero_int = document.querySelector("#numero_int-distribuidores-input");
const colonia = document.querySelector("#colonia-distribuidores-input");
const codigo_postal = document.querySelector(
  "#codigo_postal-distribuidores-input",
);

const inputRegion = document.querySelector("#region_nombre");

const selectPais = document.querySelector("#listPaises");
const selectEstado = document.querySelector("#listEstados");
const selectMunicipio = document.querySelector("#listMunicipios");

const selectPaisFiscal = document.querySelector("#listPaisesFiscal");
const selectEstadoFiscal = document.querySelector("#listEstadosFiscal");
const selectMunicipioFiscal = document.querySelector("#listMunicipiosFiscal");

const tipoPersona = document.querySelector("#tipo_persona-select");
const personaFisicaWrapper = document.querySelector("#personaFisicaWrapper");
const personaMoralWrapper = document.querySelector("#personaMoralWrapper");

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

    fntRegionales();
    fntGrupos();
    fntModelos();
    fntTipoClientes();
    fntMatrizDistribuidores();
    fntReigenFiscal();
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
        { data: "region" },
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

      language: {
        processing: "Procesando...",
        search: "Buscar:",
        lengthMenu: "Mostrar _MENU_ registros",
        info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
        infoEmpty: "Mostrando 0 a 0 de 0 registros",
        infoFiltered: "(filtrado de _MAX_ registros totales)",
        loadingRecords: "Cargando...",
        zeroRecords: "No se encontraron resultados",
        emptyTable: "No hay datos disponibles en la tabla",
        paginate: {
          first: "Primero",
          previous: "Anterior",
          next: "Siguiente",
          last: "Último",
        },
        aria: {
          sortAscending: ": activar para ordenar la columna ascendente",
          sortDescending: ": activar para ordenar la columna descendente",
        },
      },
    });

    // --------------------------------------------------------------------
    //  TABS BOOTSTRAP
    // --------------------------------------------------------------------
    const primerTabEl = document.querySelector(
      '#nav-tab a[href="#listdistribuidores"]',
    );
    const firstTabEl = document.querySelector(
      '#nav-tab a[href="#agregardistribuidores"]',
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
      });

      // ----------------------------------------------------------------
      // CLICK EN "DISTRIBUIDORES" → RESETEAR NAV A NUEVO
      // ----------------------------------------------------------------
      primerTabEl.addEventListener("click", () => {
        tabNuevo.textContent = "NUEVO";
        spanBtnText.textContent = "REGISTRAR";
        distribuidor.value = "";
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
      if (regionalesSeleccionados.children.length === 0) {
        Swal.fire("Atención", "Selecciona al menos una regional", "warning");
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
            "error",
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
                tabNuevo.textContent = "NUEVO";
                spanBtnText.textContent = "REGISTRAR";
                tableDistibuidores.api().ajax.reload();
              } else {
                // Regresar al listado
                formDistribuidores.reset();
                distribuidor.value = "";
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
  false,
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
    const reg = data.regional;

    distribuidor.value = data.id;
    grupo_id.value = data.grupo_id;
    nombre_fisica.value = data.nombre_fisica;
    apelldio_paterno.value = data.apellido_paterno;
    apelldio_materno.value = data.apellido_materno;
    fecha_nacimiento.value = data.fecha_nacimiento;
    correo.value = data.correo;
    curp.value = data.curp;
    razon_social.value = data.razon_social;
    representante_legal.value = data.representante_legal;
    domicilio_fiscal.value = data.domicilio_fiscal;
    rfc.value = data.rfc;
    nombre_comercial.value = data.nombre_comercial;
    repve.value = data.repve;
    plaza.value = data.plaza;
    clasificacion.value = data.clasificacion;
    estatus.value = data.estatus;
    tipo_negocio.value = data.tipo_negocio;
    matriz_id.value = data.matriz_id;
    tipo_cliente_id.value = data.tipo_cliente_id;
    telefono.value = data.telefono;
    telefono_alt.value = data.telefono_alt;

    modelosDisponibles.innerHTML = "";
    modelosSeleccionados.innerHTML = "";

    regionalesDisponibles.innerHTML = "";
    regionalesSeleccionados.innerHTML = "";

    regimen_fiscal_id.value = data.regimen_fiscal_id;
    tipoPersona.value = data.tipo_persona;
    toggleTipoPersona();
    toggleTipoNegocio();

    Array.from(listModelos.options).forEach((opt) => {
      let li = document.createElement("li");
      li.className = "list-group-item";
      li.textContent = opt.text;
      li.dataset.id = opt.value;

      const seleccionado = data.modelos.some(
        (m) => String(m.idlineaproducto) === opt.value,
      );

      if (seleccionado) {
        modelosSeleccionados.appendChild(li);
        opt.selected = true;
      } else {
        modelosDisponibles.appendChild(li);
      }
    });

    Array.from(regional_id.options).forEach((opt) => {
      let li = document.createElement("li");
      li.className = "list-group-item";
      li.textContent = opt.text;
      li.dataset.id = opt.value;

      const seleccionado = data.regionales.some(
        (r) => String(r.id) === opt.value,
      );

      if (seleccionado) {
        regionalesSeleccionados.appendChild(li);
        opt.selected = true;
      } else {
        regionalesDisponibles.appendChild(li);
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
    fntRegionByEstado(dir.estado_id);

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
        dirFiscal.estado_id,
      );
      fntMunicipios(
        dirFiscal.estado_id,
        selectMunicipioFiscal,
        dirFiscal.municipio_id,
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
      "application/x-www-form-urlencoded",
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

        const tipoPersona = objData.data.tipo_persona;

        document.querySelector("#tipopersona").innerHTML =
          tipoPersona == 1 ? "FÍSICA" : "MORAL";

        if (tipoPersona == 1) {
          // PERSONA FÍSICA
          document
            .querySelectorAll(".persona-fisica")
            .forEach((el) => (el.style.display = ""));
          document
            .querySelectorAll(".persona-moral")
            .forEach((el) => (el.style.display = "none"));

          document.querySelector("#nombre_fisica").innerHTML =
            objData.data.nombre_fisica;
          document.querySelector("#apellido_paterno").innerHTML =
            objData.data.apellido_paterno;
          document.querySelector("#apellido_materno").innerHTML =
            objData.data.apellido_materno;
          document.querySelector("#fecha_nacimiento").innerHTML =
            objData.data.fecha_nacimiento;
          document.querySelector("#curp").innerHTML = objData.data.curp;
        } else {
          // PERSONA MORAL
          document
            .querySelectorAll(".persona-moral")
            .forEach((el) => (el.style.display = ""));
          document
            .querySelectorAll(".persona-fisica")
            .forEach((el) => (el.style.display = "none"));

          document.querySelector("#representante_legal").innerHTML =
            objData.data.representante_legal;
          document.querySelector("#domicilio_fiscal").innerHTML =
            objData.data.domicilio_fiscal;
        }

        document.querySelector("#correo").innerHTML = objData.data.correo;
        document.querySelector("#nombregrupo").innerHTML =
          objData.data.nombre_grupo;
        document.querySelector("#nombre_tipo_negocio").innerHTML =
          objData.data.nombre_tipo_negocio;
        document.querySelector("#tiponegocio").innerHTML =
          objData.data.tipo_negocio;
        document.querySelector("#nombrecomercial").innerHTML =
          objData.data.nombre_comercial;
        document.querySelector("#razonsocial").innerHTML =
          objData.data.razon_social;
        document.querySelector("#plaza").innerHTML = objData.data.plaza;
        document.querySelector("#clasificacion").innerHTML =
          objData.data.clasificacion;
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
        document.querySelector("#region").innerHTML = direccion.region;

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

        if (objData.data.matriz_id) {
          document.querySelector("#matriz").innerHTML =
            objData.data.matriz_nombre_comercial +
            " (" +
            objData.data.matriz_razon_social +
            ")";
        } else {
          document.querySelector("#matriz").innerHTML = "Es matriz";
        }

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
                  <td>
                    ${contacto.nombre}
                    ${
                      contacto.distribuidor
                        ? `<br><small class="text-muted">${contacto.distribuidor}</small>`
                        : ""
                    }
                  </td>

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

        let htmlRegionales = "";
        let regionales = objData.data.regionales;

        if (regionales.length > 0) {
          regionales.forEach((r) => {
            htmlRegionales += `
                <tr>
                  <td>${r.id}</td>
                  <td>${r.nombre}</td>
                  <td>${r.apellido_paterno}</td>
                  <td>${r.apellido_materno}</td>
                </tr>
              `;
          });
        } else {
          htmlRegionales = `
              <tr>
                <td colspan="4" class="text-center">
                  No tiene regionales registrados
                </td>
              </tr> 
            `;
        }

        document.querySelector("#tbodyRegionales").innerHTML = htmlRegionales;

        let htmlSucursales = "";
        let sucursales = objData.data.sucursales;

        if (sucursales.length > 0) {
          sucursales.forEach((s) => {
            htmlSucursales += `
                <tr>
                  <td>${s.nombre_comercial}</td>
                  <td>${s.razon_social}</td>
                  <td>${s.plaza}</td>
                  <td>${s.telefono}</td>
                </tr>
              `;
          });
        } else {
          htmlSucursales = `
              <tr>
                <td colspan="4" class="text-center">
                  No tiene sucursales registradas
                </td>
              </tr>
            `;
        }

        document.querySelector("#tbodySucursales").innerHTML = htmlSucursales;

        $("#modalViewDistribuidor").modal("show");
      } else {
        Swal.fire("Error", objData.msg, "error");
      }
    }
  };
}

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE REGIONALES
// ------------------------------------------------------------------------
function initDragRegionales() {
  Sortable.create(regionalesDisponibles, {
    group: "regionales",
    animation: 150,
  });

  Sortable.create(regionalesSeleccionados, {
    group: "regionales",
    animation: 150,
    onAdd: syncSelectRegionales,
    onRemove: syncSelectRegionales,
    onSort: syncSelectRegionales,
  });
}
function fntRegionales() {
  let ajaxUrl = base_url + "/cli_clientes/getSelectRegionales";
  let request = new XMLHttpRequest();

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState === 4 && request.status === 200) {
      regional_id.innerHTML = request.responseText;

      regionalesDisponibles.innerHTML = "";
      regionalesSeleccionados.innerHTML = "";

      Array.from(regional_id.options).forEach((opt) => {
        let li = document.createElement("li");
        li.className = "list-group-item";
        li.textContent = opt.text;
        li.dataset.id = opt.value;

        regionalesDisponibles.appendChild(li);
      });

      initDragRegionales();
    }
  };
}

function initDragRegionales() {
  Sortable.create(regionalesDisponibles, {
    group: "regionales",
    animation: 150,
  });

  Sortable.create(regionalesSeleccionados, {
    group: "regionales",
    animation: 150,
    onAdd: syncSelectRegionales,
    onRemove: syncSelectRegionales,
    onSort: syncSelectRegionales,
  });
}

function syncSelectRegionales() {
  Array.from(regional_id.options).forEach((o) => (o.selected = false));

  Array.from(regionalesSeleccionados.children).forEach((li) => {
    const opt = regional_id.querySelector(`option[value="${li.dataset.id}"]`);
    if (opt) opt.selected = true;
  });
}

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE DISTRIBUIDORES
// ------------------------------------------------------------------------
function fntMatrizDistribuidores(selectedValue = "") {
  if (document.querySelector("#matriz_id")) {
    let ajaxUrl = base_url + "/cli_clientes/getSelectMatrizDistribuidores";
    let request = window.XMLHttpRequest
      ? new XMLHttpRequest()
      : new ActiveXObject("Microsoft.XMLHTTP");
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
      if (request.readyState == 4 && request.status == 200) {
        document.querySelector("#matriz_id").innerHTML = request.responseText;

        if (selectedValue !== "") {
          document.querySelector("#matriz_id").value = selectedValue;
        }
      }
    };
  }
}

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE TIPOS DE CLIENTES
// ------------------------------------------------------------------------
function fntTipoClientes(selectedValue = "") {
  if (document.querySelector("#tipo_cliente_id")) {
    let ajaxUrl = base_url + "/cli_clientes/getSelectTipoClientes";
    let request = window.XMLHttpRequest
      ? new XMLHttpRequest()
      : new ActiveXObject("Microsoft.XMLHTTP");
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
      if (request.readyState == 4 && request.status == 200) {
        document.querySelector("#tipo_cliente_id").innerHTML =
          request.responseText;

        if (selectedValue !== "") {
          document.querySelector("#tipo_cliente_id").value = selectedValue;
        }
      }
    };
  }
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

function fntReigenFiscal(tipoPersona = "") {
  let ajaxUrl =
    base_url + "/cli_clientes/getSelectRegimenFiscal/" + tipoPersona;
  let request = new XMLHttpRequest();

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState === 4 && request.status === 200) {
      document.querySelector("#regimen_fiscal_id").innerHTML =
        request.responseText;
    }
  };
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
    fntRegionByEstado(this.value);
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

function toggleTipoPersona() {
  const tipo = tipoPersona.value;

  const esFisica = tipo === "1";
  const esMoral = tipo === "2";

  personaFisicaWrapper.classList.toggle("d-none", !esFisica);
  personaMoralWrapper.classList.toggle("d-none", !esMoral);

  const fisicaInputs = personaFisicaWrapper.querySelectorAll("input, select");
  const moralInputs = personaMoralWrapper.querySelectorAll("input, select");

  fisicaInputs.forEach((input) => {
    input.required = esFisica;
    if (!esFisica) input.value = "";
  });

  moralInputs.forEach((input) => {
    input.required = esMoral;
    if (!esMoral) input.value = "";
  });
}

tipoPersona.addEventListener("change", toggleTipoPersona);

tipoPersona.addEventListener("change", function () {
  fntReigenFiscal(this.value);
});

document.addEventListener("DOMContentLoaded", () => {
  toggleTipoPersona();
});

function fntRegionByEstado(estado_id) {
  if (!estado_id) {
    inputRegion.value = "";
    return;
  }

  let ajaxUrl = base_url + "/cli_clientes/getRegionByEstado/" + estado_id;
  let request = new XMLHttpRequest();

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState === 4 && request.status === 200) {
      let objData = JSON.parse(request.responseText);

      if (objData.status) {
        inputRegion.value = objData.data.nombre;
      } else {
        inputRegion.value = "";
      }
    }
  };
}

const wrapperMatriz = document.querySelector("#wrapperMatriz");
const selectMatriz = document.querySelector("#matriz_id");

function toggleTipoNegocio() {
  if (tipo_negocio.value === "Sucursal") {
    wrapperMatriz.classList.remove("d-none");
    selectMatriz.required = true;
  } else {
    wrapperMatriz.classList.add("d-none");
    selectMatriz.required = false;
    selectMatriz.value = "";
  }
}

tipo_negocio.addEventListener("change", toggleTipoNegocio);

document.addEventListener("DOMContentLoaded", toggleTipoNegocio);
