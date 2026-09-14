'use strict';


document.addEventListener(
  'DOMContentLoaded',
  () => {

    activarModoEdicionPedido();
    cargarNuevasUnidadesPedido();
    configurarPedidoEditable();
    recalcularResumen();
  }
);

function activarModoEdicionPedido() {

    const pedido =window.EDIT_ORDER?.clave || '';
    const folio =window.EDIT_ORDER?.folio || '';
    if (!pedido) {
        return;
    }
    sessionStorage.setItem(
        'orders_edit_context',
        JSON.stringify({
            modo: 'editar',
            pedido: pedido,
            folio: folio
        })
    );
}


/* ============================================================
 * UNIDADES NUEVAS AGREGADAS DESDE CATÁLOGO / DETALLE
 * ============================================================ */

function getPedidoEdicionStorageKey() {

  const clave =String(window.EDIT_ORDER?.clave || '').trim();

  if (!clave) {
    return null;
  }
  return `pedidoEdicion_${clave}`;
}

function obtenerNuevasUnidadesPedido() {

  const storageKey =getPedidoEdicionStorageKey();

  if (!storageKey) {
    return [];
  }

  try {
    const data =
      JSON.parse(
        localStorage.getItem(
          storageKey
        )
        || '[]'
      );

    return Array.isArray(data)
      ? data
      : [];

  } catch (error) {

    console.error(
      'No fue posible leer las unidades nuevas del pedido:',
      error
    );

    return [];
  }
}

function cargarNuevasUnidadesPedido() {

  const container =document.getElementById('editOrderItems');

  if (!container) {
    return;
  }

  const nuevasUnidades =obtenerNuevasUnidadesPedido();

  if (!nuevasUnidades.length) {
    return;
  }

  /*
   * ========================================================
   * RECORRER LAS UNIDADES TEMPORALES
   * ========================================================
   */

  nuevasUnidades.forEach(
    item => {

      const idunidad =Number(item.idunidad || item.id || 0);

      if (idunidad <= 0) {
        return;
      }

      /*
       * Verificaos si esa unidad YA existe
       * dentro del pedido original.
       */
      const cardExistente =container.querySelector(`.edit-order-item[data-unit-id="${idunidad}"]`);


      /*
       * ====================================================
       * SI YA EXISTE EN EL PEDIDO
       * ====================================================
       *
       */
      if (cardExistente) {

        const inputCantidad =cardExistente.querySelector('[data-qty]');

        if (inputCantidad) {

          const cantidadActual =Math.max(1,Number(inputCantidad.value|| 1));
          const cantidadNueva =Math.max(1,Number(item.qty || 1));
          inputCantidad.value =cantidadActual + cantidadNueva;
        }

        return;
      }

      /*
       * ====================================================
       * SI ES UNA UNIDAD NEVA
       * ====================================================
       */

      container.insertAdjacentHTML(
        'beforeend',
        generarHTMLNuevaUnidad(
          item
        )
      );
    }
  );
}

function generarHTMLNuevaUnidad(
  item
) {

  const idunidad =Number(item.idunidad || item.id || 0);

  const precioUnitario =Number(item.precio_unitario || item.precio_estimado || item.precio || 0);
  const cantidad =Math.max(1,Number(item.qty || 1));
  const importeEstimado = precioUnitario * cantidad;
  const imagen =String(item.img || `${base_url}/Assets/images/no-image.png`);

  return `

    <article
      class="
        edit-order-item
        edit-order-item-new
      "
      data-detail-id="0"
      data-unit-id="${idunidad}"
      data-price="${precioUnitario}"
      data-new-item="1"
    >

      <div class="edit-item-image">

        <img
          src="${escapeHtmlEdit(
            imagen
          )}"
          alt="${escapeHtmlEdit(
            item.nombre
            || 'Unidad'
          )}"
          onerror="
            this.onerror=null;
            this.src='${base_url}/Assets/images/no-image.png';
          "
        >

      </div>


      <div class="edit-item-content">

        <div class="edit-item-heading">

          <div>

            <span>

              ${escapeHtmlEdit(item.marca || '')}

            </span>


            <h3>
              ${escapeHtmlEdit(item.nombre || '')}
            </h3>

            <p>
              ${escapeHtmlEdit(item.version || '')}
            </p>

            <span class="edit-new-badge">

              Nueva unidad

            </span>

          </div>


          <button
            type="button"
            class="edit-item-remove"
            data-remove-detail
          >
            Eliminar
          </button>

        </div>

        <div class="edit-item-grid">

          <!-- CANTIDAD -->

          <div>

            <label>
              Cantidad
            </label>

            <div class="edit-quantity-control">

              <button
                type="button"
                data-qty-minus
              >
                −
              </button>

              <input
                type="number"
                min="1"
                step="1"
                value="${cantidad}"
                data-qty
              >

              <button
                type="button"
                data-qty-plus
              >
                +
              </button>

            </div>

          </div>


          <!-- TIPO DE ENTREGA -->

          <div>

            <label>
              Tipo de entrega
            </label>


            <select
              data-delivery-type
            >

              <option value="">
                Selecciona
              </option>

              <option value="SUCURSAL">
                Sucursal
              </option>

              <option value="OTRA_DIRECCION">
                Otra dirección
              </option>

            </select>

          </div>

          <!-- SUCURSAL -->

          <div
            data-sucursal-wrapper
            hidden
          >

            <label>
              Sucursal
            </label>


            <select
              data-sucursal
            >

              <option value="">
                Selecciona una sucursal
              </option>

              ${generarOpcionesSucursalesEdicion()}

            </select>

          </div>


          <!-- OTRA DIRECCIÓN -->

          <div
            data-address-wrapper
            hidden
          >

            <label>
              Dirección
            </label>


            <textarea
              rows="3"
              data-address
              placeholder="Indica la dirección completa"
            ></textarea>

          </div>

        </div>


        <!-- INFORMACIÓN FINANCIERA -->

        <div class="edit-item-financial">


          <div class="edit-item-financial-value">

            <span>
              Precio unitario
            </span>

            <strong>

              ${formatMoneyEdit(precioUnitario)}

            </strong>

          </div>


          <div class="edit-item-financial-value">

            <span>
              Importe estimado
            </span>

            <strong
              data-line-total
            >

              ${formatMoneyEdit(importeEstimado)}

            </strong>

          </div>

        </div>

      </div>

    </article>
  `;
}


function generarOpcionesSucursalesEdicion(selectedId = null) {

  const sucursales =
    Array.isArray(
      window.EDIT_ORDER?.sucursales
    )
      ? window.EDIT_ORDER.sucursales
      : [];


  if (!sucursales.length) {

    return `
      <option value="">
        No existen sucursales disponibles
      </option>
    `;
  }

  return sucursales
    .map(
      sucursal => {

        const id =Number(sucursal.idsucursal || 0);
        const nombre =String(sucursal.nombre_sucursal || sucursal.nombre || '');

        const selected =
          Number(selectedId)
          === id
            ? 'selected'
            : '';

        return `

          <option
            value="${id}"
            ${selected}
          >

            ${escapeHtmlEdit(
              nombre
            )}

          </option>
        `;
      }
    )
    .join('');
}


function escapeHtmlEdit(
  value
) {

  return String(
    value ?? ''
  )
    .replace(
      /&/g,
      '&amp;'
    )
    .replace(
      /</g,
      '&lt;'
    )
    .replace(
      />/g,
      '&gt;'
    )
    .replace(
      /"/g,
      '&quot;'
    )
    .replace(
      /'/g,
      '&#039;'
    );
}


/* ============================================================
 * CONFIGURACIÓN
 * ============================================================ */

function configurarPedidoEditable() {

  document.querySelectorAll('.edit-order-item').forEach(configurarDetalle);
  document.getElementById('btnSaveOrderChanges')?.addEventListener('click',guardarCambiosPedido);
}

/* ============================================================
 * DETALLE
 * ============================================================ */

function configurarDetalle(
  card
) {

  if (card.dataset.configured=== '1') {

    return;
  }

  card.dataset.configured ='1';

  const qty =card.querySelector('[data-qty]');
  const minus =card.querySelector('[data-qty-minus]');
  const plus =card.querySelector('[data-qty-plus]');
  const remove =card.querySelector('[data-remove-detail]');
  const deliveryType =card.querySelector('[data-delivery-type]');

  /*
   * ==========================================
   * RESTAR
   * ==========================================
   */

  minus?.addEventListener(
    'click',
    () => {

      const actual =
        Math.max(
          1,
          Number(
            qty?.value
            || 1
          )
        );

      const nuevo =Math.max(1,actual - 1);

      if (qty) {
        qty.value =nuevo;
      }

      minus.disabled =nuevo <= 1;
      recalcularResumen();
    }
  );

  /*
   * ==========================================
   * SUMAR
   * ==========================================
   */

  plus?.addEventListener(
    'click',
    () => {

      const actual =
        Math.max(
          1,
          Number(
            qty?.value
            || 1
          )
        );

      const nuevo =actual + 1;

      if (qty) {

        qty.value =nuevo;
      }

      if (minus) {

        minus.disabled =false;
      }

      recalcularResumen();
    }
  );

  /*
   * ==========================================
   * INPUT CANTIDAD
   * ==========================================
   */

  qty?.addEventListener(
    'input',
    () => {

      qty.value =
        qty.value.replace(
          /\D/g,
          ''
        );
    }
  );

  qty?.addEventListener(
    'change',
    () => {

      let value =
        parseInt(
          qty.value,
          10
        );


      if (!Number.isInteger(value) || value < 1) {
        value = 1;
      }

      qty.value =value;

      if (minus) {
        minus.disabled =
          value <= 1;
      }

      recalcularResumen();
    }
  );

  /*
   * ==========================================
   * ENTREGA
   * ==========================================
   */

  deliveryType
    ?.addEventListener(
      'change',
      () => {

        actualizarEntrega(
          card
        );
      }
    );

  /*
   * ==========================================
   * ELIMINAR
   * ==========================================
   */

  remove?.addEventListener(
    'click',
    () => {

      const cards =
        document.querySelectorAll(
          '.edit-order-item'
        );


      if (
        cards.length <= 1
      ) {

        mostrarAlertaEditar(
          'warning',
          'No puedes eliminar todas las unidades',
          'El pedido debe conservar al menos una unidad.'
        );

        return;
      }

      const esNueva =card.dataset.newItem=== '1';
      const idunidad =Number(card.dataset.unitId || 0);

      if (esNueva) {
        eliminarUnidadTemporalEdicion(idunidad);
      }
      card.remove();
      recalcularResumen();
    }
  );

  /*
   * Estado inicial.
   */
  if (minus && qty) {

    minus.disabled =
      Number(
        qty.value || 1
      ) <= 1;
  }

  actualizarEntrega(card);
}

function eliminarUnidadTemporalEdicion(idunidad) {

  const storageKey =getPedidoEdicionStorageKey();

  if (!storageKey) {
    return;
  }

  const nuevas =obtenerNuevasUnidadesPedido();
  const actualizadas =
    nuevas.filter(
      item =>
        Number(
          item.idunidad
          || item.id
        )
        !== Number(
          idunidad
        )
    );

  if (actualizadas.length) {

    localStorage.setItem(
      storageKey,
      JSON.stringify(
        actualizadas
      )
    );

  } else {
    localStorage.removeItem(storageKey);
  }
}

/* ============================================================
 * ENTREGA
 * ============================================================ */

function actualizarEntrega(card) {

  const deliveryType =
    card.querySelector(
      '[data-delivery-type]'
    );

  const type =String(deliveryType?.value || '').toUpperCase();
  const sucursalWrapper =card.querySelector('[data-sucursal-wrapper]');
  const addressWrapper =card.querySelector('[data-address-wrapper]');
  const sucursal =card.querySelector('[data-sucursal]');
  const address =card.querySelector('[data-address]');

  /*
   * ==========================================
   * SUCURSAL
   * ==========================================
   */

  if (type === 'SUCURSAL') {

    if (sucursalWrapper) {
      sucursalWrapper.hidden =
        false;
    }

    if (addressWrapper) {
      addressWrapper.hidden =
        true;
    }

    /*
     * Dirección manual ya no aplica.
     */
    if (address) {
      address.value =
        '';
    }
    return;
  }

  /*
   * ==========================================
   * OTRA DIRECCIÓN
   * ==========================================
   */
  if (type === 'OTRA_DIRECCION') {

    if (sucursalWrapper) {
      sucursalWrapper.hidden =
        true;
    }

    if (addressWrapper) {

      addressWrapper.hidden =
        false;
    }

    /*
     * Sucursal ya no aplica.
     */
    if (sucursal) {

      sucursal.value =
        '';
    }

    return;
  }

  /*
   * ==========================================
   * SIN SELECCIÓN
   * ==========================================
   */

  if (sucursalWrapper) {
    sucursalWrapper.hidden =
      true;
  }

  if (addressWrapper) {

    addressWrapper.hidden =
      true;
  }
}


/* ============================================================
 * RESUMEN
 * ============================================================ */

function recalcularResumen() {

  const cards =Array.from(document.querySelectorAll('.edit-order-item'));
  let totalUnits =0;
  let subtotal =0;
  cards.forEach(
    card => {
      /*
       * Cantidad.
       */
      const qty =
        Math.max(
          1,
          Number(
            card.querySelector(
              '[data-qty]'
            )?.value
            || 1
          )
        );


      /*
       * Precio unitario.
       */
      const price =
        Math.max(
          0,
          Number(
            card.dataset.price
            || 0
          )
        );


      /*
       * Total estimado de la línea.
       */
      const lineTotal =
        price
        * qty;


      /*
       * Actualizar visualmente
       * el importe estimado.
       */
      const lineTotalElement =
        card.querySelector(
          '[data-line-total]'
        );


      if (lineTotalElement) {

        lineTotalElement.textContent =
          formatMoneyEdit(
            lineTotal
          );
      }

      /*
       * Acumular.
       */
      totalUnits +=qty;
      subtotal +=lineTotal;
    }
  );


  /*
   * IVA 16%.
   */
  const iva =subtotal * 0.16;

  /*
   * Total final.
   */
  const total =subtotal+ iva;

  actualizarTexto('editTotalModels',cards.length);
  actualizarTexto('editTotalUnits',totalUnits);
  actualizarTexto('editSubtotal',
  formatMoneyEdit(subtotal)
  );

  actualizarTexto('editIva',formatMoneyEdit(iva));
  actualizarTexto('editTotal',formatMoneyEdit(total));
}


function actualizarTexto(id,value) {

  const element =document.getElementById(id);
  if (element) {
    element.textContent =value;
  }
}


/* ============================================================
 * OBTENER PRODUCTOS
 * ============================================================ */

function obtenerProductosEditar() {
  return Array.from(
    document.querySelectorAll(
      '.edit-order-item'
    )
  ).map(
    card => {
      return {
        idpedido_detalle:Number(card.dataset.detailId || 0),
        idunidad:Number(card.dataset.unitId || 0),
        cantidad:Math.max(1,Number(card.querySelector('[data-qty]')?.value || 1)),
        tipo_entrega:card.querySelector('[data-delivery-type]')?.value || '',
        idsucursal_entrega:card.querySelector('[data-sucursal]')?.value || null,
        direccion_entrega:card.querySelector('[data-address]')?.value?.trim() || ''

      };
    }
  );
}


/* ============================================================
 * VALIDAR
 * ============================================================ */

function validarPedidoEditar(payload) {

  if (!payload.productos.length) {
    mostrarAlertaEditar('warning','Pedido vacío','Debes conservar al menos una unidad.');
    return false;
  }


  for (const item of payload.productos) {

    if (item.cantidad < 1) {
      mostrarAlertaEditar('warning','Cantidad inválida','Todas las unidades deben tener una cantidad mínima de 1.');
      return false;
    }


    if (item.tipo_entrega === 'SUCURSAL' && !item.idsucursal_entrega) {
      mostrarAlertaEditar('warning','Sucursal requerida','Selecciona una sucursal para todas las unidades con entrega en sucursal.');
      return false;
    }


    if (item.tipo_entrega=== 'OTRA_DIRECCION' && !item.direccion_entrega) {
      mostrarAlertaEditar('warning','Dirección requerida','Captura una dirección para las unidades con entrega en otra dirección.');
      return false;
    }
  }

  return true;
}


/* ============================================================
 * GUARDAR
 * ============================================================ */

async function guardarCambiosPedido() {

  const config =window.EDIT_ORDER|| {};
  const payload = {
    clave:config.clave|| '',

    fecha_requerida:document.getElementById('editRequiredDate') ?.value|| '',
    mes_facturacion_deseado:document.getElementById('editBillingMonth') ?.value|| '',

    prioridad:
      document
        .getElementById(
          'editPriority'
        )
        ?.value
      || 'NORMAL',

    observaciones:
      document
        .getElementById(
          'editObservations'
        )
        ?.value
        ?.trim()
      || '',

    productos:obtenerProductosEditar()
  };

  if (!validarPedidoEditar(payload)) {
    return;
  }

  let confirmacion =true;
  if (typeof Swal !== 'undefined' && Swal.fire) {

    const result =
      await Swal.fire({
        icon:'question',
        title:'¿Guardar cambios?',
        text:'Se actualizará la solicitud y quedará registrada una nueva versión del pedido.',
        showCancelButton:true,
        confirmButtonText:'Sí, guardar cambios',
        cancelButtonText:'Cancelar',
        reverseButtons:
          true
      });

    confirmacion =result.isConfirmed;
  }

  if (!confirmacion) {
    return;
  }

  const button =document.getElementById('btnSaveOrderChanges');

  if (button) {

    button.disabled =true;
    button.dataset.originalText =button.textContent;
    button.textContent ='Guardando...';
  }

  try {

    const response =await fetch(
        `${base_url}/orders/actualizarPedido`,
        {
          method:'POST',

          headers: {

            'Content-Type':
              'application/json',

            'Accept':
              'application/json'
          },

          body:JSON.stringify(payload)
        }
      );

    const text =await response.text();
    let result;
    try {

      result =
        JSON.parse(
          text
        );

    } catch (error) {

      console.error('Respuesta servidor:',text);
      throw new Error(
        'La respuesta del servidor no tiene formato JSON.'
      );
    }


    if (!response.ok || !result.status) {

      throw new Error(
        result.message
        || 'No fue posible actualizar el pedido.'
      );
    }


    if (typeof Swal !== 'undefined' && Swal.fire) {

      await Swal.fire({
        icon:'success',
        title:'Pedido actualizado',
        text:result.message|| 'Los cambios fueron guardados correctamente.',
        confirmButtonText:'Continuar'
      });

    }
    const storageKey =getPedidoEdicionStorageKey();

if (storageKey) { 
  localStorage.removeItem(
    storageKey
  );
}


/*
 * La edición ya fue confirmada.
 * Limpiamos también el contexto global.
 */
sessionStorage.removeItem('orders_edit_context');

    window.location.href =
      result.data?.redirect
      || `${base_url}/orders/micuenta`;


  } catch (error) {

    console.error(
      error
    );

    mostrarAlertaEditar(
      'error',
      'No fue posible guardar',
      error.message
    );

  } finally {

    if (button) {

      button.disabled =
        false;

      button.textContent =
        button.dataset
          .originalText
        || 'Guardar cambios';
    }
  }
} 


/* ============================================================
 * UTILIDADES
 * ============================================================ */

function formatMoneyEdit(value) {

  return new Intl.NumberFormat(
    'es-MX',
    {
      style:'currency',
      currency:'MXN',
      minimumFractionDigits:2
    }
  ).format(
    Number(
      value || 0
    )
  );
}

function mostrarAlertaEditar(icon,title,text) {

  if (typeof Swal !== 'undefined' && Swal.fire) {

    return Swal.fire({
      icon,
      title,
      text,
      confirmButtonText:
        'Aceptar'
    });
  }
  alert(text);

  return Promise.resolve();
}