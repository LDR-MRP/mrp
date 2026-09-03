'use strict';

/* ============================================================
 * MI CUENT - PEDIDOS
 * ============================================================ */

document.addEventListener('DOMContentLoaded',() => {

    const ITEMS_PER_PAGE = 10;

    let currentPage = 1;

    const searchInput =document.getElementById('orderSearch');
    const dateFrom =document.getElementById('dateFrom');
    const dateTo =document.getElementById('dateTo');
    const statusFilter =document.getElementById('statusFilter');
    const clearButton =document.getElementById('btnClearOrderFilters');
    const statusTabs =Array.from(document.querySelectorAll('.status-tab'));
    const rows =Array.from(document.querySelectorAll('#ordersTableBody .order-row'));
    const pagination =document.getElementById('ordersPagination');
    const paginationInfo =document.getElementById('ordersPaginationInfo');
    const noResults =document.getElementById('ordersNoResults');
    const ordersFooter =document.getElementById('ordersFooter');

    let filteredRows = [...rows];

    /* ========================================================
     * FILTROS
     * ======================================================== */

    function aplicarFiltros(resetPage = true) {

      if (resetPage) {
        currentPage = 1;
      }
      const search =String(searchInput?.value|| '').trim().toLowerCase();
      const desde =dateFrom?.value|| '';
      const hasta =dateTo?.value|| '';
      const estatus =String(statusFilter?.value|| '').trim().toUpperCase();

      filteredRows = rows.filter(
          row => {
            const text =row.textContent.toLowerCase();
            const rowStatus = String(row.dataset.status || '').toUpperCase();
            const rowDate =row.dataset.date|| '';
            const coincideBusqueda =!search || text.includes(search);
            const coincideEstatus =! estatus || rowStatus  === estatus;
            const coincideDesde =! desde || (rowDate && rowDate >= desde);
            const coincideHasta =
              !hasta
              || (
                rowDate
                && rowDate <= hasta
              );

            return (
              coincideBusqueda
              && coincideEstatus
              && coincideDesde
              && coincideHasta
            );
          }
        );

      renderPagina();
    }

    /* ========================================================
     * PAGINACIÓN
     * ======================================================== */

    function renderPagina() {

      /*
       * Ocultar todas las filas.
       */
      rows.forEach( row => {row.hidden = true;});
      const total = filteredRows.length;
      const totalPages =
        Math.max(
          1,
          Math.ceil(
            total
            / ITEMS_PER_PAGE
          )
        );


      if (currentPage > totalPages) {
        currentPage = totalPages;
      }

      const start =(currentPage - 1) * ITEMS_PER_PAGE;
      const end = start + ITEMS_PER_PAGE;

      const pageRows =
        filteredRows.slice(
          start,
          end
        );

      pageRows.forEach( row => { row.hidden = false;});

      /*
       * Mensaje sin resultados.
       */
      if (noResults) {
        noResults.hidden = total > 0;
      }

      /*
       * Footer.
       */
      if (ordersFooter) {
        ordersFooter.hidden = total === 0;
      }

      /*
       * Información.
       */
      if (paginationInfo && total > 0) {

        paginationInfo.innerHTML = `

          Mostrando
          <strong>
            ${start + 1}
          </strong>

          a
          <strong>
            ${Math.min(
              end,
              total
            )}
          </strong>

          de
          <strong>
            ${total}
          </strong>

          pedidos
        `;
      }

      renderPaginacion(totalPages);
    }

    function renderPaginacion(totalPages) {

      if (!pagination) {
        return;
      }

      if (totalPages <= 1) {

        pagination.innerHTML ='';

        return;
      }

      let html = `

        <button
          type="button"
          class="pagination-button"
          data-page="${
            currentPage - 1
          }"
          ${
            currentPage === 1
              ? 'disabled'
              : ''
          }
        >

          <span>
            ←
          </span>

        </button>
      `;

      const paginas = obtenerPaginasVisibles(totalPages);

      paginas.forEach(
        pagina => {

          if (pagina === '...') {

            html += `

              <span class="pagination-dots">
                ...
              </span>
            `;

            return;
          }

          html += `

            <button
              type="button"
              class="
                pagination-button
                ${pagina=== currentPage? 'active': ''}
              "
              data-page="${pagina}"
            >

              ${pagina}

            </button>
          `;
        }
      );

      html += `

        <button
          type="button"
          class="pagination-button"
          data-page="${
            currentPage + 1
          }"
          ${
            currentPage
            === totalPages
              ? 'disabled'
              : ''
          }
        >

          <span>
            →
          </span>

        </button>
      `;

      pagination.innerHTML =html;
      pagination.querySelectorAll('[data-page]').forEach(
          button => {

            button.addEventListener(
              'click',
              () => {

                if ( button.disabled) {
                  return;
                }

                const page = Number(button.dataset.page);

                if (
                  page < 1
                  || page > totalPages
                ) {
                  return;
                }

                currentPage =page;

                renderPagina();

                document
                  .querySelector(
                    '.orders-panel'
                  )
                  ?.scrollIntoView({
                    behavior:
                      'smooth',

                    block:
                      'start'
                  });
              }
            );
          }
        );
    } 

    function obtenerPaginasVisibles(totalPages) {

      if ( totalPages <= 7) {

        return Array.from(
          {
            length:
              totalPages
          },
          (_, index) =>
            index + 1
        );
      }

      const pages = [1];

      if (currentPage > 4 ) {

        pages.push(
          '...'
        );
      }

      const start = Math.max(2,currentPage - 1);
      const end =Math.min(totalPages - 1,currentPage + 1);

      for ( let page = start; page <= end; page++) {

        pages.push(
          page
        );
      }

      if (currentPage < totalPages - 3) {

        pages.push(
          '...'
        );
      }

      pages.push( totalPages);


      return pages;
    }

    /* ========================================================
     * TABS
     * ======================================================== */

    function actualizarTabActivo(status) {

      const valor =String(status || '').toUpperCase();

      statusTabs.forEach(
        tab => {

          tab.classList.toggle(
            'active',

            String(
              tab.dataset.status
              || ''
            ).toUpperCase()
            === valor
          );
        }
      );
    }

    statusTabs.forEach(
      tab => {

        tab.addEventListener(
          'click',
          () => {

            const status = tab.dataset.status || '';


            if (statusFilter) {

              statusFilter.value =status;
            }

            actualizarTabActivo(status);
            aplicarFiltros();
          }
        );
      }
    );

    /* ========================================================
     * BUSCADOR
     * ======================================================== */

    searchInput
      ?.addEventListener(
        'input',
        () => {

          aplicarFiltros();
        }
      );


    /* ========================================================
     * FECHAS
     * ======================================================== */

    dateFrom
      ?.addEventListener(
        'change',
        aplicarFiltros
      );

    dateTo
      ?.addEventListener(
        'change',
        aplicarFiltros
      );


    /* ========================================================
     * SELECT ESTATUS
     * ======================================================== */

    statusFilter
      ?.addEventListener(
        'change',
        () => {

          actualizarTabActivo(
            statusFilter.value
          );

          aplicarFiltros();
        }
      );


    /* ========================================================
     * LIMPIAR
     * ======================================================== */

    clearButton
      ?.addEventListener(
        'click',
        () => {

          if (searchInput) {
            searchInput.value ='';
          }

          if (dateFrom) {
            dateFrom.value = '';
          }

          if (dateTo) {
            dateTo.value ='';
          }

          if (statusFilter) {
            statusFilter.value ='';
          }

          currentPage = 1;

          actualizarTabActivo('');
          aplicarFiltros();
        }
      );

    /* ========================================================
     * INICIALIZACIÓN
     * ======================================================== */

    aplicarFiltros(false);

  }
);





document.addEventListener(
    "click",
    function (event) {

        /*
         * ======================================================
         * IMPRIMIR PEDIDO
         * ======================================================
         */

        const btnImprimir =
            event.target.closest(
                '[data-action="print-order"]'
            );


        if (btnImprimir) {

            imprimirPedidoDesdeMiCuenta(
                btnImprimir
            );

            return;
        }


        /*
         * ======================================================
         * CANCELAR PEDIDO
         * ======================================================
         */

        const btnCancelar =
            event.target.closest(
                '[data-action="cancel-order"]'
            );


        if (btnCancelar) {

            cancelarPedidoDesdeMiCuenta(
                btnCancelar
            );

            return;
        }

    }
);

async function imprimirPedidoDesdeMiCuenta(
    btn
) {

    const clave =
        btn.dataset.clave
        || "";


    if (!clave) {

        mostrarAlertaMiCuenta(
            "No fue posible identificar el pedido.",
            "error"
        );

        return;
    }


    const htmlOriginal =
        btn.innerHTML;


    btn.disabled =
        true;


    btn.innerHTML = `
        <i class="ri-loader-4-line ri-spin"></i>
        <span>Generando...</span>
    `;

    try {

        /*
         * ======================================================
         * ESTA ES LA FUNCIÓN COMPARTIDA
         * ======================================================
         */
        await imprimirPedidoPdf(clave);

    } catch (error) {

        console.error(
            "Error generando PDF:",
            error
        );
        mostrarAlertaMiCuenta(
            error.message
            || "No fue posible generar el PDF.",
            "error"
        );


    } finally {

        btn.disabled =false;
        btn.innerHTML =htmlOriginal;

    }

}


function mostrarAlertaMiCuenta(mensaje,tipo = "info") {

    if (typeof Swal!== "undefined") {

        Swal.fire({
            icon: tipo,
            title:tipo === "error"
                    ? "No fue posible continuar"
                    : "Información",
            text: mensaje,
            confirmButtonText: "Aceptar"
        });

        return;
    }
    alert(ensaje);

}


    function escapeHtmlMiCuenta(value) {

    const div =document.createElement("div");
    div.textContent =String(value ?? "");
    return div.innerHTML;

}


async function cancelarPedidoDesdeMiCuenta(btn) {
  console.log('dando click');
    const clave =String(btn.dataset.clave || "").trim();
    const folio =String(btn.dataset.folio|| "").trim();
    if (!clave) {
        mostrarAlertaMiCuenta(
            "No fue posible identificar el pedido.",
            "error"
        );

        return;
    }

    /*
     * ==========================================================
     * CONFIRMACIÓN
     * ==========================================================
     */
    const confirmacion =
        await Swal.fire({
            icon:"warning",
            title:"¿Cancelar pedido?",
            html: `
                <div style="text-align:left;">
                    <p>
                        Se cancelará el pedido
                        <strong>${escapeHtmlMiCuenta(
                            folio || clave
                        )}</strong>.
                    </p>

                    <p style="margin-bottom:0;">
                        Esta acción cambiará el estatus a
                        <strong>CANCELADO</strong>.
                    </p>
                </div>
            `,
            showCancelButton:true,
            confirmButtonText:"Sí, cancelar pedido",
            cancelButtonText:"No, regresar",
            reverseButtons:true,
            focusCancel:true

        });

    if (!confirmacion.isConfirmed) {

        return;
    }
    /*
     * ==========================================================
     * BLOQUEAR BOTÓN
     * ==========================================================
     */
    const htmlOriginal =btn.innerHTML;
    btn.disabled =true;
    btn.innerHTML = `
        <i class="ri-loader-4-line ri-spin"></i>
        <span>Cancelando...</span>
    `;

    try {
        /*
         * ======================================================
         * PETICIÓN
         * ======================================================
         */
        const response =
            await fetch(
                `${base_url}/orders/cancelarPedido`,
                {
                    method:"POST",
                    headers: {"Content-Type":"application/json","Accept":"application/json"
                    },
                    body:JSON.stringify({clave:clave})
                }
            );
        /*
         * ======================================================
         * VALIDAR RESPUESTA
         * ======================================================
         */
        const contentType =response.headers.get("content-type")|| "";
        if (!contentType.includes("application/json")) {

            const texto =await response.text();
            console.error("Respuesta no JSON:",texto);
            throw new Error("El servidor devolvió una respuesta no válida.");

        }
        const result =await response.json();

        if (!response.ok || !result.status) {
            throw new Error(
                result.message
                || "No fue posible cancelar el pedido."
            );
        }

        /*
         * ======================================================
         * ÉXITO
         * ======================================================
         */
        await Swal.fire({
            icon:"success",
            title:"Pedido cancelado",
            text:result.message || "El pedido fue cancelado correctamente.",
            confirmButtonText:"Aceptar"

        });

        window.location.reload();

    } catch (error) {

        console.error(
            "Error cancelando pedido:",
            error
        );
        mostrarAlertaMiCuenta(error.message || "No fue posible cancelar el pedido.","error");
        btn.disabled =false;
        btn.innerHTML =htmlOriginal;

    }




}