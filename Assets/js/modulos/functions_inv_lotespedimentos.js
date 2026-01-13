let tableLotesPedimentos;
let formTipo = null;

document.addEventListener("DOMContentLoaded", function () {
  tableLotesPedimentos = $("#tableLotesPedimentos").DataTable({
    ajax: {
      url: base_url + "/Inv_lotespedimentos/getLotesPedimentos",
      dataSrc: "",
    },
    columns: [
      { data: "cve_articulo" },
      { data: "descripcion" },
      { data: "lote" },
      { data: "pedimento" },
      { data: "almacen" },
      { data: "nombre_aduana" },
      { data: "cantidad" },
      { data: "ciudad" },
      { data: "frontera" },
      { data: "gln" },
      { data: "fecha_caducidad" },
      { data: "fecha_creacion" },
      { data: "estado" },
      { data: "options" },
    ],
  });

  /* ==============================
     CAMBIO DE TAB (tipo de formulario)
  ============================== */
  document.querySelectorAll('[data-bs-toggle="tab"]').forEach((tab) => {
    tab.addEventListener("shown.bs.tab", function (e) {
      if (e.target.getAttribute("href") === "#agregarLote") {
        formTipo = "lote";
        validarProductoActivo("#agregarLote");
      }

      if (e.target.getAttribute("href") === "#agregarPedimento") {
        formTipo = "pedimento";
        validarProductoActivo("#agregarPedimento");
      }
    });
  });

  /* ==============================
     CARGAR ALMACENES
  ============================== */
  document.querySelectorAll(".almacen-select").forEach((select) => {
    fetch(base_url + "/Inv_almacenes/getSelectAlmacenes/0")
      .then((res) => res.text())
      .then((html) => (select.innerHTML = html));
  });

  /* ==============================
     CAMBIO DE ALMACÉN
  ============================== */
  document.addEventListener("change", function (e) {
    if (!e.target.classList.contains("almacen-select")) return;

    let almacenid = e.target.value;
    let form = e.target.closest("form");
    let productoSelect = form.querySelector(".producto-select");

    productoSelect.innerHTML = '<option value="">Cargando...</option>';

    if (!almacenid) {
      productoSelect.innerHTML =
        '<option value="">Seleccione producto</option>';
      return;
    }

    fetch(base_url + "/Inv_lotespedimentos/getProductosPorAlmacen/" + almacenid)
      .then((res) => res.json())
      .then((data) => {
        productoSelect.innerHTML =
          '<option value="">Seleccione producto</option>';
        data.forEach((p) => {
          productoSelect.innerHTML += `
            <option value="${p.idinventario}"
              data-lote="${p.lote}"
              data-pedimento="${p.pedimento}">
              ${p.cve_articulo} - ${p.descripcion}
            </option>`;
        });
      });
  });

  document.addEventListener("submit", function (e) {
    /* ============================
     FORM PEDIMENTO
  ============================ */
    if (e.target.id === "formPedimento") {
      e.preventDefault();

      let formData = new FormData(e.target);

      fetch(base_url + "/Inv_lotespedimentos/setPedimento", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.status) {
            Swal.fire("Éxito", data.msg, "success");
            e.target.reset();
            tableLotesPedimentos.ajax.reload();
          } else {
            Swal.fire("Error", data.msg, "error");
          }
        })
        .catch((err) => {
          console.error(err);
          Swal.fire("Error", "Error de conexión", "error");
        });
    }

    /* ============================
     FORM LOTE
  ============================ */
    if (e.target.id === "formLote") {
      e.preventDefault();

      let formData = new FormData(e.target);

      fetch(base_url + "/Inv_lotespedimentos/setLote", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.status) {
            Swal.fire("Éxito", data.msg, "success");
            e.target.reset();
            tableLotesPedimentos.ajax.reload();
          } else {
            Swal.fire("Error", data.msg, "error");
          }
        })
        .catch((err) => {
          console.error(err);
          Swal.fire("Error", "Error de conexión", "error");
        });
    }
  });

  /* ==============================
     VALIDAR PRODUCTO
  ============================== */
  document.addEventListener("change", function (e) {
    if (!e.target.classList.contains("producto-select")) return;
    if (!formTipo) return;

    let option = e.target.options[e.target.selectedIndex];
    let lote = option.dataset.lote;
    let pedimento = option.dataset.pedimento;

    if (formTipo === "lote" && lote === "N") {
      Swal.fire({
        icon: "warning",
        title: "Aviso",
        html: `
      <strong>Este producto NO maneja lote</strong><br><br>
      Dirígete al inventario y edita el producto para permitirle el uso de lotes.
    `,
      });
      e.target.value = "";
    }

    if (formTipo === "pedimento" && pedimento === "N") {
      Swal.fire({
        icon: "warning",
        title: "Aviso",
        html: `
      <strong>Este producto NO maneja pedimento</strong><br><br>
      Dirígete al inventario y edita el producto para permitirle el uso de pedimentos.
    `,
      });
      e.target.value = "";
    }
  });
});

/* ==============================
   REVALIDAR PRODUCTO AL CAMBIAR TAB
============================== */
function validarProductoActivo(tabId) {
  let form = document.querySelector(tabId + " form");
  if (!form) return;

  let select = form.querySelector(".producto-select");
  if (!select || !select.value) return;

  let option = select.options[select.selectedIndex];
  let lote = option.dataset.lote;
  let pedimento = option.dataset.pedimento;

  if (formTipo === "lote" && lote === "N") {
    Swal.fire("Aviso", "Este producto NO maneja lote", "warning");
    select.value = "";
  }

  if (formTipo === "pedimento" && pedimento === "N") {
    Swal.fire("Aviso", "Este producto NO maneja pedimento", "warning");
    select.value = "";
  }

  console.log("formTipo:", formTipo);
  console.log("lote:", lote);
  console.log("pedimento:", pedimento);
}

// ----------------------------------------------
// VER DETALLE LOTE / PEDIMENTO
// ----------------------------------------------
function fntViewLotePedimento(id) {
  fetch(base_url + "/Inv_lotespedimentos/getLotePedimento/" + id)
    .then((res) => res.json())
    .then((objData) => {
      if (objData.status) {
        document.querySelector("#celArticulo").innerHTML =
          objData.data.cve_articulo;
        document.querySelector("#celLote").innerHTML = objData.data.lote;
        document.querySelector("#celPedimento").innerHTML =
          objData.data.pedimento;
        document.querySelector("#celCantidad").innerHTML =
          objData.data.cantidad;
        document.querySelector("#celAlmacen").innerHTML = objData.data.almacen;
        document.querySelector("#celFechaCad").innerHTML =
          objData.data.fecha_caducidad ?? "N/A";
        document.querySelector("#celFechaAduana").innerHTML =
          objData.data.fecha_aduana ?? "N/A";
        document.querySelector("#celAduana").innerHTML =
          objData.data.nombre_aduana ?? "N/A";
        document.querySelector("#celEstado").innerHTML =
          objData.data.estado == 2 ? "Activo" : "Inactivo";

        $("#modalViewLotesPedimentos").modal("show");
      }
    });
}

// ----------------------------------------------
// EDITAR LOTE / PEDIMENTO
// ----------------------------------------------
function fntEditLotePedimento(id) {
  fetch(base_url + "/Inv_lotespedimentos/getLotePedimento/" + id)
    .then(res => res.json())
    .then(objData => {

      if (!objData.status) return;

      const d = objData.data;

      // ID oculto
      document.querySelector("#id_ltpd").value = d.id_ltpd;

      let form, almacenSelect, productoSelect;

      /* ============================
         DEFINIR TIPO Y TAB
      ============================ */
      if (d.lote) {
        formTipo = "lote";
        document.querySelector('[href="#agregarLote"]').click();
        form = document.querySelector("#formLote");

        form.querySelector("#lote_lote").value = d.lote;
        form.querySelector("#lote_cantidad").value = d.cantidad;
        form.querySelector("#lote_fecha_caducidad").value = d.fecha_caducidad ?? "";
        form.querySelector("#lote_fecha_produccion").value = d.fecha_produccion_lote ?? "";

      } else {
        formTipo = "pedimento";
        document.querySelector('[href="#agregarPedimento"]').click();
        form = document.querySelector("#formPedimento");

        form.querySelector("#pedimento").value = d.pedimento;
        form.querySelector("#pedimento_SAT").value = d.pedimento_SAT ?? "";
        form.querySelector("#ped_cantidad").value = d.cantidad;
        form.querySelector("#ped_fecha_caducidad").value = d.fecha_caducidad ?? "";
        form.querySelector("#ped_fecha_produccion").value = d.fecha_produccion_lote ?? "";
        form.querySelector("#fecha_aduana").value = d.fecha_aduana ?? "";
        form.querySelector("#nombre_aduana").value = d.nombre_aduana ?? "";
        form.querySelector("#ciudad").value = d.ciudad ?? "";
        form.querySelector("#frontera").value = d.frontera ?? "";
        form.querySelector("#gln").value = d.gln ?? "";
        form.querySelector("#cve_observacion").value = d.cve_observacion ?? "";
      }

      /* ============================
         SELECTS DEPENDIENTES
      ============================ */
      almacenSelect = form.querySelector(".almacen-select");
      productoSelect = form.querySelector(".producto-select");

      // 1️⃣ Setear almacén
      almacenSelect.value = d.almacenid;

      // 2️⃣ Disparar change para cargar productos
      almacenSelect.dispatchEvent(new Event("change"));

      // 3️⃣ Esperar productos y setear producto
      setTimeout(() => {
        productoSelect.value = d.inventarioid;
      }, 300);

      document.querySelector("#btnText").textContent = "ACTUALIZAR";
    });
}



// ------------------------------------------------------------------------
//  ELIMINAR LOTE / PEDIMENTO
// ------------------------------------------------------------------------
function fntDelLotePedimento(id) {
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
                    ¿Estás seguro de eliminar este lote/pedimento?
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
    if (!result.isConfirmed) return;

    let request = window.XMLHttpRequest
      ? new XMLHttpRequest()
      : new ActiveXObject("Microsoft.XMLHTTP");
    let ajaxUrl = base_url + "/Inv_lotespedimentos/delLotePedimento";
    let strData = "id_ltpd=" + id;

    request.open("POST", ajaxUrl, true);
    request.setRequestHeader(
      "Content-type",
      "application/x-www-form-urlencoded"
    );
    request.send(strData);

    request.onreadystatechange = function () {
      if (request.readyState === 4 && request.status === 200) {
        let objData = JSON.parse(request.responseText);

        if (objData.status) {
          Swal.fire("Correcto", objData.msg, "success");
          $("#tableLotesPedimentos").DataTable().ajax.reload();
        } else {
          Swal.fire("Error", objData.msg, "error");
        }
      }
    };
  });
}


function cargarProductosYSeleccionar(form, almacenid, inventarioid) {
  const almacenSelect = form.querySelector(".almacen-select");
  const productoSelect = form.querySelector(".producto-select");

  almacenSelect.value = almacenid;

  productoSelect.innerHTML = '<option value="">Cargando...</option>';

  fetch(base_url + "/Inv_lotespedimentos/getProductosPorAlmacen/" + almacenid)
    .then(res => res.json())
    .then(data => {
      productoSelect.innerHTML = '<option value="">Seleccione producto</option>';

      data.forEach(p => {
        productoSelect.innerHTML += `
          <option value="${p.idinventario}">
            ${p.cve_articulo} - ${p.descripcion}
          </option>`;
      });

      // 🔥 AQUÍ SÍ, YA EXISTE EL OPTION
      productoSelect.value = inventarioid;
    });
}
