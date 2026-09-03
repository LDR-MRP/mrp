let unidadesCache = [];
let idtraslado = 0;
let guardandoCambios = false;

document.addEventListener("DOMContentLoaded", function () {
  idtraslado = document.querySelector("#idtraslado").value;

  /* =====================================================
       CARGAR CATÁLOGOS + DATOS DEL TRASLADO
    ===================================================== */

  Promise.all([
    fetch(base_url + "/Inv_traslados/getSelectAlmacenesOrigen").then((res) =>
      res.text(),
    ),
    fetch(base_url + "/Inv_traslados/getSelectAlmacenes").then((res) =>
      res.text(),
    ),
    fetch(base_url + "/Inv_traslados/getTransportistas").then((res) =>
      res.json(),
    ),
    fetch(base_url + "/Inv_traslados/getTrasladoEditar/" + idtraslado).then(
      (res) => res.json(),
    ),
  ])
    .then(([htmlOrigen, htmlDestino, proveedores, data]) => {
      document.querySelector("#almacen_origenid").innerHTML = htmlOrigen;
      document.querySelector("#almacen_destinoid").innerHTML = htmlDestino;

      let selectProveedor = document.querySelector("#id_proveedor");

      selectProveedor.innerHTML = `<option value="">
        Seleccione proveedor
        </option>`;

      (proveedores || []).forEach((p) => {
        selectProveedor.innerHTML += `

            <option value="${p.id_proveedor}">
                ${p.razon_social}
            </option>

            `;
      });

      cargarTraslado(data);
    })
    .catch(() => {
      Swal.fire(
        "Error",
        "No se pudo cargar la información del traslado",
        "error",
      ).then(() => {
        window.location.href = base_url + "/inv_traslados";
      });
    });

  document
    .querySelector("#almacen_origenid")
    .addEventListener("change", cargarUnidadesDelOrigen);

  /* =====================================================
       AGREGAR UNIDAD
    ===================================================== */

  const btnAgregar = document.querySelector("#btnAgregarUnidad");

  if (btnAgregar) {
    btnAgregar.addEventListener("click", () => {
      let filas = document.querySelectorAll("#tableUnidades tbody tr");

      let pendiente = false;

      filas.forEach((tr) => {
        if (tr.id === "rowSinUnidades") return;

        let vin = tr.querySelector(".vin")?.value;

        if (!vin) pendiente = true;
      });

      if (pendiente) {
        Swal.fire(
          "Atención",
          "Complete la unidad actual antes de agregar otra",
          "warning",
        );

        return;
      }

      agregarFilaUnidad();
    });
  }

  /* =====================================================
       GUARDAR CAMBIOS
    ===================================================== */

  const form = document.querySelector("#formTraslado");

  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      if (guardandoCambios) return;

      let nombreTrasladista = form
        .querySelector('[name="nombre_trasladista"]')
        .value.trim();

      let unidades = [];
      let faltaTipoLlave = false;
      let algunaConLlave = false;

      document.querySelectorAll("#tableUnidades tbody tr").forEach((tr) => {
        let vinid = tr.querySelector(".vinid").value;
        if (!vinid) return;

        let entrega = tr.querySelector(".entrega_llave").value === "1";
        let tipoLlave = tr.querySelector(".tipo_llave").value;

        if (entrega) {
          algunaConLlave = true;
          if (!tipoLlave) faltaTipoLlave = true;
        }

        unidades.push({
          vinid: vinid,
          inventarioid: tr.querySelector(".inventarioid").value,
          vin: tr.querySelector(".vin").value,
          almacenid: tr.querySelector(".almacenid").value,
          entrega_llave: entrega ? 1 : 0,
          tipo_llave: tipoLlave,
        });
      });

      if (unidades.length == 0) {
        Swal.fire("Atención", "Debe agregar al menos una unidad", "warning");
        return;
      }

      if (faltaTipoLlave) {
        Swal.fire(
          "Atención",
          "Indique el tipo de llave en cada unidad que entrega llave",
          "warning",
        );
        return;
      }

      if (algunaConLlave && !nombreTrasladista) {
        Swal.fire(
          "Atención",
          "Se requiere el nombre del trasladista para entregar llaves",
          "warning",
        );
        return;
      }

      let formData = new FormData(form);
      formData.append("unidades", JSON.stringify(unidades));

      guardandoCambios = true;
      const btnGuardar = document.querySelector("#btnGuardarCambios");
      if (btnGuardar) btnGuardar.disabled = true;

      fetch(base_url + "/Inv_traslados/updateTraslado", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          guardandoCambios = false;
          if (btnGuardar) btnGuardar.disabled = false;

          if (data.status) {
            Swal.fire("Correcto", data.msg, "success").then(() => {
              window.location.href = base_url + "/inv_traslados";
            });
          } else {
            Swal.fire("Error", data.msg, "error");
          }
        })
        .catch(() => {
          guardandoCambios = false;
          if (btnGuardar) btnGuardar.disabled = false;
          Swal.fire("Error", "No se pudo guardar el traslado", "error");
        });
    });
  }
});

/* =====================================================
   PINTAR DATOS DEL TRASLADO EN EL FORMULARIO
===================================================== */

function cargarTraslado(data) {
  const traslado = data && data.traslado;

  if (!traslado || (Array.isArray(traslado) && traslado.length === 0)) {
    Swal.fire("Error", "No se encontró el traslado", "error").then(() => {
      window.location.href = base_url + "/inv_traslados";
    });

    return;
  }

  if (parseInt(traslado.estado) !== 1) {
    Swal.fire({
      icon: "warning",
      title: "Ya no se puede editar",
      text: "Este traslado ya no está en 'Solicitud' (la salida ya fue registrada, o está recibido/cancelado), así que ya no se puede modificar.",
    }).then(() => {
      window.location.href = base_url + "/inv_traslados";
    });

    document
      .querySelectorAll(
        "#formTraslado input, #formTraslado select, #formTraslado button, #formTraslado textarea",
      )
      .forEach((el) => (el.disabled = true));

    return;
  }

  document.querySelector("#lblFolio").textContent = traslado.folio || "—";

  document.querySelector("#tipo_traslado").value = traslado.tipo_traslado || "";
  document.querySelector("#fecha_programada").value =
    traslado.fecha_programada || "";
  document.querySelector("#observaciones").value = traslado.observaciones || "";

  document.querySelector("#almacen_origenid").value = traslado.almacen_origenid;
  document.querySelector("#almacen_destinoid").value =
    traslado.almacen_destinoid;
  document.querySelector("#id_proveedor").value = traslado.proveedorid || "";

  // Catálogo de unidades disponibles del almacén de origen actual, para
  // que el buscador de "Agregar Unidad" funcione igual que en Nueva
  // Solicitud (unidades ya incluidas en este traslado no aparecen ahí,
  // solo se recargan aquí abajo con los datos guardados).
  cargarUnidadesDelOrigen();

  const trasladista = data.trasladista || {};

  document.querySelector("#nombre_trasladista").value =
    trasladista.nombre || "";
  document.querySelector("#numero_licencia").value =
    trasladista.numero_licencia || "";
  document.querySelector("#correo_trasladista").value =
    trasladista.contacto || "";
  document.querySelector("#vigencia_licencia").value =
    trasladista.vigencia_licencia || "";

  if (trasladista.archivo_licencia) {
    document.querySelector("#lblArchivoActual").textContent =
      "Archivo actual: " +
      trasladista.archivo_licencia +
      " (suba uno nuevo solo si desea reemplazarlo)";
  }

  const tbody = document.querySelector("#tableUnidades tbody");
  tbody.innerHTML = "";

  (data.detalle || []).forEach((u) => {
    agregarFilaUnidad(u);
  });

  if ((data.detalle || []).length === 0) {
    tbody.innerHTML = `<tr id="rowSinUnidades"></tr>`;
  }
}

function cargarUnidadesDelOrigen() {
  const almacenid = document.querySelector("#almacen_origenid").value;

  unidadesCache = [];

  if (!almacenid) return;

  fetch(base_url + "/Inv_traslados/getUnidadesPorAlmacen/" + almacenid)
    .then((res) => res.json())
    .then((data) => {
      unidadesCache = data;
    });
}

/* =====================================================
   CREAR FILA (vacía o precargada con los datos guardados)
===================================================== */

function agregarFilaUnidad(prefill) {
  let filaVacia = document.querySelector("#rowSinUnidades");
  if (filaVacia) filaVacia.remove();

  let tbody = document.querySelector("#tableUnidades tbody");
  let tr = document.createElement("tr");

  tr.innerHTML = `

<td style="position:relative">
  <input class="form-control unidadSearch" placeholder="Buscar VIN">
  <input type="hidden" class="almacenid" name="almacenid[]">
  <input type="hidden" class="vinid" name="vinid[]">
  <input type="hidden" class="inventarioid" name="inventarioid[]">
  <input type="hidden" class="vin" name="vin[]">
</td>

<td class="modelo"></td>
<td class="almacen"></td>

<td>
  <select class="form-select entrega_llave">
    <option value="0">No</option>
    <option value="1">Sí</option>
  </select>
</td>

<td>
  <select class="form-select tipo_llave" disabled>
    <option value="">--</option>
    <option value="principal">Principal</option>
    <option value="duplicado">Duplicado</option>
  </select>
</td>

<td>
  <button type="button" class="btn btn-danger btn-sm btnEliminar">
    <i class="ri-delete-bin-line"></i>
  </button>
</td>

`;

  tbody.appendChild(tr);

  if (prefill) {
    tr.querySelector(".unidadSearch").value = prefill.vin || "";
    tr.querySelector(".vin").value = prefill.vin || "";
    tr.querySelector(".vinid").value = prefill.vinid || "";
    tr.querySelector(".inventarioid").value = prefill.inventarioid || "";
    tr.querySelector(".almacenid").value = prefill.almacenid || "";
    tr.querySelector(".modelo").innerHTML = prefill.unidad || "";
    tr.querySelector(".almacen").innerHTML = prefill.almacen || "";

    let selectEntrega = tr.querySelector(".entrega_llave");
    let selectTipo = tr.querySelector(".tipo_llave");

    if (parseInt(prefill.entrega_llave) === 1) {
      selectEntrega.value = "1";
      selectTipo.disabled = false;
      selectTipo.value = prefill.tipo_llave || "";
    }
  }
}

document.addEventListener("change", function (e) {
  if (!e.target.classList.contains("entrega_llave")) return;

  let tr = e.target.closest("tr");
  let selectTipo = tr.querySelector(".tipo_llave");

  let entrega = e.target.value === "1";
  selectTipo.disabled = !entrega;
  if (!entrega) selectTipo.value = "";
});

/* =====================================================
   ELIMINAR FILA
===================================================== */

document.addEventListener("click", function (e) {
  if (e.target.classList.contains("btnEliminar")) {
    e.target.closest("tr").remove();
  }
});

/* =====================================================
   AUTOCOMPLETE VIN
===================================================== */

document.addEventListener("input", function (e) {
  if (!e.target.classList.contains("unidadSearch")) return;

  let input = e.target;

  let tr = input.closest("tr");

  cerrarListaVIN();

  let valor = input.value.toLowerCase();

  if (!valor) return;

  let lista = document.createElement("div");

  // Se agrega al <body> (no dentro de la celda/tabla) y se posiciona con
  // "fixed" según las coordenadas del input, igual que en Nueva Solicitud:
  // dentro de la tabla, ".table-responsive" recorta cualquier elemento que
  // se salga de su área.
  lista.className = "autocomplete-items list-group";

  const posicionarLista = () => {
    const rect = input.getBoundingClientRect();
    lista.style.position = "fixed";
    lista.style.left = rect.left + "px";
    lista.style.top = rect.bottom + "px";
    lista.style.width = rect.width + "px";
  };

  posicionarLista();

  document.body.appendChild(lista);

  unidadesCache

    .filter(
      (u) =>
        (u.vin && u.vin.toLowerCase().includes(valor)) ||
        (u.num_unidad && u.num_unidad.toLowerCase().includes(valor)) ||
        (u.unidad && u.unidad.toLowerCase().includes(valor)),
    )

    .slice(0, 10)

    .forEach((u) => {
      let item = document.createElement("div");

      item.className = "list-group-item list-group-item-action";

      item.innerHTML = `

<strong>${u.vin}</strong><br>
${u.unidad}

`;

      item.onclick = function () {
        let existe = false;

        document.querySelectorAll("#tableUnidades tbody tr").forEach((row) => {
          let vinSeleccionado = row.querySelector(".vin")?.value;

          if (vinSeleccionado && vinSeleccionado === u.vin && row !== tr) {
            existe = true;
          }
        });

        if (existe) {
          Swal.fire(
            "Unidad duplicada",
            "El VIN seleccionado ya fue agregado al traslado",
            "warning",
          );

          return;
        }

        fetch(base_url + "/Inv_traslados/validarUnidadPendiente/" + u.vinid)
          .then((res) => res.json())

          .then((resp) => {
            if (resp.pendiente) {
              Swal.fire({
                icon: "warning",

                title: "Unidad con traslado pendiente",

                html: `
                    <b>VIN:</b> ${u.vin}<br><br>

                    Esta unidad ya tiene un traslado pendiente:

                    <br><br>

                    <b>Folio:</b> ${resp.folio}

                    <br>

                    <b>Origen:</b> ${resp.origen}

                    <br>

                    <b>Destino:</b> ${resp.destino}

                    <br>

                    <b>Fecha:</b> ${resp.fecha}
                `,
              });

              return;
            }

            input.value = u.vin;

            tr.querySelector(".vinid").value = u.vinid;

            tr.querySelector(".inventarioid").value = u.idinventario;

            tr.querySelector(".vin").value = u.vin;

            tr.querySelector(".almacenid").value = u.almacenid;

            tr.querySelector(".modelo").innerHTML = u.unidad;

            tr.querySelector(".almacen").innerHTML = u.almacen;

            cerrarListaVIN();
          });
      };

      lista.appendChild(item);
    });
});

function cerrarListaVIN() {
  document.querySelectorAll(".autocomplete-items").forEach((e) => e.remove());
}

document.addEventListener("click", function (e) {
  if (!e.target.classList.contains("unidadSearch")) {
    cerrarListaVIN();
  }
});

// Como la lista vive en el <body> con posición "fixed", si el usuario
// hace scroll (de la página o del contenedor de la tabla) hay que
// cerrarla para que no quede flotando en un lugar equivocado. Se ignora
// el scroll que ocurre DENTRO de la propia lista (tiene max-height +
// overflow-y: auto), si no se cerraba apenas el usuario intentaba bajar
// para ver más resultados.
window.addEventListener(
  "scroll",
  function (e) {
    if (e.target.closest && e.target.closest(".autocomplete-items")) return;
    cerrarListaVIN();
  },
  true,
);
window.addEventListener("resize", cerrarListaVIN);
