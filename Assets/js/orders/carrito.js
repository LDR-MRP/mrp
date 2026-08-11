"use strict";

/* ============================================================
 * CONFIGURACIÓN
 * ============================================================ */

const portalConfig = window.ordersPortal || {};

const portalIdCliente = Number(
  portalConfig.idcliente || 0
);

const portalBaseUrl =
  portalConfig.baseUrl
  || window.base_url
  || "";
 
/*
 * Cada distribuidor tendrá su propia llave:
 *
 * cartAD_cliente_100
 * cartAD_cliente_200
 */
const CART_STORAGE_KEY =
  `cartAD_cliente_${portalIdCliente}`;

const IVA_RATE = 0.16;

let sucursalesEntrega = [];

/* ============================================================
 * UTILIDADES
 * ============================================================ */

/**
 * Convierte un precio tipo "$685,000.00"
 * a número.
 */
function parsePrice(price) {
  if (typeof price === "number") {
    return Number.isFinite(price)
      ? price
      : 0;
  }

  return (
    Number(
      String(price || "")
        .replace(/[^0-9.-]/g, "")
    ) || 0
  );
}

/**
 * Formatea una cantidad como moneda mexicana.
 */
function formatMoney(amount) {
  return new Intl.NumberFormat(
    "es-MX",
    {
      style: "currency",
      currency: "MXN",
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }
  ).format(
    Number(amount || 0)
  );
}

/**
 * Escapa valores antes de agregarlos al HTML.
 */
function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

/**
 * Lee respuestas JSON.
 */
async function readJsonResponse(response) {
  const text = await response.text();

  try {
    return JSON.parse(text);
  } catch (error) {
    console.error(
      "Respuesta real del servidor:",
      text
    );

    throw new Error(
      "La respuesta del servidor no tiene formato JSON."
    );
  }
}

/**
 * Obtiene el carrito exclusivo del distribuidor.
 */
function getCart() {
  if (portalIdCliente <= 0) {
    return [];
  }

  try {
    const storedCart = localStorage.getItem(
      CART_STORAGE_KEY
    );

    if (!storedCart) {
      return [];
    }

    const cart = JSON.parse(storedCart);

    return Array.isArray(cart)
      ? cart
      : [];
  } catch (error) {
    console.error(
      "No fue posible leer el carrito:",
      error
    );

    return [];
  }
}

/**
 * Guarda el carrito exclusivo del distribuidor.
 */
function setCart(cart) {
  if (portalIdCliente <= 0) {
    return;
  }

  const validCart = Array.isArray(cart)
    ? cart
    : [];

  localStorage.setItem(
    CART_STORAGE_KEY,
    JSON.stringify(validCart)
  );
}

/**
 * Elimina únicamente el carrito del cliente actual.
 */
function clearCurrentCart() {
  localStorage.removeItem(
    CART_STORAGE_KEY
  );
}

/**
 * Actualiza el contador del header.
 */
function updateCartCount() {
  const cart = getCart();

  const totalUnits = cart.reduce(
    (total, item) => {
      return total
        + Math.max(
          1,
          Number(item.qty || 1)
        );
    },
    0
  );

  const cartCount = document.getElementById(
    "cartCount"
  );

  if (cartCount) {
    cartCount.textContent =
      String(totalUnits);
  }
}

/**
 * Busca una sucursal por identificador.
 */
function findSucursal(idsucursal) {
  return sucursalesEntrega.find(
    (sucursal) =>
      Number(sucursal.idsucursal)
      === Number(idsucursal)
  );
}

/* ============================================================
 * SUCURSALES
 * ============================================================ */

/**
 * Carga las sucursales pertenecientes al cliente
 * autenticado.
 */
async function loadDeliveryBranches() {
  const response = await fetch(
    `${portalBaseUrl}/orders/getSucursalesEntrega`,
    {
      method: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        Accept: "application/json",
      },
      credentials: "same-origin",
    }
  );

  const result = await readJsonResponse(
    response
  );

  if (
    response.status === 401
    || response.status === 403
  ) {
    const redirect =
      result.data?.redirect
      || `${portalBaseUrl}/orders/login`;

    window.location.href = redirect;
    return;
  }

  if (!response.ok || !result.status) {
    throw new Error(
      result.message
      || "No fue posible cargar las sucursales."
    );
  }

  sucursalesEntrega = Array.isArray(
    result.data?.sucursales
  )
    ? result.data.sucursales
    : [];
}

/**
 * Construye las opciones del select de sucursales.
 */
function buildBranchOptions(
  selectedValue = ""
) {
  const options = [
    `
      <option value="">
        Selecciona una sucursal
      </option>
    `,
  ];

  sucursalesEntrega.forEach(
    (sucursal) => {
      const idsucursal = Number(
        sucursal.idsucursal
      );

      const selected =
        String(selectedValue)
          === String(idsucursal)
          ? "selected"
          : "";

      const direction =
        sucursal.direccion
          ? ` — ${sucursal.direccion}`
          : "";

      options.push(`
        <option
          value="${idsucursal}"
          ${selected}
        >
          ${escapeHtml(
            sucursal.nombre_sucursal
          )}
          ${escapeHtml(direction)}
        </option>
      `);
    }
  );

  const otherSelected =
    selectedValue === "OTRO"
      ? "selected"
      : "";

  options.push(`
    <option
      value="OTRO"
      ${otherSelected}
    >
      Otra dirección de entrega
    </option>
  `);

  return options.join("");
}

/* ============================================================
 * RENDER DEL CARRITO
 * ============================================================ */

/**
 * Calcula los totales actuales.
 */
function calculateCartTotals(cart) {
  const totalModels = cart.length;

  const totalUnits = cart.reduce(
    (total, item) => {
      return total
        + Math.max(
          1,
          Number(item.qty || 1)
        );
    },
    0
  );

  const subtotal = cart.reduce(
    (total, item) => {
      const quantity = Math.max(
        1,
        Number(item.qty || 1)
      );

      const price = parsePrice(
        item.precio
      );

      return total
        + price * quantity;
    },
    0
  );

  const iva = subtotal * IVA_RATE;

  const total = subtotal + iva;

  return {
    totalModels,
    totalUnits,
    subtotal,
    iva,
    total,
  };
}

/**
 * Actualiza el resumen lateral.
 */
function renderCartSummary(cart) {
  const totals = calculateCartTotals(
    cart
  );

  const totalModels =
    document.getElementById(
      "totalModels"
    );

  const totalUnits =
    document.getElementById(
      "totalUnits"
    );

  const subtotalEstimated =
    document.getElementById(
      "subtotalEstimated"
    );

  const ivaEstimated =
    document.getElementById(
      "ivaEstimated"
    );

  const totalEstimated =
    document.getElementById(
      "totalEstimated"
    );

  const cartToolbarModels =
    document.getElementById(
      "cartToolbarModels"
    );

  const cartToolbarUnits =
    document.getElementById(
      "cartToolbarUnits"
    );

  if (totalModels) {
    totalModels.textContent =
      String(totals.totalModels);
  }

  if (totalUnits) {
    totalUnits.textContent =
      String(totals.totalUnits);
  }

  if (subtotalEstimated) {
    subtotalEstimated.textContent =
      formatMoney(totals.subtotal);
  }

  if (ivaEstimated) {
    ivaEstimated.textContent =
      formatMoney(totals.iva);
  }

  if (totalEstimated) {
    totalEstimated.textContent =
      formatMoney(totals.total);
  }

  if (cartToolbarModels) {
    cartToolbarModels.textContent =
      `${totals.totalModels} ${
        totals.totalModels === 1
          ? "modelo"
          : "modelos"
      }`;
  }

  if (cartToolbarUnits) {
    cartToolbarUnits.textContent =
      `${totals.totalUnits} ${
        totals.totalUnits === 1
          ? "unidad seleccionada"
          : "unidades seleccionadas"
      }`;
  }
}

/**
 * Renderiza el estado vacío.
 */
function renderEmptyCart(container) {
  container.innerHTML = `
    <div class="cart-empty-state">

      <div class="empty-icon">
        <i class="ri-shopping-cart-2-line"></i>
      </div>

      <h3>
        Tu carrito está vacío
      </h3>

      <p>
        Regresa al catálogo y agrega unidades
        para generar una solicitud de pedido.
      </p>

      <a
        href="${portalBaseUrl}/orders/home#catalogo"
        class="btn btn-primary"
      >
        <i class="ri-add-line"></i>
        Ver catálogo
      </a>

    </div>
  `;
}

/**
 * Renderiza el carrito.
 */
function renderCart() {
  const container =
    document.getElementById(
      "cartItems"
    );

  if (!container) {
    return;
  }

  const cart = getCart();

  renderCartSummary(cart);
  updateCartCount();

  if (!cart.length) {
    renderEmptyCart(container);
    return;
  }

  container.innerHTML = cart
    .map((item) => {
      const id = Number(item.id);

      const quantity = Math.max(
        1,
        Number(item.qty || 1)
      );

      const price = parsePrice(
        item.precio
      );

      const subtotal =
        price * quantity;

      /*
       * Datos de entrega almacenados
       * dentro de cada producto.
       */
      const deliveryType =
        item.tipo_entrega || "";

      const selectedBranch =
        deliveryType === "SUCURSAL"
          ? String(
              item.idsucursal_entrega
              || ""
            )
          : deliveryType
              === "OTRA_DIRECCION"
            ? "OTRO"
            : "";

      const showOtherAddress =
        deliveryType
        === "OTRA_DIRECCION";

      const otherAddress =
        item.direccion_entrega
        || "";

      return `
        <article
          class="cart-product-card"
          data-id="${id}"
        >

          <div class="cart-product-main">

            <!-- IMAGEN -->
            <div class="cart-product-image">

              ${
                item.img
                  ? `
                    <img
                      src="${escapeHtml(
                        item.img
                      )}"
                      alt="${escapeHtml(
                        item.nombre
                      )}"
                    >
                  `
                  : `
                    <div class="cart-product-placeholder">
                      <i class="ri-car-line"></i>
                    </div>
                  `
              }

            </div>

            <!-- INFORMACIÓN -->
            <div class="cart-product-info">

              <div class="cart-product-header">

                <div>
                  <span class="cart-product-category">
                    ${escapeHtml(
                      item.cat || "Unidad"
                    )}
                  </span>

                  <h3>
                    ${escapeHtml(
                      item.nombre
                    )}
                  </h3>
                </div>

         <button
  type="button"
  class="btn-remove-cart"
  data-remove-id="${id}"
  title="Eliminar unidad"
  aria-label="Eliminar ${escapeHtml(item.nombre)} del carrito"
>
  <i class="ri-delete-bin-6-line"></i>
</button>

              </div>

              <p class="cart-product-description">
                ${escapeHtml(
                  item.desc || ""
                )}
              </p>

              <div class="cart-product-details">

                <div>
                  <span>Precio unitario</span>

                  <strong>
                    ${formatMoney(price)}
                  </strong>
                </div>

                <div>
                  <span>Subtotal estimado</span>

                  <strong>
                    ${formatMoney(subtotal)}
                  </strong>
                </div>

              </div>

              <!-- CANTIDAD -->
              <div class="cart-product-actions">

                <div class="quantity-wrapper">

                  <span class="quantity-label">
                    Cantidad
                  </span>

                  <div class="quantity-control">

                    <button
                      type="button"
                      class="quantity-btn"
                      data-qty-action="decrease"
                      data-product-id="${id}"
                      ${quantity <= 1
                        ? "disabled"
                        : ""}
                    >
                      <span aria-hidden="true">
                        −
                      </span>
                    </button>

                    <input
                      type="number"
                      min="1"
                      max="1000"
                      step="1"
                      value="${quantity}"
                      class="quantity-input"
                      data-qty-input="${id}"
                    >

                    <button
                      type="button"
                      class="quantity-btn"
                      data-qty-action="increase"
                      data-product-id="${id}"
                    >
                      <span aria-hidden="true">
                        +
                      </span>
                    </button>

                  </div>

                </div>

              </div>

              <!-- DESTINO DE ENTREGA -->
              <div class="cart-delivery-section">

                <div class="cart-delivery-header">
                  <div>
                    <span class="delivery-step">
                      Destino de entrega
                    </span>

                    <h4>
                      ¿Dónde se entregará este modelo?
                    </h4>
                  </div>

                  <i class="ri-map-pin-line"></i>
                </div>

                <div class="form-group">
                  <label for="delivery-${id}">
                    Sucursal o dirección
                  </label>

                  <select
                    id="delivery-${id}"
                    class="form-control delivery-select"
                    data-delivery-id="${id}"
                  >
                    ${buildBranchOptions(
                      selectedBranch
                    )}
                  </select>
                </div>

                <div
                  class="form-group other-address-group ${
                    showOtherAddress
                      ? ""
                      : "d-none"
                  }"
                  id="otherAddressGroup-${id}"
                >
                  <label for="otherAddress-${id}">
                    Dirección completa de entrega
                  </label>

                  <textarea
                    id="otherAddress-${id}"
                    class="form-control other-address-input"
                    data-address-id="${id}"
                    rows="3"
                    maxlength="500"
                    placeholder="Ejemplo: Av. Principal 100, Col. Centro, Toluca, Estado de México, C.P. 50000"
                  >${escapeHtml(
                    otherAddress
                  )}</textarea>

                  <small class="form-help">
                    Incluye calle, número, colonia,
                    municipio, estado y código postal.
                  </small>
                </div>

              </div>

            </div>

          </div>

        </article>
      `;
    })
    .join("");
}

/* ============================================================
 * MANEJO DEL CARRITO
 * ============================================================ */

/**
 * Obtiene un elemento del carrito.
 */
function getCartItem(id) {
  const cart = getCart();

  return cart.find(
    (item) =>
      Number(item.id)
      === Number(id)
  );
}

/**
 * Aumenta o disminuye cantidad.
 */
function changeCartQty(id, change) {
  const cart = getCart();

  const item = cart.find(
    (product) =>
      Number(product.id)
      === Number(id)
  );

  if (!item) {
    return;
  }

  let quantity = Number(
    item.qty || 1
  );

  quantity += Number(change);

  if (quantity < 1) {
    quantity = 1;
  }

  if (quantity > 1000) {
    quantity = 1000;
  }

  item.qty = quantity;

  setCart(cart);
  renderCart();
}

/**
 * Cambia la cantidad manualmente.
 */
function setCartQty(id, value) {
  const cart = getCart();

  const item = cart.find(
    (product) =>
      Number(product.id)
      === Number(id)
  );

  if (!item) {
    return;
  }

  let quantity = parseInt(
    value,
    10
  );

  if (
    !Number.isInteger(quantity)
    || quantity < 1
  ) {
    quantity = 1;
  }

  if (quantity > 1000) {
    quantity = 1000;
  }

  item.qty = quantity;

  setCart(cart);
  renderCart();
}

/**
 * Elimina un producto.
 */
function removeCart(id) {
  const cart = getCart().filter(
    (item) =>
      Number(item.id)
      !== Number(id)
  );

  setCart(cart);
  renderCart();
}

/**
 * Cambia el destino de entrega.
 */
function setDeliveryDestination(
  id,
  selectedValue
) {
  const cart = getCart();

  const item = cart.find(
    (product) =>
      Number(product.id)
      === Number(id)
  );

  if (!item) {
    return;
  }

  if (selectedValue === "OTRO") {
    item.tipo_entrega =
      "OTRA_DIRECCION";

    item.idsucursal_entrega = null;

    item.direccion_entrega =
      item.direccion_entrega || "";
  } else if (
    Number(selectedValue) > 0
  ) {
    item.tipo_entrega = "SUCURSAL";

    item.idsucursal_entrega =
      Number(selectedValue);

    item.direccion_entrega = "";
  } else {
    item.tipo_entrega = "";

    item.idsucursal_entrega = null;

    item.direccion_entrega = "";
  }

  setCart(cart);
  renderCart();
}

/**
 * Guarda la dirección personalizada sin volver
 * a renderizar toda la tarjeta mientras se escribe.
 */
function setOtherAddress(id, value) {
  const cart = getCart();

  const item = cart.find(
    (product) =>
      Number(product.id)
      === Number(id)
  );

  if (!item) {
    return;
  }

  item.tipo_entrega =
    "OTRA_DIRECCION";

  item.idsucursal_entrega =
    null;

  item.direccion_entrega =
    String(value || "")
      .substring(0, 500);

  setCart(cart);
}

/* ============================================================
 * VALIDACIONES
 * ============================================================ */

/**
 * Valida todos los destinos del carrito.
 */
function validateDeliveryDestinations(
  cart
) {
  for (const item of cart) {
    const id = Number(item.id);

    const name =
      item.nombre
      || `Producto ${id}`;

    if (
      item.tipo_entrega
      === "SUCURSAL"
    ) {
      const idsucursal = Number(
        item.idsucursal_entrega || 0
      );

      if (idsucursal <= 0) {
        return {
          valid: false,
          id,
          field: `delivery-${id}`,
          message:
            `Selecciona una sucursal para ${name}.`,
        };
      }

      const branch =
        findSucursal(idsucursal);

      if (!branch) {
        return {
          valid: false,
          id,
          field: `delivery-${id}`,
          message:
            `La sucursal seleccionada para ${name} ya no está disponible.`,
        };
      }

      continue;
    }

    if (
      item.tipo_entrega
      === "OTRA_DIRECCION"
    ) {
      const address = String(
        item.direccion_entrega || ""
      ).trim();

      if (address.length < 10) {
        return {
          valid: false,
          id,
          field: `otherAddress-${id}`,
          message:
            `Escribe una dirección completa para ${name}.`,
        };
      }

      continue;
    }

    return {
      valid: false,
      id,
      field: `delivery-${id}`,
      message:
        `Selecciona el destino de entrega para ${name}.`,
    };
  }

  return {
    valid: true,
  };
}

/**
 * Enfoca un campo dentro de una tarjeta.
 */
function focusCartField(fieldId) {
  const field =
    document.getElementById(
      fieldId
    );

  if (!field) {
    return;
  }

  field.scrollIntoView({
    behavior: "smooth",
    block: "center",
  });

  setTimeout(() => {
    field.focus();
  }, 350);
}

/* ============================================================
 * GENERACIÓN DEL PEDIDO
 * ============================================================ */

/**
 * Deshabilita el botón mientras se procesa.
 */
function lockRequestButton(button) {
  if (!button) {
    return;
  }

  button.dataset.originalHtml =
    button.innerHTML;

  button.disabled = true;

  button.innerHTML = `
    <span
      class="spinner-border spinner-border-sm me-2"
    ></span>
    Generando solicitud...
  `;
}

/**
 * Reactiva el botón.
 */
function unlockRequestButton(button) {
  if (!button) {
    return;
  }

  button.disabled = false;

  if (button.dataset.originalHtml) {
    button.innerHTML =
      button.dataset.originalHtml;
  }
}

/**
 * Genera la solicitud real.
 */
async function generateRequest() {
  const cart = getCart();

  if (!cart.length) {
    showCartMessage(
      "warning",
      "Carrito vacío",
      "Agrega unidades antes de generar la solicitud."
    );

    return;
  }

  const deliveryValidation =
    validateDeliveryDestinations(
      cart
    );

  if (!deliveryValidation.valid) {
    showCartMessage(
      "warning",
      "Destino requerido",
      deliveryValidation.message
    );

    focusCartField(
      deliveryValidation.field
    );

    return;
  }

  const requiredDate =
    document.getElementById(
      "orderRequiredDate"
    )?.value || "";

  const month =
    document.getElementById(
      "orderMonth"
    )?.value || "";

  const priority =
    document.getElementById(
      "orderPriority"
    )?.value || "NORMAL";

  const notes =
    document.getElementById(
      "orderNotes"
    )?.value?.trim() || "";

  const confirmation =
    await confirmRequest();

  if (!confirmation) {
    return;
  }

  const payload = {
    fecha_requerida:
      requiredDate || null,

    mes_facturacion_deseado:
      month || null,

    prioridad: priority,

    observaciones: notes,

    productos: cart.map(
      (item) => ({
        idproducto: Number(item.id),

        cantidad: Math.max(
          1,
          Number(item.qty || 1)
        ),

        tipo_entrega:
          item.tipo_entrega,

        idsucursal_entrega:
          item.tipo_entrega
            === "SUCURSAL"
            ? Number(
                item.idsucursal_entrega
              )
            : null,

        direccion_entrega:
          item.tipo_entrega
            === "OTRA_DIRECCION"
            ? String(
                item.direccion_entrega
                || ""
              ).trim()
            : null,
      })
    ),
  };

  const button =
    document.getElementById(
      "btnGenerateRequest"
    );

  try {
    lockRequestButton(button);

    const response = await fetch(
      `${portalBaseUrl}/orders/generarSolicitudPedido`,
      {
        method: "POST",

        headers: {
          "Content-Type":
            "application/json",

          "X-Requested-With":
            "XMLHttpRequest",

          Accept: "application/json",
        },

        credentials: "same-origin",

        body: JSON.stringify(
          payload
        ),
      }
    );

    const result =
      await readJsonResponse(
        response
      );

    if (
      response.status === 401
      || response.status === 403
    ) {
      await showCartMessage(
        "warning",
        "Sesión finalizada",
        result.message
        || "Inicia sesión nuevamente."
      );

      window.location.href =
        result.data?.redirect
        || `${portalBaseUrl}/orders/login`;

      return;
    }

    if (
      !response.ok
      || !result.status
    ) {
      throw new Error(
        result.message
        || "No fue posible generar la solicitud."
      );
    }

    clearCurrentCart();

    updateCartCount();
    renderCart();

    await showCartMessage(
      "success",
      "Solicitud generada",
      `Tu solicitud fue registrada con el folio ${result.data.folio_pedido}.`
    );

    if (result.data?.redirect) {
      window.location.href =
        result.data.redirect;
    }
  } catch (error) {
    console.error(
      "Error al generar pedido:",
      error
    );

    showCartMessage(
      "error",
      "No fue posible generar la solicitud",
      error.message
    );
  } finally {
    unlockRequestButton(button);
  }
}

/**
 * Confirma la generación.
 */
async function confirmRequest() {
  if (
    typeof Swal !== "undefined"
    && Swal.fire
  ) {
    const result = await Swal.fire({
      icon: "question",

      title:
        "¿Generar solicitud de pedido?",

      text:
        "Revisa cantidades y destinos de entrega antes de continuar.",

      showCancelButton: true,

      confirmButtonText:
        "Sí, generar solicitud",

      cancelButtonText:
        "Cancelar",

      reverseButtons: true,

      allowOutsideClick: false,
    });

    return result.isConfirmed;
  }

  return window.confirm(
    "¿Deseas generar la solicitud de pedido?"
  );
}

/**
 * Muestra mensajes.
 */
function showCartMessage(
  icon,
  title,
  text
) {
  if (
    typeof Swal !== "undefined"
    && Swal.fire
  ) {
    return Swal.fire({
      icon,
      title,
      text,
      confirmButtonText: "Aceptar",
    });
  }

  window.alert(text);

  return Promise.resolve();
}

/* ============================================================
 * EVENTOS
 * ============================================================ */

/**
 * Maneja clics delegados dentro del carrito.
 */
function handleCartClick(event) {
  const removeButton =
    event.target.closest(
      "[data-remove-id]"
    );

  if (removeButton) {
    removeCart(
      removeButton.dataset.removeId
    );

    return;
  }

  const quantityButton =
    event.target.closest(
      "[data-qty-action]"
    );

  if (quantityButton) {
    const id =
      quantityButton.dataset.productId;

    const action =
      quantityButton.dataset.qtyAction;

    changeCartQty(
      id,
      action === "increase"
        ? 1
        : -1
    );
  }
}

/**
 * Maneja cambios en selects e inputs.
 */
function handleCartChange(event) {
  const quantityInput =
    event.target.closest(
      "[data-qty-input]"
    );

  if (quantityInput) {
    setCartQty(
      quantityInput.dataset.qtyInput,
      quantityInput.value
    );

    return;
  }

  const deliverySelect =
    event.target.closest(
      "[data-delivery-id]"
    );

  if (deliverySelect) {
    setDeliveryDestination(
      deliverySelect.dataset.deliveryId,
      deliverySelect.value
    );
  }
}

/**
 * Maneja escritura de dirección personalizada.
 */
function handleCartInput(event) {
  const addressInput =
    event.target.closest(
      "[data-address-id]"
    );

  if (!addressInput) {
    return;
  }

  setOtherAddress(
    addressInput.dataset.addressId,
    addressInput.value
  );
}

/**
 * Inicialización.
 */
async function initCart() {
  if (portalIdCliente <= 0) {
    await showCartMessage(
      "error",
      "Sesión inválida",
      "No fue posible identificar al distribuidor."
    );

    window.location.href =
      `${portalBaseUrl}/orders/login`;

    return;
  }

  const cartContainer =
    document.getElementById(
      "cartItems"
    );

  try {
    await loadDeliveryBranches();

    renderCart();
  } catch (error) {
    console.error(
      "Error al inicializar carrito:",
      error
    );

    if (cartContainer) {
      cartContainer.innerHTML = `
        <div class="cart-empty-state">

          <div class="empty-icon">
            <i class="ri-error-warning-line"></i>
          </div>

          <h3>
            No fue posible cargar el carrito
          </h3>

          <p>
            ${escapeHtml(
              error.message
            )}
          </p>

          <button
            type="button"
            class="btn btn-primary"
            id="btnReloadCart"
          >
            Intentar nuevamente
          </button>

        </div>
      `;

      document
        .getElementById(
          "btnReloadCart"
        )
        ?.addEventListener(
          "click",
          () => window.location.reload()
        );
    }
  }

  document
    .getElementById("cartItems")
    ?.addEventListener(
      "click",
      handleCartClick
    );

  document
    .getElementById("cartItems")
    ?.addEventListener(
      "change",
      handleCartChange
    );

  document
    .getElementById("cartItems")
    ?.addEventListener(
      "input",
      handleCartInput
    );

  document
    .getElementById(
      "btnGenerateRequest"
    )
    ?.addEventListener(
      "click",
      generateRequest
    );
}

document.addEventListener(
  "DOMContentLoaded",
  initCart
);