let tableInventarios;
let currentInventarioId = null;
let modoEdicion = false;
let idLineaEditando = null;
let inventarioActual = null;
let inventarioIdReal = null;

const rutas = {
  lineas: base_url + "/Inv_lineasdproducto/getSelectLineasProductos",
  impuestos: base_url + "/Inv_inventario/getSelectImpuestos",
  marcas: base_url + "/Inv_inventario/getSelectMarcas",
  save: base_url + "/Inv_inventario/setInventario",
};

document.addEventListener("DOMContentLoaded", () => {
  const modalInv = document.getElementById("modalConfigInventario");

  if (modalInv) {
    modalInv.addEventListener("hidden.bs.modal", function () {
      limpiarModalInventario();
    });
  }

  ocultarMovimientoInicial();
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
      { data: "estado" },
      { data: "options" },
    ],
    language: {
      url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json",
    },
  });

  //imagenes
  const contenedor = document.getElementById("contenedorImagenes");
  const btnAgregar = document.getElementById("btnAgregarImagen");

  let maxImagenes = 3;

  // 🔹 AGREGAR NUEVA IMAGEN
  btnAgregar.addEventListener("click", () => {
    const total = contenedor.querySelectorAll(".input-imagen").length;

    if (total >= maxImagenes) {
      Swal.fire(
        "Límite alcanzado",
        "Solo puedes subir máximo 3 imágenes",
        "warning",
      );
      return;
    }

    const div = document.createElement("div");
    div.classList.add("input-group", "mb-2");

    div.innerHTML = `
      <input type="file" name="imagenes[]" 
        class="form-control input-imagen"
        accept="image/*" capture="environment">

      <button class="btn btn-danger btnEliminarImagen" type="button">
        <i class="bi bi-trash"></i>
      </button>
    `;

    contenedor.appendChild(div);
  });

  // 🔹 ELIMINAR IMAGEN
  contenedor.addEventListener("click", (e) => {
    if (e.target.closest(".btnEliminarImagen")) {
      e.target.closest(".input-group").remove();
    }
  });

  //limpiar modal
  function limpiarModalInventario() {
    console.log("Limpiando modalConfigInventario...");

    // ---------- HIDDEN  de impuestos----------
    const hidImp = document.getElementById("imp_inventarioid");
    if (hidImp) hidImp.value = "";

    // ---------- HIDDEN  de proveedores----------
    const hidProv = document.getElementById("prov_inventarioid");
    if (hidProv) hidProv.value = "";

    // ---------- SELECT impuesto ----------
    const selImp = document.getElementById("cfg_impuesto");
    if (selImp) {
      selImp.innerHTML = `<option value="">Seleccione un impuesto</option>`;
      selImp.value = "";
    }

    // ---------- SELECT proveedor ----------
    const selProv = document.getElementById("cfg_proveedor");
    if (selProv) {
      selProv.innerHTML = `<option value="">Seleccione un proveedor</option>`;
      selProv.value = "";
    }

    // ---------- TABLAS ----------
    const t1 = document.getElementById("tbodyImpuestosCfg");
    if (t1) t1.innerHTML = "";

    const t2 = document.getElementById("tbodyFiscal");
    if (t2) t2.innerHTML = "";

    const t3 = document.getElementById("tbodyMonedas");
    if (t3) t3.innerHTML = "";

    const t4 = document.getElementById("tbodyLineasAsignadas");
    if (t4) t4.innerHTML = "";

    const t6 = document.getElementById("tbodyProveedoresCfg");
    if (t6) t6.innerHTML = "";

    // ---------- FORMS ----------
    document
      .querySelectorAll("#modalConfigInventario form")
      .forEach((f) => f.reset());

    // =====================================================
    // LIMPIEZA TAB FISCAL
    // =====================================================

    const sat = document.querySelector(".satSearch");
    if (sat) sat.value = "";

    const unidad = document.querySelector(".unidadSatInput");
    if (unidad) unidad.value = "";

    const fraccion = document.querySelector(".fraccionInput");
    if (fraccion) fraccion.value = "";

    const aduana = document.querySelector(".aduanaInput");
    if (aduana) aduana.value = "";

    const c1 = document.querySelector('[name="clave_sat"]');
    if (c1) c1.value = "";

    const d1 = document.querySelector('[name="desc_sat"]');
    if (d1) d1.value = "";

    const c2 = document.querySelector('[name="clave_unidad_sat"]');
    if (c2) c2.value = "";

    const d2 = document.querySelector('[name="desc_clave_unidad_sat"]');
    if (d2) d2.value = "";

    const c3 = document.querySelector('[name="clave_fraccion_sat"]');
    if (c3) c3.value = "";

    const d3 = document.querySelector('[name="desc_clave_fraccion_sat"]');
    if (d3) d3.value = "";

    const c4 = document.querySelector('[name="clave_aduana_sat"]');
    if (c4) c4.value = "";

    const d4 = document.querySelector('[name="desc_clave_aduana_sat"]');
    if (d4) d4.value = "";

    const bloque = document.getElementById("bloqueFiscalTabla");
    if (bloque) bloque.classList.add("d-none");

    // ---------- VOLVER A PRIMER TAB ----------
    const firstTab = document.querySelector("#modalConfigInventario .nav-link");
    if (firstTab) {
      new bootstrap.Tab(firstTab).show();
    }

    // ---------- RESET ID GLOBAL ----------
    currentInventarioId = null;
  }

  /* =============================
     CONFIGURACIÓN
  ============================= */

  const tabsConfig = {
    agregarProducto: {
      form: "#formInventarioProducto",
      selectLinea: "#lineaproductoid_producto",
      selectAlmacen: "#almacenid",
      tipo: "P",
    },
    agregarServicio: {
      form: "#formInventarioServicio",
      tipo: "S",
    },
    agregarKit: {
      form: "#formInventarioKit",
      tipo: "K",
    },
  };

  /* =============================
   NUEVO INVENTARIO
============================= */
  document
    .querySelector('a[href="#agregarProducto"]')
    ?.addEventListener("click", () => {
      resetFormularioInventario();
      cargarAlmacenes("#almacenid");
      cargarImpuestos("#idimpuesto"); //
      cargarMarcas("#idmarca"); //
      cargarProveedores("#id_proveedor"); //
    });

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

  // 🔥 ESTA LÍNEA ES LA CLAVE
  window.cargarLineas = cargarLineas;

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
  // 🔹 HACERLA GLOBAL
  window.cargarAlmacenes = cargarAlmacenes;

  function cargarImpuestos(selectId, selectedValue = "") {
    const select = document.querySelector(selectId);
    if (!select) return;

    const request = new XMLHttpRequest();
    request.open("GET", rutas.impuestos, true);
    request.send();

    request.onreadystatechange = () => {
      if (request.readyState === 4 && request.status === 200) {
        select.innerHTML = request.responseText;
        if (selectedValue) select.value = selectedValue;
      }
    };
  }
  // 🔹 HACERLA GLOBAL
  window.cargarImpuestos = cargarImpuestos;

  function cargarMarcas(selectId, selectedValue = "") {
    const select = document.querySelector(selectId);
    if (!select) return;

    const request = new XMLHttpRequest();
    request.open("GET", rutas.marcas, true);
    request.send();

    request.onreadystatechange = () => {
      if (request.readyState === 4 && request.status === 200) {
        select.innerHTML = request.responseText;
        if (selectedValue) select.value = selectedValue;
      }
    };
  }
  // 🔹 HACERLA GLOBAL
  window.cargarMarcas = cargarMarcas;

  function cargarProveedores(selectId, selectedValue = "") {
    const select = document.querySelector(selectId);
    if (!select) return;

    const request = new XMLHttpRequest();
    request.open("GET", rutas.impuestos, true);
    request.send();

    request.onreadystatechange = () => {
      if (request.readyState === 4 && request.status === 200) {
        select.innerHTML = request.responseText;
        if (selectedValue) select.value = selectedValue;
      }
    };
  }
  // 🔹 HACERLA GLOBAL
  window.cargarProveedores = cargarProveedores;

  /* =============================
     EVENTOS DE TABS
  ============================= */
  document.querySelectorAll('[data-bs-toggle="tab"]').forEach((tab) => {
    tab.addEventListener("shown.bs.tab", (e) => {
      const targetId = e.target.getAttribute("href").replace("#", "");
      const config = tabsConfig[targetId];

      //  SI CAMBIAS DE TAB → SIEMPRE LIMPIA
      resetFormularioInventario();

      //  SI CAMBIAS DE TAB → OCULTAR KIT SI NO ES KIT
      if (targetId !== "agregarKit") {
        const kitContainer = document.getElementById("kit_config_container");
        if (kitContainer) {
          kitContainer.style.display = "none";
        }
      }

      //  CARGAS NORMALES
      if (config) {
        if (config.selectAlmacen) {
          cargarAlmacenes(config.selectAlmacen);
        }

        cargarImpuestos("#idimpuesto");
        cargarMarcas("#idmarca");
        cargarProveedores("#id_proveedor");
      }

      //  CONTROL DEL BOTÓN
      const btnText = document.querySelector("#btnText");

      if (modoEdicion) {
        btnText.textContent = "ACTUALIZAR";
      } else {
        btnText.textContent = "GUARDAR";
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

      //  Crear FormData
      const formData = new FormData(form);

      //  Envío AJAX
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
            if (
              res.tipo === "P" ||
              res.tipo === "S" ||
              res.tipo === "C" ||
              res.tipo === "H" ||
              res.tipo === "R"
            ) {
              // Limpiar formularios
              form.reset();

              // 🔁 Volver a la pestaña de tabla
              const tabTabla = document.querySelector(
                'a[href="#listInventarios"]',
              );
              if (tabTabla) {
                new bootstrap.Tab(tabTabla).show();
              }
            }

            // 🔹 KIT
            if (res.tipo === "K") {
              inventarioIdReal = res.id; // 🔥 GUARDAR ID REAL

              const container = document.getElementById("kit_config_container");
              const inputKitId = document.getElementById("kitid");

              if (container && inputKitId) {
                inputKitId.value = "";
                container.style.display = "block";
                container.scrollIntoView({ behavior: "smooth" });
              }
            }
          } else {
            Swal.fire({
              icon: "warning",
              title: "Atención",
              text: res.msg,
              confirmButtonColor: "#3085d6",
            });

            const inputClave = form.querySelector("#cve_articulo");
            if (inputClave) inputClave.focus();

            return;
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
  // 🔹 GUARDAR UBICACION
  const form = document.getElementById("formUbicacionInventario");

  if (form) {
    form.addEventListener("submit", guardarUbicacion);
  } else {
    console.warn("No se encontró el formulario de ubicaciones");
  }

  // HABILITAR CLAVE ALTERNA
  document
    .getElementById("btn_habilitar_clave_alterna")
    .addEventListener("click", function () {
      const inputClave = document.getElementById("clave_alterna");
      const contenedorTipo = document.getElementById(
        "tipo_asignacion_container",
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
        true,
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

  /* =============================
     GUARDAR CONFIGURACIÓN KIT
  ============================= */
  document.getElementById("btnGuardarKit").addEventListener("click", () => {
    const kitid = document.getElementById("kitid").value || null;

    const formData = new FormData();
    const kitConfigId = document.getElementById("kitid").value;

    if (kitConfigId) {
      formData.append("kitid", kitConfigId);
    }

    formData.append("inventarioid", inventarioIdReal);
    formData.append("precio", document.getElementById("precio").value || 0);
    formData.append(
      "descripcion",
      document.getElementById("descripcion_kit").value,
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

    // 🔥 decidir si es editar o nuevo
    const url = "/Inv_inventario/setKitConfig";

    console.log("inventarioIdReal:", inventarioIdReal);
    console.log("kitid:", document.getElementById("kitid").value);

    fetch(base_url + url, {
      method: "POST",
      body: formData,
    })
      .then((res) => res.text()) // 🔥 CAMBIO IMPORTANTE
      .then((text) => {
        console.log("RESPUESTA DEL SERVIDOR:", text); // 🔥 VER ERROR REAL

        let res;
        try {
          res = JSON.parse(text);
        } catch (e) {
          Swal.fire(
            "Error",
            "El servidor devolvió un error (no es JSON)",
            "error",
          );
          return;
        }
        if (res.status) {
          Swal.fire("Correcto", res.msg, "success");

          // 🔹 Limpiar formulario kit
          document.getElementById("precio").value = "";
          document.getElementById("descripcion_kit").value = "";

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
              'a[data-bs-toggle="tab"][href="#listInventarios"]',
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

// ------------------------------------------------------------------------
//  VER EL DETALLE DEL INVENTARIO
// ------------------------------------------------------------------------
function fntViewInventario(idinventario) {
  let request = new XMLHttpRequest();
  let ajaxUrl = base_url + "/Inv_inventario/getInventario/" + idinventario;

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      let objData = JSON.parse(request.responseText);

      if (objData.status) {
        const data = objData.data;

        let estadoHtml =
          data.estado == 2
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-danger">Inactivo</span>';

        let tipoTxt = "N/A";
        if (data.tipo_elemento === "P") tipoTxt = "Producto";
        if (data.tipo_elemento === "S") tipoTxt = "Servicio";
        if (data.tipo_elemento === "K") tipoTxt = "Kit";
        if (data.tipo_elemento === "C") tipoTxt = "Componente";
        if (data.tipo_elemento === "H") tipoTxt = "Herramienta";

        document.querySelector("#celClave").innerHTML = data.cve_articulo;
        document.querySelector("#celDescripcion").innerHTML = data.descripcion;
        document.querySelector("#celNotas").innerHTML = data.notas;
        document.querySelector("#celTipo").innerHTML = tipoTxt;
        document.querySelector("#celUnidadEntrada").innerHTML =
          data.unidad_entrada;
        document.querySelector("#celUnidadSalida").innerHTML =
          data.unidad_salida;
        document.querySelector("#celFactor").innerHTML = data.factor_unidades;
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

        // ✅ CLAVES ALTERNAS
        let htmlClave = "Sin clave alterna";

        const claves = data.claves || [];

        if (claves.length > 0 && claves[0].cve_alterna) {
          htmlClave = claves
            .map((c) => {
              let tipo = "Interna";
              if (c.tipo_clave === "C") tipo = "Cliente";
              if (c.tipo_clave === "V") tipo = "Proveedor";
              if (c.tipo_clave === "I") tipo = "Interna";

              return `<span class="me-1">
                ${c.cve_alterna} (${tipo})
              </span>`;
            })
            .join("");
        }

        document.querySelector("#celClaveAlterna").innerHTML = htmlClave;

        // 🔥 IMÁGENES (YA CORRECTO)
        const contenedor = document.querySelector("#contenedorImagenesView");
        contenedor.innerHTML = "";

        // 🔹 contenedor tipo grid
        contenedor.classList.add("d-flex", "flex-wrap", "gap-2");

        if (data.imagenes && data.imagenes.length > 0) {
          data.imagenes.forEach((img) => {
            const ruta =
              base_url + "/Assets/uploads/inventario_imagenes/" + img.foto;

            const wrapper = document.createElement("div");
            wrapper.style.cursor = "pointer";

            const image = document.createElement("img");
            image.src = ruta;
            image.style.width = "120px";
            image.style.height = "120px";
            image.style.objectFit = "cover";
            image.classList.add("rounded", "shadow-sm", "border");

            // 🔥 hover effect
            image.onmouseover = () => {
              image.style.transform = "scale(1.05)";
              image.style.transition = "0.2s";
            };

            image.onmouseout = () => {
              image.style.transform = "scale(1)";
            };

            // 🔥 click para ver grande
            image.onclick = () => abrirImagenGrande(ruta);

            wrapper.appendChild(image);
            contenedor.appendChild(wrapper);
          });
        } else {
          contenedor.innerHTML = "<span class='text-muted'>Sin imágenes</span>";
        }

        $("#modalViewInventario").modal("show");
      } else {
        Swal.fire("Error", objData.msg, "error");
      }
    }
  };
}

function abrirImagenGrande(ruta) {
  const img = document.getElementById("imgGrande");
  img.src = ruta;

  const modal = new bootstrap.Modal(
    document.getElementById("modalImagenGrande"),
  );

  modal.show();
}

function fntEditInventario(idinventario) {
  modoEdicion = true; // 🔥 ACTIVAR MODO EDICIÓN

  bloquearTipoElemento();
  ocultarMovimientoInicial();

  const request = new XMLHttpRequest();
  request.open(
    "GET",
    base_url + "/Inv_inventario/getInventario/" + idinventario,
    true,
  );
  request.send();

  request.onload = () => {
    const res = JSON.parse(request.responseText);

    if (!res.status) {
      Swal.fire("Error", res.msg, "error");
      return;
    }

    const data = res.data;

    abrirTabEdicion(data.tipo_elemento);
    llenarFormularioInventario(data);

    if (data.tipo_elemento === "K") {
      cargarKitParaEdicion(data.idinventario);
    }

    // 🔥 FORZAR BOTÓN
    document.querySelector("#btnText").textContent = "ACTUALIZAR";
  };
}

function cargarKitParaEdicion(idinventario) {
  inventarioIdReal = idinventario; // 🔥 IMPORTANTE
  // 🔥 CAMBIAR BOTÓN
  const btnText = document.querySelector("#btnText");
  if (btnText) btnText.textContent = "ACTUALIZAR";
  fetch(base_url + "/Inv_inventario/getKitCompleto/" + idinventario)
    .then((res) => res.json())
    .then((res) => {
      if (!res.status) {
        Swal.fire("Aviso", res.msg, "warning");
        return;
      }

      const { config, detalle } = res.data;

      // 🔹 Mostrar contenedor
      const container = document.getElementById("kit_config_container");
      container.style.display = "block";

      // 🔹 Set header
      document.getElementById("kitid").value = config.idkitconfig;
      document.getElementById("precio").value = config.precio;
      document.getElementById("descripcion_kit").value = config.descripcion;

      const tbody = document.querySelector("#tabla_componentes tbody");
      tbody.innerHTML = "";

      let index = 0;

      detalle.forEach((item) => {
        index++;

        const tr = document.createElement("tr");

        tr.innerHTML = `
          <td>
            <input type="number" class="form-control form-control-sm cantidad"
              value="${item.cantidad}">
          </td>

          <td>
            <input type="text" class="form-control form-control-sm"
              value="${item.cve_articulo} - ${item.descripcion}" readonly>

            <input type="hidden" class="producto_id"
              value="${item.producto_id}">
          </td>

          <td>
            <input type="number" class="form-control form-control-sm porcentaje"
              value="${item.porcentaje}" readonly>
          </td>

          <td class="text-center">
            <button type="button" class="btn btn-sm btn-danger btnEliminar">
              🗑
            </button>
          </td>
        `;

        tbody.appendChild(tr);
      });

      actualizarTotales();
    });
}

function abrirTabEdicion(tipo) {
  let tabId = null;

  if (tipo === "P" || tipo === "C" || tipo === "H" || tipo === "R") {
    tabId = "#agregarProducto";
  }

  if (tipo === "S") {
    tabId = "#agregarServicio";
  }

  if (tipo === "K") {
    tabId = "#agregarKit";
  }

  if (tabId) {
    const tab = document.querySelector(`a[href="${tabId}"]`);
    if (tab) {
      new bootstrap.Tab(tab).show();
    }
  }
}

function llenarFormularioInventario(data) {
  // 🔥 RESET CLAVE ALTERNA SIEMPRE
  const inputClave = document.getElementById("clave_alterna");
  const tipoSelect = document.getElementById("tipo_asignacion");
  const contenedorTipo = document.getElementById("tipo_asignacion_container");

  if (inputClave) {
    inputClave.value = "";
    inputClave.disabled = true;
  }

  if (tipoSelect) {
    tipoSelect.value = "";
  }

  if (contenedorTipo) {
    contenedorTipo.style.display = "none";
  }
  let form = null;

  // 🔹 marcar tipo_elemento (radio)
  const radios = document.querySelectorAll('input[name="tipo_elemento"]');

  radios.forEach((r) => {
    r.checked = r.value === data.tipo_elemento;
  });

  // 🔹 detectar formulario activo
  if (
    data.tipo_elemento === "P" ||
    data.tipo_elemento === "C" ||
    data.tipo_elemento === "H" ||
    data.tipo_elemento === "R"
  ) {
    form = document.querySelector("#formInventarioProducto");
  }

  if (data.tipo_elemento === "S") {
    form = document.querySelector("#formInventarioServicio");
  }

  if (data.tipo_elemento === "K") {
    form = document.querySelector("#formInventarioKit");
  }

  if (!form) return;

  // 🔹 helper local
  const set = (selector, value) => {
    const el = form.querySelector(selector);
    if (el && value !== undefined && value !== null) {
      el.value = value;
    }
  };

  // 🔹 llenar campos
  set('[name="idinventario"]', data.idinventario);
  set('[name="cve_articulo"]', data.cve_articulo);
  set('[name="descripcion"]', data.descripcion);
  set('[name="notas"]', data.notas);
  set('[name="unidad_entrada"]', data.unidad_entrada);
  set('[name="unidad_salida"]', data.unidad_salida);
  set('[name="factor_unidades"]', data.factor_unidades);
  set('[name="tiempo_surtido"]', data.tiempo_surtido);
  set('[name="peso"]', data.peso);
  set('[name="volumen"]', data.volumen);
  set('[name="unidad_empaque"]', data.unidad_empaque);
  set('[name="ultimo_costo"]', data.ultimo_costo);
  set('[name="ubicacion"]', data.ubicacion);
  set('[name="estado"]', data.estado);

  // CARGAR MARCA SELECCIONADA
  cargarMarcas("#idmarca", data.idmarca);

  //  CHECKS
  document.getElementById("serie").checked = data.serie === "S";
  document.getElementById("lote").checked = data.lote === "S";
  document.getElementById("pedimiento").checked = data.pedimiento === "S";

  //  CLAVE ALTERNA
  if (data.claves && data.claves.length > 0) {
    const clave = data.claves[0];

    const inputClave = document.getElementById("clave_alterna");
    const tipoSelect = document.getElementById("tipo_asignacion");
    const contenedorTipo = document.getElementById("tipo_asignacion_container");

    inputClave.disabled = false;
    inputClave.value = clave.cve_alterna;

    if (clave.tipo) {
      contenedorTipo.style.display = "block";
      tipoSelect.value = clave.tipo;
    }
  }
}

function bloquearTipoElemento() {
  document
    .querySelectorAll('input[name="tipo_elemento"]')
    .forEach((radio) => {});
}

function resetFormularioInventario() {
  // 🔹 Reset TODOS los forms
  document
    .querySelectorAll(
      `
    #formInventarioProducto,
    #formInventarioServicio,
    #formInventarioKit
  `,
    )
    .forEach((form) => form.reset());

  // 🔹 Limpiar IDs (modo edición)
  document
    .querySelectorAll('[name="idinventario"]')
    .forEach((el) => (el.value = ""));

  // 🔹 Reset selects
  ["#almacenid"].forEach((sel) => {
    const s = document.querySelector(sel);
    if (s) s.innerHTML = "";
  });

  // 🔹 Reset botón
  const btnText = document.querySelector("#btnText");
  if (btnText) btnText.textContent = "GUARDAR";

  // 🔹 Quitar modo edición
  modoEdicion = false;

  // 🔹 Ocultar kit
  const kitContainer = document.getElementById("kit_config_container");
  if (kitContainer) kitContainer.style.display = "none";

  // 🔹 Limpiar tabla kit
  const tbody = document.querySelector("#tabla_componentes tbody");
  if (tbody) tbody.innerHTML = "";

  // 🔹 Limpiar kitid
  const kitid = document.getElementById("kitid");
  if (kitid) kitid.value = "";

  // 🔥 RESET CLAVE ALTERNA
  const inputClave = document.getElementById("clave_alterna");
  const tipoSelect = document.getElementById("tipo_asignacion");
  const contenedorTipo = document.getElementById("tipo_asignacion_container");

  if (inputClave) {
    inputClave.value = "";
    inputClave.disabled = true;
  }

  if (tipoSelect) {
    tipoSelect.value = "";
  }

  if (contenedorTipo) {
    contenedorTipo.style.display = "none";
  }
}

function setValue(selector, value) {
  const el = document.querySelector(selector);
  if (el !== null && value !== undefined && value !== null) {
    el.value = value;
  }
}

function ocultarMovimientoInicial() {
  const bloque = document.getElementById("bloqueMovimientoInicial");
  if (bloque) bloque.classList.add("d-none");

  ["almacenid", "cantidad_inicial", "costo"].forEach((id) => {
    const el = document.getElementById(id);
    if (el) {
      el.disabled = true;
      el.value = "";
    }
  });
}

function mostrarMovimientoInicial() {
  const bloque = document.getElementById("bloqueMovimientoInicial");
  if (bloque) bloque.classList.remove("d-none");

  ["almacenid", "cantidad_inicial", "costo"].forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.disabled = false;
  });
}

//---------------------------------------------------------------------------------------------------------------------
//------------------configuracio del inventario PCH para asignarle monedas, lotes/pedimentos/etc-----------------------
//---------------------------------------------------------------------------------------------------------------------

function fntConfigInventario(idinventario) {
  currentInventarioId = idinventario; // 👈 AQUI

  fetch(base_url + "/Inv_inventario/getInventario/" + idinventario)
    .then((res) => res.json())
    .then((res) => {
      if (!res.status) return;

      const d = res.data;

      document.getElementById("cfg_cve").innerText = d.cve_articulo;
      document.getElementById("cfg_desc").innerText = d.descripcion;
      let tipo = d.tipo_elemento;
      switch (tipo) {
        case "P":
          tipo = "Producto";
          break;
        case "H":
          tipo = "Herramienta";
          break;
        case "C":
          tipo = "Componente";
          break;
        case "R":
          tipo = "Refacción";
          break;
      }
      document.getElementById("cfg_tipo").innerText = tipo;

      const modal = document.getElementById("modalConfigInventario");
      bootstrap.Modal.getOrCreateInstance(modal).show();

      setTimeout(() => {
        cargarTabMoneda(idinventario);
        cargartabPrecio(idinventario);
        cargarTabLineas(idinventario);
        refrescarFiscal(idinventario);
        cargarTabImpuestos(idinventario);
        cargarTabUbicaciones(idinventario);
        cargarTabProveedores(idinventario);
      }, 150);
    });
}
//-----------------------------------------------------------------------------------------------------------------
//------------------------------------------------------MONEDAS----------------------------------------------------
//-----------------------------------------------------------------------------------------------------------------
function cargarTabMoneda(idinventario) {
  fetch(base_url + "/Inv_moneda/getSelectMonedas")
    .then((res) => res.text())
    .then((html) => {
      const cont = document.getElementById("contentMoneda");
      if (!cont) return;

      cont.innerHTML = `
        <div class="row g-3 mb-3">

          <div class="col-md-5">
            <label class="form-label">Moneda</label>
            <select id="cfg_moneda" class="form-select">
              ${html}
            </select>
          </div>

          <div class="col-md-5">
            <label class="form-label">Tipo de cambio</label>
            <input type="number" step="0.0001" id="cfg_tipo_cambio" class="form-control">
          </div>

          <div class="col-md-2 align-self-end">
            <button class="btn btn-primary w-100"
              onclick="guardarMoneda(${idinventario})">
              Guardar
            </button>
          </div>

        </div>

        <div class="mt-2">
        <br/>
          <h5>Monedas asignadas</h5>
          <table class="table table-striped table-bordered">
            <thead style="background-color: #ff896534;">
              <tr>
                <th>ID</th>
                <th>Moneda</th>
                <th>Tipo de cambio</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody id="tbodyMonedas"></tbody>
          </table>
        </div>
      `;

      // ✅ ahora sí existe el tbody
      refrescarTablaMonedas(idinventario);
    })
    .catch((err) => console.error(err));
}

//-----------------------------------------------------------------------------------------------------------------
//------------------------------------------------------Precios----------------------------------------------------
//-----------------------------------------------------------------------------------------------------------------
function cargartabPrecio(idinventario) {
  fetch(base_url + "/Inv_precios/getSelectPrecios")
    .then((res) => res.text())
    .then((html) => {
      const cont = document.getElementById("contentPrecio");
      if (!cont) return;

      cont.innerHTML = `
        <div class="row g-3 mb-3">

          <div class="col-md-5">
            <label class="form-label">Catalogo de precios</label>
            <select id="cfg_precio" class="form-select">
              ${html}
            </select>
          </div>

          <div class="col-md-5">
            <label class="form-label">Precio actual</label>
            <input type="number" step="0.01" id="cfg_precio_num" class="form-control">
          </div>

          <div class="col-md-2 align-self-end">
            <button class="btn btn-primary w-100"
              onclick="guardarPrecio(${idinventario})">
              Guardar
            </button>
          </div>

        </div>

        <div class="mt-2">
        <br/>
          <h5>Precios asignados</h5>
          <table class="table table-striped table-bordered">
            <thead style="background-color: #ff896534;">
              <tr>
                <th>Clave</th>
                <th>Descripción</th>
                <th>Precio</th>
                <th>Fecha</th>
              </tr>
            </thead>
            <tbody id="tbodyPrecios"></tbody>
          </table>
        </div>
      `;

      refrescarTablaPrecios(idinventario);
    });
}
function guardarPrecio(idinventario) {
  let select = document.getElementById("cfg_precio");
  let idprecio = select.value;
  let precio = document.getElementById("cfg_precio_num").value;

  if (!precio || precio <= 0) {
    Swal.fire("Atención", "Ingrese un precio válido", "warning");
    return;
  }

  let formData = new FormData();
  formData.append("inventarioid", idinventario);
  formData.append("idprecio", idprecio);
  formData.append("precio", precio);

  fetch(base_url + "/Inv_inventario/setPrecioInventario", {
    method: "POST",
    body: formData,
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.status) {
        Swal.fire("Correcto", data.msg, "success");

        // ✅ LIMPIAR SELECT
        select.selectedIndex = 0;

        // ✅ LIMPIAR INPUT PRECIO
        document.getElementById("cfg_precio_num").value = "";

        // O también puedes usar:
        // select.value = "";

        refrescarTablaPrecios(idinventario);
      } else {
        Swal.fire("Error", data.msg, "error");
      }
    });
}

function refrescarTablaPrecios(idinventario) {
  fetch(base_url + "/Inv_inventario/getPreciosAsignados/" + idinventario)
    .then((res) => res.json())
    .then((data) => {
      let tbody = document.getElementById("tbodyPrecios");
      if (!tbody) return;

      tbody.innerHTML = "";

      if (data.status && data.data.length > 0) {
        data.data.forEach((item) => {
          tbody.innerHTML += `
            <tr>
              <td>${item.cve_precio}</td>
              <td>${item.descripcion}</td>
              <td>$${item.precio}</td>
              <td>${item.fecha_creacion}</td>
            </tr>
          `;
        });
      } else {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center">Sin precios asignados</td></tr>`;
      }
    });
}

//-----------------------------------------------------------------------------------------------------------------
//------------------------------------------------------LINEAS----------------------------------------------------
//-----------------------------------------------------------------------------------------------------------------
function cargarTabLineas(idinventario) {
  fetch(base_url + "/Inv_inventario/getSelectLineas")
    .then((res) => res.text())
    .then((htmlLineas) => {
      const cont = document.getElementById("contentLinea");

      cont.innerHTML = `
        <div class="row g-3 mb-3">

          <!-- LINEA -->
          <div class="col-md-5">
            <label class="form-label">Línea</label>
            <select id="cfg_linea" class="form-select">
              ${htmlLineas}
            </select>
          </div>

          <!-- SUBLINEA -->
          <div class="col-md-5">
            <label class="form-label">Sublínea</label>
            <select id="cfg_sublinea" class="form-select">
              <option value="">Seleccione una sublínea</option>
            </select>
          </div>

          <div class="col-md-2 align-self-end">
            <button class="btn btn-primary w-100" onclick="guardarLinea(${idinventario})">
              Guardar
            </button>
          </div>
        </div>

        <div class="mt-2">
        <br/>
          <h5>Líneas asignadas</h5>
          <table class="table table-striped table-bordered">
            <thead style="background-color: #ff896534;">
              <tr>
                <th>ID</th>
                <th>Línea</th>
                <th>Sublínea</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody id="tbodyLineasAsignadas"></tbody>
          </table>
        </div>
      `;

      // 🔥 evento cambio línea
      document
        .getElementById("cfg_linea")
        .addEventListener("change", function () {
          cargarSublineasPorLinea(this.value);
        });

      refrescarTablaLineas(idinventario);
    });
}

function cargarSublineasPorLinea(idLinea) {
  return fetch(base_url + "/Inv_lineasdproducto/getSublineas/" + idLinea)
    .then((res) => res.json())
    .then((data) => {
      let options = `<option value="">Seleccione sublínea</option>`;

      data.forEach((s) => {
        options += `<option value="${s.idsublineaproducto}">
          ${s.cve_sublinea_producto} - ${s.descripcion}
        </option>`;
      });

      document.getElementById("cfg_sublinea").innerHTML = options;
    });
}

function refrescarTablaLineas(idinventario) {
  fetch(base_url + "/Inv_inventario/getLineasAsignadas/" + idinventario)
    .then((res) => res.json())
    .then((data) => {
      const tbody = document.getElementById("tbodyLineasAsignadas");
      tbody.innerHTML = "";

      data.data.forEach((linea) => {
        tbody.innerHTML += `
          <tr>
            <td>${linea.id_inv_linea}</td>
            <td>${linea.linea}</td>
            <td>${linea.sublinea}</td>
            <td>${linea.fecha_creacion}</td>
            <td>${linea.estado == 2 ? "Activo" : "Inactivo"}</td>
            <td>
              <button class="btn btn-sm btn-warning"
                onclick="editarLinea(${linea.id_inv_linea}, ${linea.idsublineaproducto}, ${linea.idlinea})">
                Editar
              </button>
            </td>
          </tr>
        `;
      });
    });
}

function guardarLinea(idinventario) {
  const sublinea = document.getElementById("cfg_sublinea").value;

  if (!sublinea) {
    Swal.fire("Aviso", "Selecciona una sublínea", "warning");
    return;
  }

  const fd = new FormData();

  if (modoEdicion) {
    fd.append("id_inv_linea", idLineaEditando);
    fd.append("sublineaproductoid", sublinea);

    fetch(base_url + "/Inv_inventario/updateLinea", {
      method: "POST",
      body: fd,
    })
      .then((r) => r.json())
      .then((res) => {
        if (res.status) {
          Swal.fire("Actualizado", res.msg, "success");
          limpiarFormularioLinea();
          refrescarTablaLineas(idinventario);
        } else {
          Swal.fire("Error", res.msg, "warning"); // 🔥 IMPORTANTE
        }
      });
  } else {
    fd.append("inventarioid", idinventario);
    fd.append("sublineaproductoid", sublinea);

    fetch(base_url + "/Inv_inventario/setLinea", {
      method: "POST",
      body: fd,
    })
      .then((r) => r.json())
      .then((res) => {
        if (res.status) {
          Swal.fire("OK", res.msg, "success");
          limpiarFormularioLinea();
          refrescarTablaLineas(idinventario);
        } else {
          Swal.fire("Error", res.msg, "warning"); // 🔥 ESTA LÍNEA FALTABA
        }
      });
  }
}

function editarLinea(id_inv_linea, idsublinea, idlinea) {
  const selectLinea = document.getElementById("cfg_linea");
  const selectSublinea = document.getElementById("cfg_sublinea");
  const btn = document.querySelector("#contentLinea button");

  // 🔹 seleccionar línea
  selectLinea.value = idlinea;

  // 🔹 cargar sublíneas y luego seleccionar la correcta
  cargarSublineasPorLinea(idlinea).then(() => {
    selectSublinea.value = idsublinea;
  });

  // 🔹 activar modo edición
  modoEdicion = true;
  idLineaEditando = id_inv_linea;

  btn.textContent = "Actualizar Línea";
  btn.classList.remove("btn-primary");
  btn.classList.add("btn-warning");
}

function limpiarFormularioLinea() {
  document.getElementById("cfg_linea").selectedIndex = 0;

  document.getElementById("cfg_sublinea").innerHTML =
    '<option value="">Seleccione una sublínea</option>';

  // 🔥 RESET MODO EDICIÓN
  modoEdicion = false;
  idLineaEditando = null;

  // 🔥 RESET BOTÓN
  const btn = document.querySelector("#contentLinea button");
  btn.textContent = "Guardar";
  btn.classList.remove("btn-warning");
  btn.classList.add("btn-primary");
}

//-----------------------------------------------------------------------------------------------------------------
//------------------------------------------------------MONEDAS----------------------------------------------------
//-----------------------------------------------------------------------------------------------------------------

function guardarMoneda(idinventario) {
  const moneda = document.getElementById("cfg_moneda").value;
  const tipoCambio = document.getElementById("cfg_tipo_cambio").value;

  if (!moneda) {
    Swal.fire("Aviso", "Selecciona una moneda", "warning");
    return;
  }

  const fd = new FormData();
  fd.append("inventarioid", idinventario);
  fd.append("idmoneda", moneda);
  fd.append("tipo_cambio", tipoCambio);

  fetch(base_url + "/Inv_inventario/setMoneda", {
    method: "POST",
    body: fd,
  })
    .then((r) => r.json())
    .then((res) => {
      if (res.status) {
        Swal.fire("OK", res.msg, "success");

        document.getElementById("cfg_tipo_cambio").value = "";
        document.getElementById("cfg_moneda").value = "";

        // 🔹 REFRESCAR LA TABLA
        refrescarTablaMonedas(idinventario);
      } else {
        Swal.fire("Error", res.msg, "error");
      }
    });
}

function refrescarTablaMonedas(idinventario) {
  fetch(base_url + "/Inv_inventario/getMonedasAsignadas/" + idinventario)
    .then((res) => res.json())
    .then((data) => {
      console.log("Monedas asignadas:", data);

      const tbody = document.getElementById("tbodyMonedas");
      if (!tbody) return;

      tbody.innerHTML = "";

      if (!data.data || data.data.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="4" class="text-center">No hay monedas asignadas</td>
          </tr>`;
        return;
      }

      data.data.forEach((moneda) => {
        tbody.innerHTML += `
          <tr>
            <td>${moneda.idmoneda}</td>
            <td>${moneda.descripcion}</td>
            <td>${moneda.tipo_cambio ?? ""}</td>
            <td>${moneda.estado == 2 ? "Activo" : "Inactivo"}</td>
          </tr>
        `;
      });
    });
}

// Cuando se abra el modal, refrescar la tabla de monedas
$("#modalConfigInventario").on("shown.bs.modal", function () {
  if (currentInventarioId) {
    refrescarTablaMonedas(currentInventarioId);
  }
});

//---------------------------------------------------------------------------------------------------------------------------------
//-----------------------------------------------------------------lote y pedimento------------------------------------------------
//---------------------------------------------------------------------------------------------------------------------------------

document.addEventListener("change", (e) => {
  if (e.target.id === "ltpd_tipo") {
    let tipo = e.target.value;
    let cont = document.getElementById("ltpdCampos");
    if (!cont) return;

    if (tipo === "L") {
      cont.innerHTML = htmlLote();
      cargarAlmacenesLtpd();
    }

    if (tipo === "P") {
      cont.innerHTML = htmlPedimento();
      cargarAlmacenesLtpd();
    }
  }
});

function htmlLote() {
  return `
  <div class="row g-3">

    <div class="col-md-4">
      <label class="form-label">Almacén</label>
      <select id="ltpd_almacen" class="form-select"></select>
    </div>

    <div class="col-md-4">
      <label class="form-label">Cantidad</label>
      <input type="number" step="any" id="ltpd_cantidad" class="form-control">
    </div>

    <div class="col-md-4">
      <label class="form-label">Lote</label>
      <input type="text" id="ltpd_lote" class="form-control">
    </div>

    <div class="col-md-4">
      <label class="form-label">Fecha producción</label>
      <input type="date" id="ltpd_fprod" class="form-control">
    </div>

    <div class="col-md-4">
      <label class="form-label">Fecha caducidad</label>
      <input type="date" id="ltpd_fcad" class="form-control">
    </div>

    <div class="col-md-4">
      <label class="form-label">Observaciones</label>
      <input type="text" id="ltpd_obs" class="form-control">
    </div>

  </div>
  `;
}

function htmlPedimento() {
  return `
  <div class="row g-3">

    <div class="col-md-4">
      <label class="form-label">Almacén</label>
      <select id="ltpd_almacen" class="form-select"></select>
    </div>

    <div class="col-md-4">
      <label class="form-label">Cantidad</label>
      <input type="number" step="any" id="ltpd_cantidad" class="form-control">
    </div>

    <div class="col-md-4">
      <label class="form-label">Pedimento</label>
      <input type="text" id="ltpd_pedimento" class="form-control">
    </div>

    <div class="col-md-4">
      <label class="form-label">Pedimento SAT</label>
      <input type="text" id="ltpd_pedimentosat" class="form-control">
    </div>

    <div class="col-md-4">
      <label class="form-label">Fecha frontera</label>
      <input type="date" id="ltpd_ffrontera" class="form-control">
    </div>

    <div class="col-md-4">
      <label class="form-label">Aduana</label>
      <input type="text" id="ltpd_aduana" class="form-control">
    </div>

    <div class="col-md-4">
      <label class="form-label">GLN</label>
      <input type="text" id="ltpd_gln" class="form-control">
    </div>

    <div class="col-md-4">
      <label class="form-label">Ciudad</label>
      <input type="text" id="ltpd_ciudad" class="form-control">
    </div>

    <div class="col-md-4">
      <label class="form-label">Fecha producción</label>
      <input type="date" id="ltpd_fprod" class="form-control">
    </div>

    <div class="col-md-4">
      <label class="form-label">Fecha caducidad</label>
      <input type="date" id="ltpd_fcad" class="form-control">
    </div>

    <div class="col-md-4">
      <label class="form-label">Observaciones</label>
      <input type="text" id="ltpd_obs" class="form-control">
    </div>

  </div>
  `;
}
function cargarAlmacenesLtpd() {
  fetch(base_url + "/Inv_almacenes/getSelectAlmacenes")
    .then((r) => r.text())
    .then((html) => {
      let sel = document.getElementById("ltpd_almacen");
      if (sel) sel.innerHTML = html;
    });
}

function guardarLtpd() {
  const tipo = document.getElementById("ltpd_tipo").value;

  const formData = new FormData();
  formData.append("inventarioid", currentInventarioId);
  formData.append("almacenid", document.getElementById("ltpd_almacen").value);

  if (tipo === "L") {
    formData.append(
      "lote_cantidad",
      document.getElementById("ltpd_cantidad").value,
    );
    formData.append("lote_lote", document.getElementById("ltpd_lote").value);
    formData.append(
      "lote_fecha_produccion",
      document.getElementById("ltpd_fprod").value,
    );
    formData.append(
      "lote_fecha_caducidad",
      document.getElementById("ltpd_fcad").value,
    );
    formData.append(
      "cve_observacion",
      document.getElementById("ltpd_obs").value || "",
    );

    fetch(base_url + "/Inv_lotespedimentos/setLote", {
      method: "POST",
      body: formData,
    })
      .then((r) => r.json())
      .then((res) => {
        if (res.status) {
          Swal.fire("OK", res.msg, "success");
          refrescarTablaLtpd();
          // ✅ LIMPIAR
          document
            .querySelector("#ltpdCampos")
            .querySelectorAll("input")
            .forEach((i) => (i.value = ""));
        } else {
          Swal.fire("Error", res.msg, "error");
        }
      });
  }

  if (tipo === "P") {
    formData.append(
      "ped_cantidad",
      document.getElementById("ltpd_cantidad").value,
    );
    formData.append(
      "pedimento",
      document.getElementById("ltpd_pedimento").value,
    );
    formData.append(
      "pedimento_SAT",
      document.getElementById("ltpd_pedimentosat").value,
    );
    formData.append(
      "ped_fecha_produccion",
      document.getElementById("ltpd_fprod").value,
    );
    formData.append(
      "ped_fecha_caducidad",
      document.getElementById("ltpd_fcad").value,
    );
    formData.append(
      "fecha_aduana",
      document.getElementById("ltpd_ffrontera").value,
    );
    formData.append(
      "nombre_aduana",
      document.getElementById("ltpd_aduana").value,
    );
    formData.append("gln", document.getElementById("ltpd_gln").value);
    formData.append("ciudad", document.getElementById("ltpd_ciudad").value);
    formData.append(
      "cve_observacion",
      document.getElementById("ltpd_obs").value || "",
    );

    fetch(base_url + "/Inv_lotespedimentos/setPedimento", {
      method: "POST",
      body: formData,
    })
      .then((r) => r.json())
      .then((res) => {
        if (res.status) {
          Swal.fire("OK", res.msg, "success");
          refrescarTablaLtpd();
          // ✅ LIMPIAR
          document
            .querySelector("#ltpdCampos")
            .querySelectorAll("input")
            .forEach((i) => (i.value = ""));
        } else {
          Swal.fire("Error", res.msg, "error");
        }
      });
  }
}
function refrescarTablaLtpd() {
  fetch(
    base_url + "/Inv_lotespedimentos/getLtpdAsignados/" + currentInventarioId,
  )
    .then((r) => r.json())
    .then((res) => {
      console.log("LTPD:", res);

      const tbody = document.getElementById("tbodyLtpd");
      if (!tbody) {
        console.warn("No existe tbodyLtpd");
        return;
      }

      tbody.innerHTML = "";

      if (!res.data || res.data.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="7" class="text-center">No hay registros</td>
          </tr>`;
        return;
      }

      res.data.forEach((r) => {
        tbody.innerHTML += `
          <tr>
            <td>${r.tipo === "L" ? "Lote" : "Pedimento"}</td>
            <td>${r.almacen}</td>
            <td>${r.clave}</td>
            <td>${r.cantidad}</td>
            <td>${r.fecha_produccion_lote ?? ""}</td>
            <td>${r.fecha_caducidad ?? ""}</td>
            <td>${r.estado == 2 ? "Activo" : "Inactivo"}</td>
          </tr>
        `;
      });
    });
}
document.addEventListener("shown.bs.tab", function (e) {
  if (e.target.getAttribute("href") === "#tabLtpd") {
    if (currentInventarioId) {
      refrescarTablaLtpd();
    } else {
      console.warn("currentInventarioId vacío");
    }
  }
});

//---------------------------------------------------------------------------------------------------------------------------------
//-----------------------------------------------------------------Datos fiscales SAT----------------------------------------------
//---------------------------------------------------------------------------------------------------------------------------------

//********************CLAVE SAT PRODUCTO O SERVICIO *************************/
document.querySelector(".btnsatSearch").addEventListener("click", () => {
  new bootstrap.Modal(document.getElementById("modalSAT")).show();
  document.querySelector("#satSearchInput").focus();
});

document.getElementById("modalSAT").addEventListener("shown.bs.modal", () => {
  document.querySelector("#satSearchInput").value = "";
  document.querySelector("#satResultados").innerHTML = "";
});

document
  .querySelector("#satSearchInput")
  .addEventListener("input", function () {
    let term = this.value.trim();
    if (term.length < 2) return;

    fetch(
      base_url + "/Inv_inventario/searchSAT?term=" + encodeURIComponent(term),
    )
      .then((r) => r.json())
      .then((data) => {
        let html = "";

        data.forEach((g) => {
          html += `<div class="mb-3">
          <div class="fw-bold text-primary">${g.clase}</div>`;

          g.items.forEach((i) => {
            html += `
            <div class="sat-item ps-3 py-1"
                 data-clave="${i.clave}"
                 data-desc="${i.descripcion}">
              ${i.clave} - ${i.descripcion}
            </div>`;
          });

          html += `</div>`;
        });

        document.querySelector("#satResultados").innerHTML = html;
      });
  });

document
  .querySelector("#satResultados")
  .addEventListener("click", function (e) {
    let item = e.target.closest(".sat-item");
    if (!item) return;

    document.querySelector('[name="clave_sat"]').value = item.dataset.clave;
    document.querySelector('[name="desc_sat"]').value = item.dataset.desc;
    document.querySelector(".satSearch").value = item.dataset.clave;

    bootstrap.Modal.getInstance(document.getElementById("modalSAT")).hide();
  });

// ================== UNIDAD SAT =====================
document.querySelector(".btnUnidadSat").addEventListener("click", () => {
  new bootstrap.Modal(document.getElementById("modalUNIDSAT")).show();
  document.querySelector("#unidadSatSearchInput").focus();
});

document
  .getElementById("modalUNIDSAT")
  .addEventListener("shown.bs.modal", () => {
    document.querySelector("#unidadSatSearchInput").value = "";
    document.querySelector("#unidadSatResultados").innerHTML = "";
  });

document
  .querySelector("#unidadSatSearchInput")
  .addEventListener("input", function () {
    let term = this.value.trim();
    if (term.length < 2) return;

    fetch(
      base_url +
        "/Inv_inventario/searchUNIDADSAT?term=" +
        encodeURIComponent(term),
    )
      .then((r) => r.json())
      .then((data) => {
        let html = "";

        data.forEach((i) => {
          html += `
          <div class="satunidad-item ps-3 py-1"
               data-clave="${i.clave}"
               data-desc="${i.descripcion}">
            ${i.clave} - ${i.descripcion}
          </div>`;
        });

        document.querySelector("#unidadSatResultados").innerHTML = html;
      });
  });

document
  .querySelector("#unidadSatResultados")
  .addEventListener("click", function (e) {
    let item = e.target.closest(".satunidad-item");
    if (!item) return;

    document.querySelector('[name="clave_unidad_sat"]').value =
      item.dataset.clave;
    document.querySelector('[name="desc_clave_unidad_sat"]').value =
      item.dataset.desc;
    document.querySelector(".unidadSatInput").value = item.dataset.clave;

    bootstrap.Modal.getInstance(document.getElementById("modalUNIDSAT")).hide();
  });

// ================== FRACCION ARANCELARIA =====================

document
  .querySelector(".fraccionArancelariaSearch")
  .addEventListener("click", () => {
    new bootstrap.Modal(document.getElementById("modalFRACCIONSAT")).show();
    document.querySelector("#satFraccionSearchInput").focus();
  });

document
  .getElementById("modalFRACCIONSAT")
  .addEventListener("shown.bs.modal", () => {
    document.querySelector("#satFraccionSearchInput").value = "";
    document.querySelector("#satFraccionResultados").innerHTML = "";
  });

document
  .querySelector("#satFraccionSearchInput")
  .addEventListener("input", function () {
    let term = this.value.trim();
    if (term.length < 2) return;

    fetch(
      base_url +
        "/Inv_inventario/searchFRACCIONSAT?term=" +
        encodeURIComponent(term),
    )
      .then((r) => r.json())
      .then((data) => {
        let html = "";

        data.forEach((i) => {
          html += `
          <div class="satfraccion-item ps-3 py-1"
               data-clave="${i.clave}"
               data-desc="${i.descripcion}">
            ${i.clave} - ${i.descripcion}
          </div>`;
        });

        document.querySelector("#satFraccionResultados").innerHTML = html;
      });
  });

document
  .querySelector("#satFraccionResultados")
  .addEventListener("click", function (e) {
    let item = e.target.closest(".satfraccion-item");
    if (!item) return;

    document.querySelector('[name="clave_fraccion_sat"]').value =
      item.dataset.clave;
    document.querySelector('[name="desc_clave_fraccion_sat"]').value =
      item.dataset.desc;
    document.querySelector(".fraccionInput").value = item.dataset.clave;

    bootstrap.Modal.getInstance(
      document.getElementById("modalFRACCIONSAT"),
    ).hide();
  });

// ================== UNIDAD ADUANA SAT =====================

document.querySelector(".aduanaSearch").addEventListener("click", () => {
  new bootstrap.Modal(document.getElementById("modalADUANASAT")).show();
  document.querySelector("#satAduanaSearchInput").focus();
});

document
  .getElementById("modalADUANASAT")
  .addEventListener("shown.bs.modal", () => {
    document.querySelector("#satAduanaSearchInput").value = "";
    document.querySelector("#satAduanaResultados").innerHTML = "";
  });

document
  .querySelector("#satAduanaSearchInput")
  .addEventListener("input", function () {
    let term = this.value.trim();
    if (term.length < 2) return;

    fetch(
      base_url +
        "/Inv_inventario/searchADUANASAT?term=" +
        encodeURIComponent(term),
    )
      .then((r) => r.json())
      .then((data) => {
        let html = "";

        data.forEach((i) => {
          html += `
          <div class="sataduana-item ps-3 py-2 border-bottom"
               data-clave="${i.clave}"
               data-desc="${i.descripcion}">
            <strong>${i.clave}</strong> - ${i.descripcion}
          </div>`;
        });

        document.querySelector("#satAduanaResultados").innerHTML = html;
      });
  });

document
  .querySelector("#satAduanaResultados")
  .addEventListener("click", function (e) {
    let item = e.target.closest(".sataduana-item");
    if (!item) return;

    document.querySelector('[name="clave_aduana_sat"]').value =
      item.dataset.clave;
    document.querySelector('[name="desc_clave_aduana_sat"]').value =
      item.dataset.desc;
    document.querySelector(".aduanaInput").value = item.dataset.clave;

    bootstrap.Modal.getInstance(
      document.getElementById("modalADUANASAT"),
    ).hide();
  });

// ================== guardar datos fiscales =====================
function guardarFiscal(inventarioid) {
  let form = document.querySelector("#formInventario"); // o el que uses
  let data = new FormData(form);
  data.append("inventarioid", inventarioid);

  fetch(base_url + "/Inv_inventario/setFiscal", {
    method: "POST",
    body: data,
  })
    .then((r) => r.json())
    .then((resp) => {
      if (resp.status) {
        console.log("Fiscal guardado");
        rememberToast("Fiscal guardado correctamente", "success");
      } else {
        rememberToast(resp.msg, "error");
      }
    });
}

document
  .querySelector("#btnGuardarFiscal")
  .addEventListener("click", function () {
    let inventarioid = currentInventarioId;

    if (!inventarioid) {
      Swal.fire("Aviso", "Primero guarda el inventario", "warning");
      return;
    }

    let data = new FormData();

    let grupo =
      document.querySelector(".bloqueFiscalForm:not(.d-none)")?.dataset.grupo ||
      "";

    data.append("inventarioid", inventarioid);
    data.append("grupo", grupo);

    data.append(
      "clave_sat",
      document.querySelector('[name="clave_sat"]').value,
    );
    data.append("desc_sat", document.querySelector('[name="desc_sat"]').value);

    data.append(
      "clave_unidad_sat",
      document.querySelector('[name="clave_unidad_sat"]').value,
    );
    data.append(
      "desc_clave_unidad_sat",
      document.querySelector('[name="desc_clave_unidad_sat"]').value,
    );

    data.append(
      "clave_fraccion_sat",
      document.querySelector('[name="clave_fraccion_sat"]').value,
    );
    data.append(
      "desc_clave_fraccion_sat",
      document.querySelector('[name="desc_clave_fraccion_sat"]').value,
    );

    data.append(
      "clave_aduana_sat",
      document.querySelector('[name="clave_aduana_sat"]').value,
    );
    data.append(
      "desc_clave_aduana_sat",
      document.querySelector('[name="desc_clave_aduana_sat"]').value,
    );

    fetch(base_url + "/Inv_inventario/setFiscal", {
      method: "POST",
      body: data,
    })
      .then((r) => r.json())
      .then((resp) => {
        if (resp.status) {
          Swal.fire("OK", "Datos fiscales guardados", "success");
          refrescarFiscal(inventarioid);
        } else {
          Swal.fire("Error", resp.msg, "error");
        }
      });
  });

function refrescarFiscal(idinventario) {
  fetch(base_url + "/Inv_inventario/getFiscalByInventario/" + idinventario)
    .then((r) => r.json())
    .then((res) => {
      const bloque = document.getElementById("bloqueFiscalTabla");
      const tbody = document.getElementById("tbodyFiscal");

      if (!res.status) {
        bloque.classList.add("d-none");
        mostrarFormularioFiscal();
        return;
      }

      const f = res.data;
      let html = "";

      if (f.clave_sat) {
        html += `
        <tr>
          <td>Clave SAT</td>
          <td>${f.clave_sat}</td>
          <td>${f.desc_sat}</td>
          <td class="text-center">
            <button class="btn btn-sm btn-warning" onclick="editarFiscal('sat')">Editar</button>
          </td>
        </tr>`;
      }

      if (f.clave_unidad_sat) {
        html += `
        <tr>
          <td>Unidad SAT</td>
          <td>${f.clave_unidad_sat}</td>
          <td>${f.desc_unidad_sat}</td>
          <td class="text-center">
            <button class="btn btn-sm btn-warning" onclick="editarFiscal('unidad')">Editar</button>
          </td>
        </tr>`;
      }

      if (f.clave_fraccion_sat) {
        html += `
        <tr>
          <td>Fracción</td>
          <td>${f.clave_fraccion_sat}</td>
          <td>${f.desc_fraccion_sat}</td>
          <td class="text-center">
            <button class="btn btn-sm btn-warning" onclick="editarFiscal('fraccion')">Editar</button>
          </td>
        </tr>`;
      }

      if (f.clave_aduana_sat) {
        html += `
        <tr>
          <td>Aduana</td>
          <td>${f.clave_aduana_sat}</td>
          <td>${f.desc_aduana_sat}</td>
          <td class="text-center">
            <button class="btn btn-sm btn-warning" onclick="editarFiscal('aduana')">Editar</button>
          </td>
        </tr>`;
      }

      tbody.innerHTML = html;
      bloque.classList.remove("d-none");

      ocultarBloquesConValor(f);
    });
}

function ocultarBloquesConValor(f) {
  document.querySelectorAll(".bloqueFiscalForm").forEach((b) => {
    const grupo = b.dataset.grupo;
    let ocultar = false;

    if (grupo === "sat" && f.clave_sat) ocultar = true;
    if (grupo === "unidad" && f.clave_unidad_sat) ocultar = true;
    if (grupo === "fraccion" && f.clave_fraccion_sat) ocultar = true;
    if (grupo === "aduana" && f.clave_aduana_sat) ocultar = true;

    if (ocultar) {
      b.classList.add("d-none");
    } else {
      b.classList.remove("d-none");
    }
  });
}

function ocultarFormularioFiscal() {
  document.querySelectorAll(".bloqueFiscalForm").forEach((b) => {
    b.classList.add("d-none");
  });
}

function mostrarFormularioFiscal() {
  document.querySelectorAll(".bloqueFiscalForm").forEach((b) => {
    b.classList.remove("d-none");
  });
}

function editarFiscal(grupo) {
  document.querySelectorAll(".bloqueFiscalForm").forEach((b) => {
    if (b.dataset.grupo === grupo) {
      b.classList.remove("d-none");
    } else {
      b.classList.add("d-none");
    }
  });

  document.getElementById("bloqueFiscalTabla").classList.add("d-none");
}

document.addEventListener("shown.bs.tab", function (e) {
  if (e.target.getAttribute("href") === "#tabFiscal") {
    if (currentInventarioId) {
      refrescarFiscal(currentInventarioId);
    }
  }
});

//-----------------------------------------------------------------------------------------------------------------
//------------------------------------------------------IMPUESTOS-------------------------------------------------
//-----------------------------------------------------------------------------------------------------------------
document
  .getElementById("formImpuestoInventario")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    const idinv = document.getElementById("imp_inventarioid").value;
    guardarImpuesto(idinv);
  });

function cargarTabImpuestos(idinventario) {
  // setear hidden
  const hid = document.getElementById("imp_inventarioid");
  if (hid) hid.value = idinventario;

  fetch(base_url + "/Inv_inventario/getSelectImpuestosCfg")
    .then((r) => r.text())
    .then((html) => {
      document.getElementById("cfg_impuesto").innerHTML = html;
      refrescarTablaImpuestos(idinventario);
    });
}

function guardarImpuesto(idinventario) {
  console.log("ID inventario:", idinventario);
  const impuesto = document.getElementById("cfg_impuesto").value;

  if (!impuesto) {
    Swal.fire("Aviso", "Selecciona un impuesto", "warning");
    return;
  }

  const fd = new FormData();
  fd.append("inventarioid", idinventario);
  fd.append("idimpuesto", impuesto);

  fetch(base_url + "/Inv_inventario/setImpuesto", {
    method: "POST",
    body: fd,
  })
    .then((r) => r.json())
    .then((res) => {
      if (res.status) {
        Swal.fire("OK", res.msg, "success");

        document.getElementById("cfg_impuesto").value = "";

        refrescarTablaImpuestos(idinventario);
      } else {
        Swal.fire("Error", res.msg, "error");
      }
    });
}
function refrescarTablaImpuestos(idinventario) {
  fetch(base_url + "/Inv_inventario/getImpuestosAsignados/" + idinventario)
    .then((r) => r.json())
    .then((res) => {
      console.log("Respuesta impuestos:", res);

      const tbody = document.getElementById("tbodyImpuestosCfg");
      if (!tbody) {
        console.log("No existe tbodyImpuestosCfg");
        return;
      }

      tbody.innerHTML = "";

      if (!res.data || res.data.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="3" class="text-center text-muted">
              Sin impuestos asignados
            </td>
          </tr>`;
        return;
      }

      res.data.forEach((i) => {
        tbody.innerHTML += `
          <tr>
            <td>${i.descripcion}</td>
            <td>${i.estado == 2 ? "Activo" : "Inactivo"}</td>
          </tr>
        `;
      });
    });
}

//-----------------------------------------------------------------------------------------------------------------
//------------------------------------------------------ubicaciones-------------------------------------------------
//-----------------------------------------------------------------------------------------------------------------
//----------------------------- CARGAR TAB
document
  .querySelector('a[href="#tabUbicaciones"]')
  .addEventListener("shown.bs.tab", function () {
    if (!inventarioActual) {
      console.warn("No hay inventario seleccionado");
      return;
    }

    cargarTabUbicaciones(inventarioActual);
  });

function cargarTabUbicaciones(idinventario) {
  // 🔹 GUARDAR ID
  document.getElementById("inv_id").value = idinventario;

  // 🔹 CARGAR SELECT
  cargarSelectUbicaciones();

  // 🔹 CARGAR TABLA
  refrescarTablaUbicaciones(idinventario);
}

function cargarSelectUbicaciones() {
  fetch(base_url + "/Inv_inventario/getSelectUbicaciones")
    .then((res) => res.text())
    .then((html) => {
      document.getElementById("ubicacion_id").innerHTML = html;
    })
    .catch((err) => console.error("Error cargando ubicaciones:", err));
}

//----------------------------- REFRESCAR TABLA
function refrescarTablaUbicaciones(idinventario) {
  fetch(base_url + "/Inv_inventario/getUbicacionesAsignadas/" + idinventario)
    .then((res) => res.json())
    .then((data) => {
      const tbody = document.getElementById("tbodyUbicaciones");
      tbody.innerHTML = "";

      data.data.forEach((u) => {
        tbody.innerHTML += `
          <tr>
            <td>${u.cantidad}</td>
            <td>${u.ubicacion}</td>
            <td>${u.fecha_creacion}</td>
          </tr>
        `;
      });
    });
}

//----------------------------- GUARDAR
function guardarUbicacion(e) {
  e.preventDefault();

  const inventarioid = document.getElementById("inv_id").value;
  const ubicacion = document.getElementById("ubicacion_id").value;
  const cantidad = document.getElementById("cantidad").value;

  if (!inventarioid) {
    Swal.fire("Error", "No hay inventario seleccionado", "error");
    return;
  }

  if (!ubicacion) {
    Swal.fire("Aviso", "Selecciona una ubicación", "warning");
    return;
  }

  const fd = new FormData();
  fd.append("inventarioid", inventarioid);
  fd.append("ubicacionid", ubicacion);
  fd.append("cantidad", cantidad);

  fetch(base_url + "/Inv_inventario/setUbicacion", {
    method: "POST",
    body: fd,
  })
    .then((res) => res.json())
    .then((res) => {
      if (res.status) {
        Swal.fire("OK", res.msg, "success");
        document.getElementById("formUbicacionInventario").reset();
        refrescarTablaUbicaciones(inventarioid);
      } else {
        Swal.fire("Error", res.msg, "error");
      }
    });
}

//-----------------------------------------------------------------------------------------------------------------
//------------------------------------------------------PROVEEDORES-------------------------------------------------
//-----------------------------------------------------------------------------------------------------------------
document
  .getElementById("formProveedores")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    const idinv = document.getElementById("prov_inventarioid").value;
    guardarProveedor(idinv);
  });

function cargarTabProveedores(idinventario) {
  // setear hidden
  const hid = document.getElementById("prov_inventarioid");
  if (hid) hid.value = idinventario;

  fetch(base_url + "/Inv_inventario/getSelectProveedoresCfg")
    .then((r) => r.text())
    .then((html) => {
      document.getElementById("cfg_proveedor").innerHTML = html;
      refrescarTablaProveedores(idinventario);
    });
}

function guardarProveedor(idinventario) {
  console.log("ID inventario:", idinventario);
  const proveedor = document.getElementById("cfg_proveedor").value;

  if (!proveedor) {
    Swal.fire("Aviso", "Selecciona un proveedor", "warning");
    return;
  }

  const fd = new FormData();
  fd.append("inventarioid", idinventario);
  fd.append("id_proveedor", proveedor);

  fetch(base_url + "/Inv_inventario/setProveedor", {
    method: "POST",
    body: fd,
  })
    .then((r) => r.json())
    .then((res) => {
      if (res.status) {
        Swal.fire("OK", res.msg, "success");

        document.getElementById("cfg_proveedor").value = "";

        refrescarTablaProveedores(idinventario);
      } else {
        Swal.fire("Error", res.msg, "error");
      }
    });
}
function refrescarTablaProveedores(idinventario) {
  fetch(base_url + "/Inv_inventario/getProveedoresAsignados/" + idinventario)
    .then((r) => r.json())
    .then((res) => {
      console.log("Respuesta impuestos:", res);

      const tbody = document.getElementById("tbodyProveedoresCfg");
      if (!tbody) {
        console.log("No existe tbodyProveedoresCfg");
        return;
      }

      tbody.innerHTML = "";

      if (!res.data || res.data.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="3" class="text-center text-muted">
              Sin proveedores asignados
            </td>
          </tr>`;
        return;
      }

      res.data.forEach((i) => {
        tbody.innerHTML += `
          <tr>
            <td>${i.nombre_comercial}</td>
            <td>${i.estado == 2 ? "Activo" : "Inactivo"}</td>
          </tr>
        `;
      });
    });
}

//-----------------------------------------------------------------------------------------------------------------
//------------------------------------------------------CANTIDADES-------------------------------------------------
//-----------------------------------------------------------------------------------------------------------------

function cargarCantidades(inventarioid) {
  $("#inventarioid_cantidades").val(inventarioid);

  // limpiar visual
  $("#tbodyAlmacenes").html(`
    <tr>
      <td colspan="5" class="text-center">Cargando...</td>
    </tr>
  `);
  $("#contenedorAlmacenes").show();

  // ===============================
  // RESUMEN GENERAL (wms_inventario)
  // ===============================
  $.ajax({
    url: base_url + "/Inv_inventario/getCantidadesProducto",
    type: "POST",
    data: { inventarioid: inventarioid },
    dataType: "json",
    success: function (res) {
      $("#existencia_total").val(res.existencia_total ?? 0);
      $("#stock_minimo").val(res.stock_minimo ?? 0);
      $("#stock_maximo").val(res.stock_maximo ?? 0);
      $("#apartado").val(res.apartado ?? 0);
    },
    error: function () {
      $("#existencia_total").val(0);
      $("#stock_minimo").val(0);
      $("#stock_maximo").val(0);
      $("#apartado").val(0);
    },
  });

  // ===============================
  // DETALLE POR ALMACÉN (wms_multialmacen)
  // ===============================
  $.ajax({
    url: base_url + "/Inv_inventario/getAlmacenesProducto",
    type: "POST",
    data: { inventarioid: inventarioid },
    dataType: "json",
    success: function (res) {
      let html = "";

      if (res.length > 0) {
        res.forEach((row) => {
          html += `
            <tr>
              <td>${row.almacen}</td>
              <td>${row.existencia ?? 0}</td>
              <td>${row.stock_minimo ?? 0}</td>
              <td>${row.stock_maximo ?? 0}</td>
              <td>${row.apartado ?? 0}</td>
            </tr>
          `;
        });
      } else {
        html = `
          <tr>
            <td colspan="5" class="text-center">Sin almacenes asignados</td>
          </tr>
        `;
      }

      $("#tbodyAlmacenes").html(html);
      $("#contenedorAlmacenes").show();
    },
    error: function () {
      $("#tbodyAlmacenes").html(`
        <tr>
          <td colspan="5" class="text-center text-danger">
            Error al consultar almacenes
          </td>
        </tr>
      `);
      $("#contenedorAlmacenes").show();
    },
  });
}

document
  .querySelector('a[href="#tabCantidades"]')
  .addEventListener("shown.bs.tab", function () {
    if (!currentInventarioId) return;
    cargarCantidades(currentInventarioId);
  });
