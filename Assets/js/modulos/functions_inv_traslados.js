let unidadesCache = [];

document.addEventListener("DOMContentLoaded", function () {
  /* =====================================================
       CARGAR ALMACENES
    ===================================================== */

  // Origen: solo los almacenes de la planta del usuario que está
  // capturando la solicitud.
  fetch(base_url + "/Inv_traslados/getSelectAlmacenesOrigen")
    .then((res) => res.text())

    .then((html) => {
      document.querySelector("#almacen_origenid").innerHTML = html;
    });

  // Destino: todos los almacenes (el traslado puede ir a otra planta).
  fetch(base_url + "/Inv_traslados/getSelectAlmacenes")
    .then((res) => res.text())

    .then((html) => {
      document.querySelector("#almacen_destinoid").innerHTML = html;
    });

  document
    .querySelector("#almacen_origenid")
    .addEventListener("change", function () {
      let almacenid = this.value;

      unidadesCache = [];

      if (!almacenid) {
        return;
      }

      fetch(base_url + "/Inv_traslados/getUnidadesPorAlmacen/" + almacenid)
        .then((res) => res.json())

        .then((data) => {
          unidadesCache = data;

          console.log("Unidades cargadas:", data);
        });
    });

  /* =====================================================
       CARGAR TRANSPORTISTAS
    ===================================================== */

  fetch(base_url + "/Inv_traslados/getTransportistas")
    .then((res) => res.json())

    .then((data) => {
      let select = document.querySelector("#id_proveedor");

      select.innerHTML = `<option value="">
        Seleccione proveedor
        </option>`;

      data.forEach((p) => {
        select.innerHTML += `

            <option value="${p.id_proveedor}">
                ${p.razon_social}
            </option>

            `;
      });
    });

  /* =====================================================
       AGREGAR UNIDAD
    ===================================================== */

  const btnAgregar = document.querySelector("#btnAgregarUnidad");

  if (btnAgregar) {
    btnAgregar.addEventListener("click", () => {
      let filas = document.querySelectorAll("#tableUnidades tbody tr");

      let pendiente = false;

      filas.forEach((tr) => {
        // Ignorar fila inicial vacía
        if (tr.id === "rowSinUnidades") {
          return;
        }

        let vin = tr.querySelector(".vin")?.value;

        if (!vin) {
          pendiente = true;
        }
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
       GUARDAR TRASLADO
    ===================================================== */

  const form = document.querySelector("#formTraslado");

  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();

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

      fetch(base_url + "/Inv_traslados/setTraslado", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.status) {
            Swal.fire("Correcto", data.msg, "success");
            form.reset();
            document.querySelector("#tableUnidades tbody").innerHTML = "";
          } else {
            Swal.fire("Error", data.msg, "error");
          }
        });
    });
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
  // "fixed" según las coordenadas del input. Dentro de la tabla el
  // contenedor ".table-responsive" recorta cualquier elemento que se
  // salga de su área, por lo que la lista quedaba oculta/cortada.
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
            console.log(u);

            tr.querySelector(".inventarioid").value = u.idinventario;

            tr.querySelector(".vin").value = u.vin;

            tr.querySelector(".almacenid").value = u.almacenid;

            tr.querySelector(".modelo").innerHTML = u.unidad;

            tr.querySelector(".almacen").innerHTML = u.almacen;

            cerrarListaVIN();
          });
      }; // IMPORTANTE

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

// Como la lista ahora vive en el <body> con posición "fixed", si el
// usuario hace scroll (de la página o del contenedor de la tabla) hay
// que cerrarla para que no quede flotando en un lugar equivocado.
// OJO: la propia lista es scrolleable (max-height + overflow-y: auto),
// así que hay que ignorar el scroll que ocurre DENTRO de ella; si no,
// se cerraba apenas el usuario intentaba bajar para ver más resultados.
window.addEventListener(
  "scroll",
  function (e) {
    if (e.target.closest && e.target.closest(".autocomplete-items")) return;
    cerrarListaVIN();
  },
  true,
);
window.addEventListener("resize", cerrarListaVIN);

/* =====================================================
   ELIMINAR FILA
===================================================== */

document.addEventListener("click", function (e) {
  if (e.target.classList.contains("btnEliminar")) {
    e.target.closest("tr").remove();
  }
});

/* =====================================================
   AGREGAR FILA UNIDAD
===================================================== */
function agregarFilaUnidad() {
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
}
document.addEventListener("change", function (e) {
  if (!e.target.classList.contains("entrega_llave")) return;

  let tr = e.target.closest("tr");
  let selectTipo = tr.querySelector(".tipo_llave");

  let entrega = e.target.value === "1";
  selectTipo.disabled = !entrega;
  if (!entrega) selectTipo.value = "";
});
