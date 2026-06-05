let tableListas, tableProductosLista;

document.addEventListener("DOMContentLoaded", () => {
  loadListas();
  refreshListaSelector();
  refreshListasMover();
  initEvents();
  initSelects();
  toggleBusquedaProductos();
});

function refreshListaSelector() {
  fetch(base_url + "/Inv_productossustitutos/getListas")
    .then((r) => r.json())
    .then((r) => {
      let html = '<option value="">Selecciona una lista</option>';
      (r.data || []).forEach((x) => {
        html += `<option value="${x.id_clave_lista}">${x.nombre_lista}</option>`;
      });
      document.getElementById("listaSelector").innerHTML = html;
    });
}

function loadListas() {
  tableListas = $("#tableListas").DataTable({
    ajax: {
      url: base_url + "/Inv_productossustitutos/getListas",
      dataSrc: (json) => json.data || [],
    },
    columns: [
      { data: "id_clave_lista" },
      { data: "nombre_lista" },
      { data: "activo", render: (d) => (d == 1 ? "Activo" : "Inactivo") },
      {
        data: null,
        render: (row) => `
          <button class="btn btn-sm btn-warning btnEditLista" data-id="${row.id_clave_lista}">
            Editar
          </button>
        `,
      },
    ],
    destroy: true,
  });
}

function initSelects() {
  initPredictivos();
  toggleBusquedaProductos();
}

function initPredictivos() {
  document.querySelectorAll(".productoPredictivo").forEach((input) => {
    if (input.dataset.ready) return;
    input.dataset.ready = "1";

    $(input).autocomplete({
      minLength: 2,
      source: function (request, response) {
        const tipo = document.getElementById("tipoProducto").value;
        const lista = document.getElementById("listaSelector").value;

        // bloquear búsqueda hasta tener contexto completo
        if (!lista) {
          Swal.fire("Error", "Primero selecciona una lista", "warning");
          return response([]);
        }

        if (!tipo) {
          Swal.fire(
            "Error",
            "Primero selecciona un tipo de producto",
            "warning",
          );
          return response([]);
        }

        fetch(
          base_url +
            "/Inv_productossustitutos/getInventario?search=" +
            encodeURIComponent(request.term) +
            "&tipo=" +
            encodeURIComponent(tipo),
        )
          .then((r) => r.json())
          .then((r) => {
            response(
              (r.data || []).map((x) => ({
                label: x.text,
                value: x.text,
                id: x.id,
                clave: x.cve_articulo,
                descripcion: x.descripcion,
                tipo: x.tipo_text,
              })),
            );
          });
      },
      select: function (event, ui) {
        this.value = ui.item.clave + " - " + ui.item.descripcion;
        this.closest(".producto-item").querySelector(".productoId").value =
          ui.item.id;
        return false;
      },
    });

    // render visual bonito
    $(input).autocomplete("instance")._renderItem = function (ul, item) {
      return $("<li>")
        .append(
          `
      <div class="px-3 py-2 border-bottom autocomplete-item">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="fw-bold text-primary">${item.clave}</div>
            <div class="small text-muted">${item.descripcion}</div>
          </div>
          <span class="badge bg-info text-dark ms-2">${item.tipo}</span>
        </div>
      </div>
    `,
        )
        .appendTo(ul);
    };
  });
}

function toggleBusquedaProductos() {
  const lista = document.getElementById("listaSelector").value;
  const tipo = document.getElementById("tipoProducto").value;

  document.querySelectorAll(".productoPredictivo").forEach((input) => {
    input.disabled = !(lista && tipo);

    if (!lista || !tipo) {
      input.placeholder = "Primero selecciona lista y tipo...";
      input.value = "";
      input.closest(".producto-item").querySelector(".productoId").value = "";
    } else {
      input.placeholder = "Buscar producto...";
    }
  });
}

function initEvents() {
  document.getElementById("btnNuevaLista").addEventListener("click", () => {
    Swal.fire({
      title: "Nueva Lista",
      input: "text",
      inputLabel: "Nombre de lista",
      showCancelButton: true,
    }).then((r) => {
      if (!r.isConfirmed) return;

      if (!r.value || !r.value.trim()) {
        Swal.fire("Error", "Ingresa un nombre de lista", "error");
        return;
      }

      fetch(base_url + "/Inv_productossustitutos/setLista", {
        method: "POST",
        body: new URLSearchParams({ nombre_lista: r.value.trim() }),
      })
        .then((x) => x.json())
        .then((x) => {
          console.log("setLista response:", x);

          const ok = x.status ?? x.success ?? x.type === "success";

          Swal.fire(
            ok ? "Correcto" : "Error",
            x.msg || x.message || "Sin respuesta del servidor",
            ok ? "success" : "error",
          );

          if (ok) {
            tableListas.ajax.reload(null, false);
            refreshListaSelector();
            refreshListasMover();
          }
        });
    });
  });

  document
    .getElementById("listaSelector")
    .addEventListener("change", function () {
      let id = this.value;
      if (!id) return;

      tableProductosLista = $("#tableProductosLista").DataTable({
        ajax: {
          url: base_url + "/Inv_productossustitutos/getProductosLista/" + id,
          dataSrc: (json) => json.data || [],
        },
        columns: [
          { data: "cve_articulo" },
          { data: "descripcion" },
          { data: "tipo_text" },
          { data: "fecha_creacion" },
          {
            data: null,
            render: function (row) {
              return `
        <button class="btn btn-sm btn-danger btnDeleteProducto" data-id="${row.id_detalle}">
          Eliminar
        </button>
      `;
            },
          },
        ],
        destroy: true,
      });
      toggleBusquedaProductos();
    });

  document
    .getElementById("btnGuardarProductos")
    .addEventListener("click", () => {
      let idLista = document.getElementById("listaSelector").value;
      let productos = [];

      if (!idLista) {
        Swal.fire("Error", "Selecciona una lista", "error");
        return;
      }

      document.querySelectorAll(".productoId").forEach((input) => {
        if (input.value) productos.push(input.value);
      });

      if (!productos.length) {
        Swal.fire("Error", "Agrega al menos un producto", "error");
        return;
      }

      fetch(base_url + "/Inv_productossustitutos/setProductoLista", {
        method: "POST",
        body: new URLSearchParams({
          id_clave_lista: idLista,
          productos: JSON.stringify(productos),
        }),
      })
        .then((r) => r.json())
        .then((r) => {
          const ok = r.status ?? r.success ?? false;
          const msg = r.msg || r.message || "Sin respuesta del servidor";

          Swal.fire(ok ? "Correcto" : "Error", msg, ok ? "success" : "error");

          if (ok) {
            if (tableProductosLista) {
              tableProductosLista.ajax.reload(null, false);
            }

            resetProductosContainer();
            toggleBusquedaProductos();
          }
        });
    });

  document.addEventListener("click", function (e) {
    if (!e.target.classList.contains("btnEditLista")) return;

    const id = e.target.dataset.id;

    fetch(base_url + "/Inv_productossustitutos/getLista/" + id)
      .then((r) => r.json())
      .then((r) => {
        const item = r.data;

        Swal.fire({
          title: "Editar Lista",
          input: "text",
          inputValue: item.nombre_lista,
          inputLabel: "Nombre de lista",
          showCancelButton: true,
        }).then((resp) => {
          if (!resp.isConfirmed) return;

          if (!resp.value || !resp.value.trim()) {
            Swal.fire("Error", "Ingresa un nombre de lista", "error");
            return;
          }

          fetch(base_url + "/Inv_productossustitutos/updateLista", {
            method: "POST",
            body: new URLSearchParams({
              id_clave_lista: id,
              nombre_lista: resp.value.trim(),
            }),
          })
            .then((x) => x.json())
            .then((x) => {
              const ok = x.status ?? x.success ?? x.type === "success";

              Swal.fire(
                ok ? "Correcto" : "Error",
                x.msg || x.message,
                ok ? "success" : "error",
              );

              if (ok) {
                tableListas.ajax.reload(null, false);
                refreshListaSelector();
                refreshListasMover();
              }
            });
        });
      });
  });

  document
    .getElementById("btnAddInputProducto")
    .addEventListener("click", () => {
      const html = `
    <div class="row mb-2 producto-item">
      <div class="col-md-11">
        <input type="text" class="form-control productoPredictivo" placeholder="Buscar producto...">
        <input type="hidden" class="productoId">
      </div>
      <div class="col-md-1">
        <button type="button" class="btn btn-danger btnRemoveProducto w-100">X</button>
      </div>
    </div>
  `;

      document
        .getElementById("productosContainer")
        .insertAdjacentHTML("beforeend", html);
      initPredictivos();
    });

  document.addEventListener("click", function (e) {
    if (e.target.classList.contains("btnRemoveProducto")) {
      e.target.closest(".producto-item").remove();
    }
  });

  document.getElementById("tipoProducto").addEventListener("change", () => {
    document.querySelectorAll(".productoPredictivo").forEach((input) => {
      input.value = "";
    });

    document.querySelectorAll(".productoId").forEach((input) => {
      input.value = "";
    });

    toggleBusquedaProductos();
  });
  document
    .getElementById("btnLimpiarProductos")
    .addEventListener("click", () => {
      resetProductosContainer();
      toggleBusquedaProductos();
    });

  document.addEventListener("click", function (e) {
    if (!e.target.classList.contains("btnDeleteProducto")) return;

    const id = e.target.dataset.id;

    Swal.fire({
      title: "Eliminar producto",
      text: "¿Deseas eliminar este producto de la lista?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, eliminar",
      cancelButtonText: "Cancelar",
    }).then((result) => {
      if (!result.isConfirmed) return;

      fetch(base_url + "/Inv_productossustitutos/deleteProductoLista", {
        method: "POST",
        body: new URLSearchParams({ id_detalle: id }),
      })
        .then((r) => r.json())
        .then((r) => {
          const ok = r.status ?? r.success ?? false;
          const msg = r.msg || r.message || "Sin respuesta del servidor";

          Swal.fire(ok ? "Correcto" : "Error", msg, ok ? "success" : "error");

          if (ok && tableProductosLista) {
            tableProductosLista.ajax.reload(null, false);
          }
        });
    });
  });

  //mover listas
  document
    .getElementById("listaOrigen")
    .addEventListener("change", function () {
      loadProductosMover("productosOrigen", this.value);
    });

  document
    .getElementById("listaDestino")
    .addEventListener("change", function () {
      loadProductosMover("productosDestino", this.value);
    });

  document.getElementById("btnMoverDerecha").addEventListener("click", () => {
    moverProductos("productosOrigen", "productosDestino");
  });

  document.getElementById("btnMoverIzquierda").addEventListener("click", () => {
    moverProductos("productosDestino", "productosOrigen");
  });

  //resetear selects de movimientos entre listas
  $('a[data-bs-toggle="tab"]').on("shown.bs.tab", function (e) {
    const target = $(e.target).attr("href");

    // pestaña productos por lista
    if (target === "#tabProductos") {
      document.getElementById("listaSelector").value = "";
      document.getElementById("tipoProducto").value = "";
      resetProductosContainer();
      toggleBusquedaProductos();

      if (tableProductosLista) {
        tableProductosLista.clear().draw();
      }
    }

    // pestaña mover productos
    if (target === "#tabMatriz") {
      document.getElementById("listaOrigen").value = "";
      document.getElementById("listaDestino").value = "";
      document.getElementById("productosOrigen").innerHTML = "";
      document.getElementById("productosDestino").innerHTML = "";
    }
  });
}

function resetProductosContainer() {
  const container = document.getElementById("productosContainer");

  container.innerHTML = `
    <div class="row mb-2 producto-item">
      <div class="col-md-12">
        <label class="form-label">Producto</label>
        <input type="text" class="form-control productoPredictivo" placeholder="Buscar producto...">
        <input type="hidden" class="productoId">
      </div>
    </div>
  `;

  initPredictivos();
}

//movimientos entre listas pestaña 3

function refreshListasMover() {
  fetch(base_url + "/Inv_productossustitutos/getListas")
    .then((r) => r.json())
    .then((r) => {
      let html = '<option value="">Selecciona una lista</option>';

      (r.data || []).forEach((x) => {
        html += `<option value="${x.id_clave_lista}">${x.nombre_lista}</option>`;
      });

      document.getElementById("listaOrigen").innerHTML = html;
      document.getElementById("listaDestino").innerHTML = html;
    });
}

function loadProductosMover(selectId, listaId) {
  if (!listaId) {
    document.getElementById(selectId).innerHTML = "";
    return;
  }

  fetch(base_url + "/Inv_productossustitutos/getProductosLista/" + listaId)
    .then((r) => r.json())
    .then((r) => {
      let html = "";

      (r.data || []).forEach((x) => {
        html += `<option value="${x.idinventario}">${x.cve_articulo} - ${x.descripcion}</option>`;
      });

      document.getElementById(selectId).innerHTML = html;
    });
}

function moverProductos(origenSelect, destinoSelect) {
  const origenLista =
    origenSelect === "productosOrigen"
      ? document.getElementById("listaOrigen").value
      : document.getElementById("listaDestino").value;

  const destinoLista =
    destinoSelect === "productosDestino"
      ? document.getElementById("listaDestino").value
      : document.getElementById("listaOrigen").value;

  const selected = Array.from(
    document.getElementById(origenSelect).selectedOptions,
  ).map((o) => o.value);

  if (!origenLista || !destinoLista) {
    Swal.fire("Error", "Selecciona lista origen y destino", "warning");
    return;
  }

  if (!selected.length) {
    Swal.fire("Error", "Selecciona al menos un producto", "warning");
    return;
  }

  fetch(base_url + "/Inv_productossustitutos/moverProductosLista", {
    method: "POST",
    body: new URLSearchParams({
      origen: origenLista,
      destino: destinoLista,
      productos: JSON.stringify(selected),
    }),
  })
    .then((r) => r.json())
    .then((r) => {
      const ok = r.status ?? r.success ?? false;
      const msg = r.msg || r.message || "Sin respuesta del servidor";

      Swal.fire(ok ? "Correcto" : "Error", msg, ok ? "success" : "error");

      if (ok) {
        loadProductosMover(
          "productosOrigen",
          document.getElementById("listaOrigen").value,
        );
        loadProductosMover(
          "productosDestino",
          document.getElementById("listaDestino").value,
        );
      }
    });
}
