document.addEventListener("DOMContentLoaded", function () {
  const modalExistenciasEl = document.getElementById("modalExistencias");

  if (modalExistenciasEl) {
    modalExistenciasEl.addEventListener("hidden.bs.modal", function () {
      document.getElementById("bodyExistencias").innerHTML = "";
      document.getElementById("totalExistencias").innerText = "0";
    });
  }

  const form = document.querySelector("#formMultialmacenes");
  const selectInv = document.querySelector("#listInventario");
  const selectAlm = document.querySelector("#listAlmacenes");
  /* ================================================================
   * MATRIZ DE EXISTENCIAS: todo el inventario activo x almacén
   * (server-side; las columnas de almacén se arman con getAlmacenesMatriz)
   * ============================================================== */
  const esc = (s) =>
    String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]);
  const fmt = (n) => Number(n).toLocaleString("es-MX", { maximumFractionDigits: 2 });

  let table = null;
  let almacenesMatriz = [];
  let tiposMatriz = {};
  const COLS_FIJAS = 5; // clave, descripción, tipo, total, desglose (oculta en vista matriz)
  const COL_DESGLOSE = 4;

  const selTipo = document.getElementById("filtroTipoMatriz");
  const selPlanta = document.getElementById("filtroPlantaMatriz");
  const chkSoloExist = document.getElementById("soloExistenciaMatriz");
  const chkOcultarVacios = document.getElementById("ocultarVaciosMatriz");

  // Vista: "matriz" (columna por almacén) o "desglose" (chips). Se recuerda por navegador.
  let vista = "matriz";
  try {
    if (localStorage.getItem("multialm_vista") === "desglose") vista = "desglose";
  } catch (e) {}
  document.getElementById(vista === "desglose" ? "vistaDesglose" : "vistaMatriz").checked = true;

  function filtrosMatriz() {
    return {
      filtro_tipo: selTipo.value,
      filtro_planta: selPlanta.value,
      solo_existencia: chkSoloExist.checked ? 1 : 0,
    };
  }

  function celdaExistencia(valor, minimo) {
    if (valor === null || valor === undefined) return '<span class="cero">–</span>';
    const v = Number(valor);
    if (v === 0) return '<span class="cero">0</span>';
    const bajo = Number(minimo) > 0 && v < Number(minimo);
    const txt = `<span class="${v < 0 ? "negativo" : ""}">${fmt(v)}</span>`;
    return bajo ? `<span class="celda-bajo-min px-1" title="Stock mínimo: ${fmt(minimo)}">${txt}</span>` : txt;
  }

  // Desglose: un chip por almacén (de la planta elegida) donde el artículo tiene existencia
  function chipsDesglose(row) {
    const planta = selPlanta.value;
    const chips = almacenesMatriz
      .filter((a) => !planta || a.planta === planta)
      .map((a) => ({ a, v: row["a_" + a.idalmacen], m: row["m_" + a.idalmacen] }))
      .filter((x) => x.v !== null && x.v !== undefined && Number(x.v) !== 0)
      .sort((x, y) => Number(y.v) - Number(x.v))
      .map((x) => {
        const bajo = Number(x.m) > 0 && Number(x.v) < Number(x.m);
        const cls = "chip-alm" + (bajo ? " bajo-min" : "") + (Number(x.v) < 0 ? " negativo" : "");
        const tit = esc(x.a.descripcion) + (bajo ? ` · stock mínimo ${fmt(x.m)}` : "");
        return `<span class="${cls}" title="${tit}">${esc(x.a.cve_almacen)} <b>${fmt(x.v)}</b></span>`;
      });
    return chips.length ? chips.join("") : '<span class="cero fst-italic">Sin existencia</span>';
  }

  // Columnas visibles según vista, planta y "ocultar almacenes sin existencia"
  function aplicarVisibilidadAlmacenes() {
    if (!table) return;
    const planta = selPlanta.value;
    const esMatriz = vista === "matriz";
    table.column(COL_DESGLOSE).visible(!esMatriz, false);
    almacenesMatriz.forEach((a, i) => {
      const enPlanta = !planta || a.planta === planta;
      const conExist = Number(a.existencia) !== 0;
      table.column(COLS_FIJAS + i).visible(esMatriz && enPlanta && (!chkOcultarVacios.checked || conExist), false);
    });
    chkOcultarVacios.disabled = !esMatriz;
    table.columns.adjust();
    const visibles = almacenesMatriz.filter((a) => (!planta || a.planta === planta) && Number(a.existencia) !== 0).length;
    document.getElementById("kpiAlmacenes").textContent = fmt(visibles);
  }

  function initMatriz() {
    fetch(base_url + "/Inv_multialmacenes/getAlmacenesMatriz")
      .then((r) => r.json())
      .then((resp) => {
        almacenesMatriz = resp.almacenes || [];
        tiposMatriz = resp.tipos || {};

        Object.entries(tiposMatriz).forEach(([k, v]) => selTipo.insertAdjacentHTML("beforeend", `<option value="${k}">${esc(v)}</option>`));
        [...new Set(almacenesMatriz.map((a) => a.planta))].forEach((p) =>
          selPlanta.insertAdjacentHTML("beforeend", `<option value="${esc(p)}">${p === "OTROS" ? "Otros" : esc(p)}</option>`),
        );

        // Encabezado: fijas + un almacén por columna
        let th = `<tr>
            <th class="fija fija-1">CLAVE</th>
            <th class="fija fija-2">DESCRIPCIÓN</th>
            <th>TIPO</th>
            <th class="num">TOTAL</th>
            <th>POR ALMACÉN</th>`;
        almacenesMatriz.forEach((a) => {
          th += `<th class="num col-almacen" title="${esc(a.descripcion)}">${esc(a.cve_almacen)}</th>`;
        });
        document.querySelector("#tableMultialmacenes thead").innerHTML = th + "</tr>";

        const columns = [
          { data: "cve_articulo", className: "fija fija-1 fw-semibold", render: (d) => esc(d) },
          { data: "descripcion", className: "fija fija-2", render: (d) => `<span title="${esc(d)}">${esc(d)}</span>` },
          {
            data: "tipo_elemento",
            render: (d) => `<span class="badge bg-light text-body border tipo-badge">${esc(tiposMatriz[d] || d)}</span>`,
          },
          { data: "total", className: "num col-total", render: (d) => celdaExistencia(d, 0) },
          { data: null, orderable: false, className: "col-desglose", render: (d, t, row) => chipsDesglose(row) },
          ...almacenesMatriz.map((a) => ({
            data: "a_" + a.idalmacen,
            className: "num",
            defaultContent: "",
            render: (d, t, row) => celdaExistencia(d, row["m_" + a.idalmacen]),
          })),
        ];

        table = $("#tableMultialmacenes").DataTable({
          serverSide: true,
          processing: true,
          searchDelay: 400,
          ajax: {
            url: base_url + "/Inv_multialmacenes/getMatriz",
            type: "POST",
            data: (d) => Object.assign(d, filtrosMatriz()),
          },
          columns,
          order: [[3, "desc"]],
          pageLength: 25,
          lengthMenu: [10, 25, 50, 100, 250],
          autoWidth: false,
          dom: "<'row mb-2'<'col-sm-6'l><'col-sm-6'f>><'matriz-scroll'tr><'row mt-2'<'col-sm-5'i><'col-sm-7'p>>",
          language: {
            search: "Buscar:",
            searchPlaceholder: "Clave o descripción",
            lengthMenu: "Mostrar _MENU_",
            info: "_START_ a _END_ de _TOTAL_ artículos",
            infoEmpty: "Sin artículos",
            infoFiltered: "(de _MAX_ activos)",
            zeroRecords: "No hay artículos con esos filtros",
            processing: "Cargando…",
            paginate: { previous: "Anterior", next: "Siguiente" },
          },
          initComplete: aplicarVisibilidadAlmacenes,
          // La descripción se fija justo después de la clave: su "left" es el ancho real de la clave
          drawCallback: function () {
            const th = this.api().column(0).header();
            if (th) this.api().table().node().style.setProperty("--ancho-fija-1", th.offsetWidth + "px");
          },
        });

        table.on("xhr", function (e, settings, json) {
          if (!json || !json.resumen) return;
          document.getElementById("kpiArticulos").textContent = fmt(json.resumen.articulos);
          document.getElementById("kpiConExistencia").textContent = fmt(json.resumen.con_existencia);
          document.getElementById("kpiUnidades").textContent = fmt(json.resumen.unidades);
        });

        // Clic en un artículo: detalle por almacén con stock mínimo / máximo
        $("#tableMultialmacenes tbody").on("click", "tr", function () {
          const row = table.row(this).data();
          if (!row) return;
          cargarExistencias(row.cve_articulo);
          bootstrap.Modal.getOrCreateInstance(document.getElementById("modalExistencias")).show();
        });
      });
  }

  [selTipo, chkSoloExist].forEach((el) => el.addEventListener("change", () => table && table.ajax.reload()));
  selPlanta.addEventListener("change", () => {
    aplicarVisibilidadAlmacenes();
    if (table) table.ajax.reload(); // el total se recalcula con los almacenes de la planta
  });
  chkOcultarVacios.addEventListener("change", aplicarVisibilidadAlmacenes);
  document.querySelectorAll('input[name="vistaMatriz"]').forEach((r) =>
    r.addEventListener("change", function () {
      vista = this.value;
      try {
        localStorage.setItem("multialm_vista", vista);
      } catch (e) {}
      aplicarVisibilidadAlmacenes();
    }),
  );

  document.getElementById("btnExportarMatriz").addEventListener("click", () => {
    const q = new URLSearchParams(filtrosMatriz());
    if (table) q.set("q", table.search());
    window.open(base_url + "/Inv_multialmacenes/exportarMatriz?" + q.toString(), "_blank");
  });

  initMatriz();

  // Cargar select
  fetch(base_url + "/Inv_multialmacenes/getSelectAlmacenes")
    .then((res) => res.text())
    .then((html) => (selectAlm.innerHTML = html));

  // Form submit
  form.addEventListener("submit", function (e) {
    e.preventDefault();
    if (!document.querySelector("#listInventario").value) {
      Swal.fire("Selecciona un inventario válido", "", "warning");
      return;
    }
    const fd = new FormData(this);
    fetch(base_url + "/Inv_multialmacenes/setMultialmacen", {
      method: "POST",
      body: fd,
    })
      .then((res) => res.json())
      .then((obj) => {
        Swal.fire(obj.msg, "", obj.status ? "success" : "error");
        if (obj.status) {
          if (table) table.ajax.reload();
          form.reset();
          document.querySelector("#idmultialmacen").value = 0;
        }
      });
  });

  let inventariosCache = [];

  // Cargar inventarios en cache
  fetch(base_url + "/Inv_multialmacenes/getSelectInventariosJson")
    .then((res) => res.json())
    .then((data) => {
      inventariosCache = data;
      console.log("Inventarios:", inventariosCache);
    });

  const inputInv = document.querySelector("#inventarioSearch");

  if (inputInv) {
    inputInv.addEventListener("input", function () {
      let val = this.value.toLowerCase();
      cerrarListaInv();

      // limpiar hidden al escribir
      document.querySelector("#listInventario").value = "";

      if (!val) return;

      let lista = document.createElement("div");
      lista.className = "autocomplete-items list-group position-absolute w-100";
      this.parentNode.appendChild(lista);

      inventariosCache
        .filter(
          (p) =>
            (p.cve_articulo && p.cve_articulo.toLowerCase().includes(val)) ||
            (p.descripcion && p.descripcion.toLowerCase().includes(val)),
        )
        .slice(0, 10)
        .forEach((p) => {
          let item = document.createElement("div");
          item.className = "list-group-item list-group-item-action";
          item.innerHTML = `<strong>${p.cve_articulo}</strong> - ${p.descripcion}`;

          item.addEventListener("click", function () {
            document.querySelector("#inventarioSearch").value =
              p.cve_articulo + " - " + p.descripcion;
            document.querySelector("#listInventario").value = p.idinventario;
            cerrarListaInv();
          });

          lista.appendChild(item);
        });
    });

    inputInv.addEventListener("change", function () {
      if (!this.value) {
        document.querySelector("#listInventario").value = "";
      }
    });
  }

  function cerrarListaInv() {
    document.querySelectorAll(".autocomplete-items").forEach((e) => e.remove());
  }
  // Cerrar autocomplete al hacer click fuera
  document.addEventListener("click", function (e) {
    const input = document.querySelector("#inventarioSearch");
    const lista = document.querySelector(".autocomplete-items");

    if (!input) return;

    if (
      e.target !== input &&
      !input.contains(e.target) &&
      (!lista || !lista.contains(e.target))
    ) {
      cerrarListaInv();
    }
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      cerrarListaInv();
    }
  });

  // BOTON EXISTENCIAS
  document
    .getElementById("btnExistencias")
    .addEventListener("click", function () {
      const modal = new bootstrap.Modal(
        document.getElementById("modalExistencias"),
      );
      modal.show();
      document.getElementById("bodyExistencias").innerHTML = "";
    });
  const modalExistencias = new bootstrap.Modal(
    document.getElementById("modalExistencias"),
  );
  const modalBuscar = new bootstrap.Modal(
    document.getElementById("modalBuscarArticulo"),
  );

  document.getElementById("btnAbrirBusqueda").addEventListener("click", () => {
    document.getElementById("inputBuscarArticulo").value = "";
    document.getElementById("bodyBuscarArticulo").innerHTML = "";
    modalBuscar.show();
  });
});

document
  .getElementById("inputBuscarArticulo")
  .addEventListener("keyup", function () {
    const q = this.value;

    if (q.length < 2) {
      document.getElementById("bodyBuscarArticulo").innerHTML = "";
      return;
    }

    fetch(base_url + "/Inv_multialmacenes/buscarArticulos?q=" + q)
      .then((r) => r.json())
      .then((data) => {
        let html = "";

        data.forEach((row) => {
          html += `
            <tr>
              <td>${row.cve_articulo}</td>
              <td>${row.descripcion}</td>
              <td>
                <button class="btn btn-sm btn-primary"
                  onclick="seleccionarArticulo('${row.cve_articulo}')">
                  Seleccionar
                </button>
              </td>
            </tr>
          `;
        });

        document.getElementById("bodyBuscarArticulo").innerHTML = html;
      });
  });

function seleccionarArticulo(clave) {
  const modalBuscarEl = document.getElementById("modalBuscarArticulo");
  const instance = bootstrap.Modal.getInstance(modalBuscarEl);

  if (instance) instance.hide();

  cargarExistencias(clave);
  document.getElementById("inputBuscarArticulo").value = "";
document.getElementById("bodyBuscarArticulo").innerHTML = "";

}

function cargarExistencias(clave) {
  fetch(base_url + "/Inv_multialmacenes/buscarExistencias?q=" + clave)
    .then((r) => r.json())
    .then((data) => {
      let html = "";
      let totalGeneral = 0;

      data.forEach((row) => {
        totalGeneral += parseFloat(row.existencia);

        html += `
          <tr>
            <td>${row.cve_articulo}</td>
            <td>${row.descripcion}</td>
            <td>${row.almacen}</td>
            <td>${row.existencia}</td>
            <td><input type="number" class="form-control form-control-sm" value="${row.stock_minimo}"></td>
            <td><input type="number" class="form-control form-control-sm" value="${row.stock_maximo}"></td>
            <td>
              <button class="btn btn-sm btn-success"
                onclick="guardarStock(${row.idmultialmacen}, this)">
                Guardar
              </button>
            </td>
          </tr>`;
      });

      document.getElementById("bodyExistencias").innerHTML = html;
      document.getElementById("totalExistencias").innerText =
        totalGeneral.toFixed(2);
    });
}

function guardarStock(id, btn) {
  let row = btn.closest("tr");
  let min = row.querySelectorAll("input")[0].value;
  let max = row.querySelectorAll("input")[1].value;

  let fd = new FormData();
  fd.append("id", id);
  fd.append("stock_min", min);
  fd.append("stock_max", max);

  fetch(base_url + "/Inv_multialmacenes/updateStock", {
    method: "POST",
    body: fd,
  })
    .then((r) => r.json())
    .then((resp) => {
      Swal.fire(resp.msg, "", resp.status ? "success" : "error");
    });
}
