let tableInventarios;

document.addEventListener("DOMContentLoaded", () => {
  tableInventarios = $("#tableInventarios").DataTable({
    destroy: true,
    ajax: {
      url: base_url + "/Inv_inventario/getInventarios",
      dataSrc: "",
    },
    columns: [
      { data: "cve_articulo" },
      { data: "descripcion" },
      { data: "tipo_elemento" },
      { data: "linea" },
      { data: "estado" },
      { data: "options" },
    ],
    language: {
      url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json",
    },
  });

  /* =============================
     CONFIGURACIÓN
  ============================= */
  const rutas = {
    lineas: base_url + "/Inv_lineasdproducto/getSelectLineasProductos",
    almacenes: base_url + "/Inv_almacenes/getSelectAlmacenes",
    save: base_url + "/Inv_inventario/setInventario",
  };

  const tabsConfig = {
    agregarProducto: {
      form: "#formInventarioProducto",
      selectLinea: "#lineaproductoid_producto",
      selectAlmacen: "#almacenid",
      tipo: "P",
    },
    agregarServicio: {
      form: "#formInventarioServicio",
      selectLinea: "#lineaproductoid_servicio",
      tipo: "S",
    },
    agregarKit: {
      form: "#formInventarioKit",
      selectLinea: "#lineaproductoid_kit",
      tipo: "K",
    },
  };

  /* =============================
     CARGA SELECT LÍNEAS
  ============================= */
  function cargarLineas(selectId, selectedValue = "") {
    const select = document.querySelector(selectId);
    if (!select) return;

    const request = new XMLHttpRequest();
    request.open("GET", rutas.lineas, true);
    request.send();

    request.onreadystatechange = () => {
      if (request.readyState === 4 && request.status === 200) {
        select.innerHTML = request.responseText;
        if (selectedValue) select.value = selectedValue;
      }
    };
  }

  /* =============================
     CARGA SELECT ALMACENES
  ============================= */
  function cargarAlmacenes(selectId, selectedValue = "") {
    const select = document.querySelector(selectId);
    if (!select) return;

    const request = new XMLHttpRequest();
    request.open("GET", rutas.almacenes, true);
    request.send();

    request.onreadystatechange = () => {
      if (request.readyState === 4 && request.status === 200) {
        select.innerHTML = request.responseText;
        if (selectedValue) select.value = selectedValue;
      }
    };
  }

  /* =============================
     EVENTOS DE TABS
  ============================= */
  document.querySelectorAll('[data-bs-toggle="tab"]').forEach((tab) => {
    tab.addEventListener("shown.bs.tab", (e) => {
      const targetId = e.target.getAttribute("href").replace("#", "");
      const config = tabsConfig[targetId];
      if (config) {
        cargarLineas(config.selectLinea);
        // 🔹 SOLO PRODUCTOS tienen almacén
        if (config.selectAlmacen) {
          cargarAlmacenes(config.selectAlmacen);
        }
      }
    });
  });

  /* =============================
     SUBMIT GENÉRICO
  ============================= */
  Object.values(tabsConfig).forEach((config) => {
    const form = document.querySelector(config.form);
    if (!form) return;

    form.addEventListener("submit", (e) => {
      e.preventDefault();

      // 1️⃣ Crear FormData PRIMERO
      const formData = new FormData(form);

      let lineaProducto = null;

      // 2️⃣ Detectar tab activo
      if (document.querySelector("#agregarProducto.show")) {
        lineaProducto = document.querySelector(
          "#lineaproductoid_producto"
        )?.value;
      }

      if (document.querySelector("#agregarKit.show")) {
        lineaProducto = document.querySelector("#lineaproductoid_kit")?.value;
      }

      if (document.querySelector("#agregarServicio.show")) {
        lineaProducto = document.querySelector(
          "#lineaproductoid_servicio"
        )?.value;
      }

      // 3️⃣ Validación FK
      if (!lineaProducto || lineaProducto === "") {
        Swal.fire(
          "Aviso",
          "Selecciona una línea de producto válida",
          "warning"
        );
        return;
      }

      // 5️⃣ Envío AJAX
      const request = new XMLHttpRequest();
      request.open("POST", rutas.save, true);
      request.send(formData);

      request.onload = () => {
        try {
          const res = JSON.parse(request.responseText);

          if (res.status === true) {
            Swal.fire("Correcto", res.msg, "success");

            // 🔄 Recargar tabla SIEMPRE
            if (typeof tableInventarios !== "undefined") {
              tableInventarios.ajax.reload();
            }

            // 🔹 PRODUCTO / SERVICIO
            if (res.tipo === "P" || res.tipo === "S") {
              // Limpiar formularios
              form.reset();

              // 🔁 Volver a la pestaña de tabla
              const tabTabla = document.querySelector(
                'a[href="#listInventarios"]'
              );
              if (tabTabla) {
                new bootstrap.Tab(tabTabla).show();
              }
            }

            // 🔹 KIT
            if (res.tipo === "K") {
              // Mostrar contenedor de configuración
              const container = document.getElementById("kit_config_container");
              const inputKitId = document.getElementById("kitid");

              if (container && inputKitId) {
                inputKitId.value = res.id;
                container.style.display = "block";

                // Scroll suave
                container.scrollIntoView({ behavior: "smooth" });
              }
            }
          }

          console.log(res);
        } catch (err) {
          console.error("Respuesta inválida:", request.responseText);
          Swal.fire("Error", "Respuesta inválida del servidor", "error");
        }
      };

      request.onerror = () => {
        Swal.fire("Error", "No se pudo conectar con el servidor", "error");
      };
    });
  });

  // HABILITAR CLAVE ALTERNA
  document
    .getElementById("btn_habilitar_clave_alterna")
    .addEventListener("click", function () {
      const inputClave = document.getElementById("clave_alterna");
      const contenedorTipo = document.getElementById(
        "tipo_asignacion_container"
      );

      inputClave.disabled = false;
      inputClave.required = true;

      contenedorTipo.style.display = "block";
    });

  /* =============================
     KIT CONFIG (NUEVO)
  ============================= */
  initKitConfig();

  function initKitConfig() {
    const kitContainer = document.getElementById("kit_config_container");
    if (!kitContainer) return; // ⛔ no estamos en kits

    let tbody = document.querySelector("#tabla_componentes tbody");
    let btnAgregar = document.getElementById("btn_agregar_fila");

    if (!tbody || !btnAgregar) return;

    let index = 0;

    /* =============================
     AGREGAR FILA
  ============================= */
    btnAgregar.addEventListener("click", () => {
      index++;

      const tr = document.createElement("tr");
      tr.innerHTML = `
      <td>
        <input type="number" min="0.0001" step="0.0001"
          class="form-control form-control-sm cantidad"
          name="componentes[${index}][cantidad]">
      </td>

      <td>
        <input type="text"
          class="form-control form-control-sm buscarProducto"
          placeholder="Buscar por clave o descripción">

        <input type="hidden"
          class="producto_id"
          name="componentes[${index}][idinventario]">

        <div class="list-group position-absolute z-3 d-none resultados"></div>
      </td>


      <td>
        <input type="number"
          class="form-control form-control-sm porcentaje"
          readonly>
      </td>

      <td class="text-center">
        <button type="button" class="btn btn-sm btn-danger btnEliminar">
          <i class="bi bi-trash"></i>
        </button>
      </td>
    `;

      tbody.appendChild(tr);

      actualizarTotales();
    });
    /* =============================
     BUSCADOR DE PRODUCTOS
  ============================= */

    document.addEventListener("input", function (e) {
      if (!e.target.classList.contains("buscarProducto")) return;

      const input = e.target;
      const query = input.value.trim();
      const contenedor = input.closest("td").querySelector(".resultados");

      if (query.length < 2) {
        contenedor.classList.add("d-none");
        contenedor.innerHTML = "";
        return;
      }

      const request = new XMLHttpRequest();
      request.open(
        "GET",
        base_url +
          "/Inv_inventario/buscarProductoKit?term=" +
          encodeURIComponent(query),
        true
      );
      request.send();

      request.onload = () => {
        if (request.status === 200) {
          const data = JSON.parse(request.responseText);
          contenedor.innerHTML = "";

          data.forEach((item) => {
            const a = document.createElement("a");
            a.className = "list-group-item list-group-item-action";
            a.innerHTML = `<strong>${item.cve_articulo}</strong> - ${item.descripcion}`;
            a.onclick = () => {
              input.value = item.descripcion;
              input.closest("td").querySelector(".producto_id").value =
                item.idinventario;
              contenedor.classList.add("d-none");
            };
            contenedor.appendChild(a);
          });

          contenedor.classList.remove("d-none");
        }
      };
    });

    /* =============================
     ELIMINAR FILA
  ============================= */
    tbody.addEventListener("click", (e) => {
      if (e.target.closest(".btnEliminar")) {
        e.target.closest("tr").remove();
        actualizarTotales();
      }
    });

    /* =============================
     RECÁLCULO
  ============================= */
    tbody.addEventListener("input", actualizarTotales);
  }

  function actualizarTotales() {
    let totalCantidad = 0;
    let partidas = 0;

    const filas = document.querySelectorAll("#tabla_componentes tbody tr");

    // 🔹 Sumar cantidades
    filas.forEach((tr) => {
      const cantidad = parseFloat(tr.querySelector(".cantidad")?.value || 0);
      if (cantidad > 0) {
        totalCantidad += cantidad;
        partidas++;
      }
    });

    // 🔹 Calcular porcentajes
    filas.forEach((tr) => {
      const cantidad = parseFloat(tr.querySelector(".cantidad")?.value || 0);
      const porcentajeInput = tr.querySelector(".porcentaje");

      if (cantidad > 0 && totalCantidad > 0) {
        const porcentaje = (cantidad / totalCantidad) * 100;
        porcentajeInput.value = porcentaje.toFixed(2);
      } else {
        porcentajeInput.value = "0.00";
      }
    });

    // 🔹 Mostrar totales
    document.getElementById("total_partidas").textContent = partidas;
    document.getElementById("total_kit").textContent = totalCantidad.toFixed(2);
  }

  /* =============================
     GUARDAR CONFIGURACIÓN KIT
  ============================= */
  document.getElementById("btnGuardarKit").addEventListener("click", () => {
    const kitid = document.getElementById("kitid").value;

    if (!kitid) {
      Swal.fire("Error", "Kit inválido", "error");
      return;
    }

    const formData = new FormData();
    formData.append("inventarioid", kitid);
    formData.append("precio", document.getElementById("precio").value || 0);
    formData.append(
      "descripcion",
      document.getElementById("descripcion_kit").value
    );

    document
      .querySelectorAll("#tabla_componentes tbody tr")
      .forEach((tr, i) => {
        const producto = tr.querySelector(".producto_id")?.value;
        const cantidad = tr.querySelector(".cantidad")?.value;
        const porcentaje = tr.querySelector(".porcentaje")?.value;

        if (producto && cantidad) {
          formData.append(`componentes[${i}][idinventario]`, producto);
          formData.append(`componentes[${i}][cantidad]`, cantidad);
          formData.append(`componentes[${i}][porcentaje]`, porcentaje || 0);
        }
      });

    fetch(base_url + "/Inv_inventario/setKitConfig", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((res) => {
        if (res.status) {
          Swal.fire("Correcto", res.msg, "success");

          // 🔹 Limpiar formulario kit
          document.getElementById("precio").value = "";
          document.getElementById("descripcion").value = "";

          // 🔹 Limpiar tabla de componentes
          const tbody = document.querySelector("#tabla_componentes tbody");
          tbody.innerHTML = "";

          // 🔹 Ocultar configuración
          document.getElementById("kit_config_container").style.display =
            "none";

          // 🔹 Limpiar kitid
          document.getElementById("kitid").value = "";

          // 🔄 Recargar tabla principal
          if (typeof tableInventarios !== "undefined") {
            tableInventarios.ajax.reload(null, false);
          }

          // 🔁 Volver a tabla principal
          setTimeout(() => {
            const tabTabla = document.querySelector(
              'a[data-bs-toggle="tab"][href="#listInventarios"]'
            );
            if (tabTabla) {
              bootstrap.Tab.getOrCreateInstance(tabTabla).show();
            }
          }, 300);
        } else {
          Swal.fire("Error", res.msg, "error");
        }
      });
  });
});

// ------------------------------------------------------------------------
//  VER EL DETALLE DEL INVENTARIO
// ------------------------------------------------------------------------
function fntViewInventario(idinventario) {
  let request = window.XMLHttpRequest
    ? new XMLHttpRequest()
    : new ActiveXObject("Microsoft.XMLHTTP");

  let ajaxUrl = base_url + "/Inv_inventario/getInventario/" + idinventario;
  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      let objData = JSON.parse(request.responseText);

      if (objData.status) {
        const data = objData.data;

        // Estado
        let estadoHtml =
          data.estado == 2
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-danger">Inactivo</span>';

        // Tipo
        let tipoTxt = "N/A";
        if (data.tipo_elemento === "P") tipoTxt = "Producto";
        if (data.tipo_elemento === "S") tipoTxt = "Servicio";
        if (data.tipo_elemento === "K") tipoTxt = "Kit";

        // Pintar datos en modal
        document.querySelector("#celClave").innerHTML = data.cve_articulo;
        document.querySelector("#celDescripcion").innerHTML = data.descripcion;
        document.querySelector("#celTipo").innerHTML = tipoTxt;
        document.querySelector("#celUnidadEntrada").innerHTML =
          data.unidad_entrada;
        document.querySelector("#celUnidadSalida").innerHTML =
          data.unidad_salida;
        document.querySelector("#celFactor").innerHTML = data.factor_unidades;
        document.querySelector("#celUbicacion").innerHTML = data.ubicacion;
        document.querySelector("#celPeso").innerHTML = data.peso;
        document.querySelector("#celVolumen").innerHTML = data.volumen;
        document.querySelector("#celSerie").innerHTML =
          data.serie === "S" ? "Sí" : "No";
        document.querySelector("#celLote").innerHTML =
          data.lote === "S" ? "Sí" : "No";
        document.querySelector("#celPedimiento").innerHTML =
          data.pedimiento === "S" ? "Sí" : "No";
        document.querySelector("#celFecha").innerHTML = data.fecha_creacion;
        document.querySelector("#celEstado").innerHTML = estadoHtml;

        // Mostrar modal
        $("#modalViewInventario").modal("show");
      } else {
        Swal.fire("Error", objData.msg, "error");
      }
    }
  };
}
