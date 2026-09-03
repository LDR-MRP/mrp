'use strict';

/* ============================================================
 * CONFIGRACIÓN
 * ============================================================ */

let products = [];

let filteredProducts = [];

let currentPage = 1;

const PRODUCTS_PER_PAGE = 15;



/* ============================================================
 * CONTEXTO DEL CALOGO
 * ============================================================ */

function getCatalogContext() {

 
    const globalContext =
        typeof getOrdersEditContext
            === 'function'
            ? getOrdersEditContext()
            : null;

    if (globalContext?.activo) {

        return {modo:'editar',pedido:globalContext.pedido, esEdicion:true
        };
    }


    /*
     * Fallback por URL.
     */
    const params =new URLSearchParams(window.location.search);

    const modo =String(params.get('modo')|| 'normal').toLowerCase();

    const pedido = String(params.get('pedido')|| '').trim();

    return {modo,pedido,esEdicion:modo === 'editar'&& pedido !== ''};
}


function getPedidoEdicionStorageKey(clavePedido) {

  const clave =String(clavePedido || '').trim();

  if (!clave) {
    return null;
  }

  return `pedidoEdicion_${clave}`;
}


function getPedidoEdicionItems(clavePedido) {

  const key = getPedidoEdicionStorageKey(clavePedido);

  if (!key) {
    return [];
  }

  try {

    const data =JSON.parse(localStorage.getItem(key) || '[]');

    return Array.isArray(data)
      ? data
      : [];

  } catch (error) {

    console.error(
      'Error leyendo unidades del pedido en edición:',
      error
    );

    return [];
  }
}


function setPedidoEdicionItems(clavePedido,items) {

  const key =getPedidoEdicionStorageKey(clavePedido);

  if (!key) {
    return false;
  }

  localStorage.setItem(
    key,
    JSON.stringify(
      Array.isArray(items)
        ? items
        : []
    )
  );

  return true;
}



function getPortalClientId() {

  return Number(window.ordersPortal?.idcliente || 0);
}


function getCartStorageKey() {

  const idcliente =getPortalClientId();

  if (idcliente <= 0) {

    return null;
  }

  return `cartAD_cliente_${idcliente}`;
}


/* ============================================================
 * CARRITO
 * ============================================================ */

function getCart() {

  const storageKey =getCartStorageKey();

  if (!storageKey) {

    return [];
  }

  try {

    const storedCart =localStorage.getItem(storageKey);

    if (!storedCart) {

      return [];
    }

    const cart =
      JSON.parse(
        storedCart
      );

    return Array.isArray(cart)
      ? cart
      : [];

  } catch (error) {

    console.error(
      'Error leyendo carrito:',
      error
    );

    return [];
  }
}


function setCart(cart) {

  const storageKey =getCartStorageKey();

  if (!storageKey) {

    console.error(
      'No fue posible guardar el carrito porque no existe un distribuidor autenticado.'
    );

    mostrarMensajeCarrito(
      'error',
      'Sesión no válida',
      'No fue posible identificar al distribuidor. Inicia sesión nuevamente.'
    );

    return false;
  }

  const carritoValido =Array.isArray(cart)? cart: [];

  localStorage.setItem(
    storageKey,
    JSON.stringify(
      carritoValido
    )
  );

  updateCartCount();

  return true;
}


function updateCartCount() {

  const element =document.getElementById('cartCount');

  if (!element) {
    return;
  }

  const totalUnidades =getCart().reduce((total, item) => {

        const cantidad =
          Math.max(
            1,
            Number(item.qty || 1)
          );

        return total + cantidad;
      },
      0
    );

  element.textContent =
    String(totalUnidades);
}


function addToCart(id) {

  const idcliente =getPortalClientId();

  if (idcliente <= 0) {

    mostrarMensajeCarrito(
      'warning',
      'Inicia sesión',
      'Debes iniciar sesión para agregar unidades.'
    ).then(() => {

      window.location.href =
        `${base_url}/orders/login`;
    });

    return;
  }

  const item =
    products.find(
      product =>
        Number(product.id)
        === Number(id)
    );

  if (!item) {

    mostrarMensajeCarrito('error','Unidad no disponible','No fue posible localizar la unidad seleccionada.'
    );

    return;
  }

  if (
    Number(item.stock || 0)
    <= 0
  ) {

    mostrarMensajeCarrito(
      'warning',
      'Sin disponibilidad',
      'Esta unidad no cuenta con disponibilidad actualmente.'
    );

    return;
  }

  const context =getCatalogContext();

  /*
   * ==========================================
   * MODO EDIIÓN
   * ==========================================
   */
  if (context.esEdicion) {

    agregarUnidadPedidoEdicion(
      item,
      context.pedido,
      1
    );

    return;
  }

  /*
   * ==========================================
   * CARRITO NORMAL
   * ==========================================
   */

  agregarUnidadCarritoNormal(item,1);
}


function agregarUnidadCarritoNormal(item,cantidad = 1) {

  const cart =getCart();

  const found =
    cart.find(
      product =>
        Number(
          product.idunidad
          || product.id
        )
        === Number(
          item.idunidad
          || item.id
        )
    );

  if (found) {

    found.qty =
      Math.max(
        1,
        Number(found.qty || 1)
      )
      + cantidad;

  } else {

    cart.push(construirItemCarrito(item,cantidad));
  }

  const guardado =
    setCart(cart);

  if (!guardado) {
    return;
  }

  mostrarMensajeCarrito(
    'success',
    'Unidad agregada',
    `${item.nombre} fue agregada al carrito.`,
    {
      timer: 1400,
      showConfirmButton: false
    }
  );
}

function agregarUnidadPedidoEdicion(item,clavePedido,cantidad = 1) {

  const items = getPedidoEdicionItems(clavePedido);

  const existente =
    items.find(
      product =>
        Number(
          product.idunidad
          || product.id
        )
        === Number(
          item.idunidad
          || item.id
        )
    );

  if (existente) {

    existente.qty =
      Math.max(
        1,
        Number(existente.qty || 1)
      )
      + cantidad;

  } else {

    const nuevo =construirItemCarrito(item,cantidad);

    /*
     * Importantísimo:
     * todavía NO existe en ped_pedidos_detalle.
     */
    nuevo.idpedido_detalle = 0;

    nuevo.es_nuevo = true;

    items.push(nuevo);
  }

  const guardado =setPedidoEdicionItems(clavePedido,items);

  if (!guardado) {

    mostrarMensajeCarrito(
      'error',
      'No fue posible agregar',
      'No se pudo almacenar la unidad para editar el pedido.'
    );

    return;
  }

  mostrarMensajeCarrito(
    'success',
    'Unidad agregada al pedido',
    `${item.nombre} se agregó al pedido que estás editando.`,
    {
      showCancelButton: true,
      confirmButtonText:
        'Volver al pedido',
      cancelButtonText:
        'Agregar más unidades'
    }
  ).then(
    result => {

      if (
        result.isConfirmed
      ) {

        window.location.href =
          `${base_url}/orders/editarpedido/${encodeURIComponent(
            clavePedido
          )}`;
      }
    }
  );
}


function getUrlDetalleUnidad(idunidad) {

  const context =getCatalogContext();

  let url =`${base_url}/orders/detalle/${Number(idunidad)}`;

  if (context.esEdicion) {

    url +=`?modo=editar&pedido=${encodeURIComponent(context.pedido)}`;
  }

  return url;
}

function construirItemCarrito(item,cantidad = 1) {

  return {

    id:Number(item.id|| item.idunidad ),

    idunidad:Number(item.idunidad || item.id),

    modelo:String(item.modelo || ''),

    clave_modelo:String(item.clave_modelo || '' ),

    nombre:String(item.nombre || ''),

    version:String(item.version || ''),

    marca:String(item.marca || ''),

    anio: Number(item.anio || 0),

    motor:String(item.motor || ''),

    stock:Number(item.stock || 0),

    cat:String(item.modelo || 'unidad').toLowerCase(),

    precio:Number(item.precio || item.precio_estimado || 0),

    precio_estimado:
      Number(
        item.precio_estimado
        || item.precio
        || 0
      ),

    img:String(item.img || ''),

    desc:
      String(
        item.descripcion
        || item.desc
        || ''
      ),

    qty:
      Math.max(
        1,
        Number(cantidad || 1)
      ),

    tipo_entrega: '',

    idsucursal_entrega: null,

    direccion_entrega: ''
  };
}


function addToCartOLD(id) {

  const idcliente =getPortalClientId();

  if (idcliente <= 0) {

    mostrarMensajeCarrito(
      'warning',
      'Inicia sesión',
      'Debes iniciar sesión para agregar unidades al carrito.'
    ).then(() => {

      window.location.href = `${base_url}/orders/login`;
    });

    return;
  }

  const item =
    products.find(
      product =>
        Number(product.id)
        === Number(id)
    );

  if (!item) {

    mostrarMensajeCarrito(
      'error',
      'Unidad no disponible',
      'No fue posible localizar la unidad seleccionada.'
    );

    return;
  }

  if (Number(item.stock || 0) <= 0) {

    mostrarMensajeCarrito(
      'warning',
      'Sin disponibilidad',
      'Esta unidad no cuenta con disponibilidad actualmente.'
    );

    return;
  }

  const cart =getCart();

  const found =
    cart.find(
      product =>
        Number(product.id)
        === Number(item.id)
    );

  if (found) {

    found.qty =
      Math.max(
        1,
        Number(found.qty || 1)
      ) + 1;

  } else {

    cart.push({

      id: Number(item.id),

      idunidad: Number(item.idunidad),

      modelo:String(item.modelo || ''),

      clave_modelo:String(item.clave_modelo || ''),

      nombre:String(item.nombre || ''),

      version:String(item.version || ''),

      marca:String(item.marca || ''),

      anio:Number(item.anio || 0),

      motor:String(item.motor || ''),

      stock:Number(item.stock || 0),

      cat:String(item.modelo || 'unidad').toLowerCase(),

      precio:Number(item.precio || 0),

      precio_estimado:Number(item.precio || 0),

      img:String(item.img || ''),

      desc:String(item.descripcion || ''),

      qty:
        1,

      tipo_entrega:'',

      idsucursal_entrega: null,

      direccion_entrega:''
    });
  }

  const guardado =setCart(cart);

  if (!guardado) {
    return;
  }

  mostrarMensajeCarrito(
    'success',
    'Unidad agregada',
    `${item.nombre} fue agregada al carrito.`,
    {
      timer: 1400,
      showConfirmButton: false
    }
  );
}


function mostrarMensajeCarrito(icon,title,text,options = {}) {

  if (typeof Swal !== 'undefined'&& Swal.fire) {

    return Swal.fire({
      icon,
      title,
      text,
      confirmButtonText:
        'Aceptar',
      ...options
    });
  }

  alert(text);

  return Promise.resolve();
}


/* ============================================================
 * UTILIDADES
 * ============================================================ */

function escapeHtml(value) {

  return String(
    value ?? ''
  )
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}


function formatMoney(value) {

  return new Intl.NumberFormat(
    'es-MX',
    {
      style:
        'currency',

      currency:
        'MXN',

      minimumFractionDigits:
        2
    }
  ).format(
    Number(value || 0)
  );
}


function obtenerRutaImagen(ruta) {

  const imagen =
    String(
      ruta || ''
    ).trim();

  if (!imagen) {

    return `${base_url}/Assets/images/no-image.png`;
  }

  if (imagen.startsWith('http://')|| imagen.startsWith('https://')) {

    return imagen;
  }

  return `${base_url}/${imagen.replace(/^\/+/, '')}`;
}


/* ============================================================
 * NORMALIZAR PRODUCTO
 * ============================================================ */

function normalizarProducto(unidad) {

  return {

    id:Number(unidad.idunidad),

    idunidad:Number(unidad.idunidad),

    modelo:String(unidad.modelo || '').trim(),

    clave_modelo:String(unidad.clave_modelo || '').trim(),

    nombre:String(unidad.nombre || '').trim(),

    version:String(unidad.version || '').trim(),

    descripcion:String(unidad.descripcion || '' ).trim(),

    anio:Number(unidad.anio || 0),

    marca:String(unidad.marca || '').trim(),

    motor:String(unidad.motor || '').trim(),

    stock:Number(unidad.stock || 0),

    precio:Number(unidad.precio_estimado || 0),

    img:obtenerRutaImagen(unidad.imagen_caratula)

  };
}


/* ============================================================
 * CARGAR PRODUCTOS
 * ============================================================ */

async function cargarProductos() {

  const grid =document.getElementById('productsGrid');

  if (grid) {

    grid.innerHTML = `
      <div class="catalog-loading">

        <strong>
          Cargando catálogo...
        </strong>

        <span>
          Consultando unidades disponibles.
        </span>

      </div>
    `;
  }

  try {

    const response =
      await fetch(
        `${base_url}/orders/getUnidades`,
        {
          method:
            'GET',

          headers: {
            'Accept':
              'application/json'
          },

          cache:
            'no-store'
        }
      );

    const text =await response.text();

    let result;

    try {

      result =JSON.parse(text);

    } catch (error) {

      console.error( text);

      throw new Error(
        'La respuesta del servidor no tiene formato JSON.'
      );
    }

    if (!response.ok|| !result.status) {

      throw new Error(
        result.message
        || 'No fue posible cargar las unidades.'
      );
    }

    products =Array.isArray(result.data)
        ? result.data.map(
            normalizarProducto
          )
        : [];

    cargarOpcionesFiltros();

    aplicarFiltros();

  } catch (error) {

    console.error(
      error
    );

    products = [];

    filteredProducts = [];

    mostrarErrorCatalogo(error.message);
  }
}


/* ============================================================
 * CARGAR OPCIONES DINÁMICAS DE FILTROS
 * ============================================================ */

function cargarOpcionesFiltros() {

  cargarSelectUnico(
    'brandFilter',
    products.map(
      item => item.marca
    ),
    'Todas las marcas'
  );

  cargarSelectUnico(
    'modelFilter',
    products.map(
      item => item.modelo
    ),
    'Todos los modelos'
  );

  cargarFiltroAnios();
}


function cargarSelectUnico(elementId,values,defaultLabel) {

  const select =document.getElementById(elementId);

  if (!select) {
    return;
  }

  const unique =
    [...new Set(
      values
        .filter(Boolean)
        .map(
          value =>
            String(value).trim()
        )
    )]
      .sort(
        (a, b) =>
          a.localeCompare(
            b,
            'es'
          )
      );

  select.innerHTML = `

    <option value="todos">
      ${defaultLabel}
    </option>

    ${
      unique.map(
        value => `

          <option
            value="${escapeHtml(
              value.toLowerCase()
            )}">

            ${escapeHtml(value)}

          </option>

        `
      ).join('')
    }
  `;
}


function cargarFiltroAnios() {

  const years =
    [...new Set(
      products
        .map(
          item =>
            Number(item.anio)
        )
        .filter(
          year =>
            year > 0
        )
    )]
      .sort(
        (a, b) =>
          b - a
      );

  const from =document.getElementById('yearFromFilter');

  const to =document.getElementById('yearToFilter');

  const options =
    years.map(
      year =>
        `<option value="${year}">
           ${year}
         </option>`
    )
    .join('');

  if (from) {

    from.innerHTML = `

      <option value="">
        Desde
      </option>

      ${options}
    `;
  }

  if (to) {

    to.innerHTML = `

      <option value="">
        Hasta
      </option>

      ${options}
    `;
  }
}


/* ============================================================
 * FILTROS
 * ============================================================ */

function aplicarFiltros(resetPage = true) {

  if (resetPage) {

    currentPage = 1;
  }

  const search =
    (
      document
        .getElementById(
          'searchInput'
        )
        ?.value
      || ''
    )
      .trim()
      .toLowerCase();

  const marca =
    document
      .getElementById(
        'brandFilter'
      )
      ?.value
    || 'todos';

  const modelo =
    document
      .getElementById(
        'modelFilter'
      )
      ?.value
    || 'todos';

  const yearFrom =
    Number(
      document
        .getElementById(
          'yearFromFilter'
        )
        ?.value
      || 0
    );

  const yearTo =
    Number(
      document
        .getElementById(
          'yearToFilter'
        )
        ?.value
      || 0
    );

  const priceFrom =
    Number(
      document
        .getElementById(
          'priceFromFilter'
        )
        ?.value
      || 0
    );

  const priceTo =
    Number(
      document
        .getElementById(
          'priceToFilter'
        )
        ?.value
      || 0
    );

  const stock =
    document
      .getElementById(
        'stockFilter'
      )
      ?.value
    || 'todos';

  filteredProducts =
    products.filter(
      item => {

        const searchable =
          [
            item.nombre,
            item.modelo,
            item.version,
            item.marca,
            item.motor,
            item.clave_modelo,
            item.descripcion,
            item.anio
          ]
            .join(' ')
            .toLowerCase();

        if (
          search
          && !searchable.includes(
            search
          )
        ) {

          return false;
        }

        if (marca !== 'todos' && item.marca.toLowerCase() !== marca) {

          return false;
        }

        if (
          modelo !== 'todos'
          && item.modelo
            .toLowerCase()
            !== modelo
        ) {

          return false;
        }

        if (yearFrom && item.anio < yearFrom) {

          return false;
        }

        if (yearTo && item.anio > yearTo) {

          return false;
        }

        if (priceFrom && item.precio < priceFrom) {

          return false;
        }

        if (priceTo && item.precio > priceTo) {

          return false;
        }

        if (stock === 'disponible' && item.stock <= 0) {

          return false;
        }

        if (stock === 'sin_stock' && item.stock > 0) {

          return false;
        }

        return true;
      }
    );

  ordenarProductos();

  renderCatalogPage();
}


/* ============================================================
 * ORDENAMIENTO
 * ============================================================ */

function ordenarProductos() {

  const sort =
    document
      .getElementById(
        'sortProducts'
      )
      ?.value
    || 'nombre';

  filteredProducts.sort(
    (a, b) => {

      switch (sort) {

        case 'precio_asc':

          return (
            a.precio
            - b.precio
          );

        case 'precio_desc':

          return (
            b.precio
            - a.precio
          );

        case 'anio_desc':

          return (
            b.anio
            - a.anio
          );

        case 'stock_desc':

          return (
            b.stock
            - a.stock
          );

        default:

          return a.nombre.localeCompare(
            b.nombre,
            'es'
          );
      }
    }
  );
}


/* ============================================================
 * PAGINACIÓN
 * ============================================================ */

function renderCatalogPage() {

  const total =filteredProducts.length;

  const totalPages =
    Math.max(
      1,
      Math.ceil(
        total
        / PRODUCTS_PER_PAGE
      )
    );

  if (
    currentPage > totalPages
  ) {

    currentPage =
      totalPages;
  }

  const start =
    (
      currentPage - 1
    )
    * PRODUCTS_PER_PAGE;

  const end =
    start
    + PRODUCTS_PER_PAGE;

  const pageItems =
    filteredProducts.slice(
      start,
      end
    );

  renderProducts(pageItems);

  renderPagination(totalPages);

  actualizarContadorResultados(
    total,
    start,
    end
  );
}


function cambiarPagina(page) {

  const totalPages =
    Math.ceil(
      filteredProducts.length
      / PRODUCTS_PER_PAGE
    );

  if (
    page < 1
    || page > totalPages
  ) {

    return;
  }

  currentPage = page;

  renderCatalogPage();

  document
    .getElementById(
      'catalogo'
    )
    ?.scrollIntoView({
      behavior:
        'smooth',

      block:
        'start'
    });
}


function renderPagination(totalPages) {

  const container =
    document.getElementById(
      'catalogPagination'
    );

  if (!container) {
    return;
  }

  if (totalPages <= 1) {

    container.innerHTML = '';

    return;
  }

  let html = `

    <button
      type="button"
      class="pagination-btn"
      onclick="cambiarPagina(
        ${currentPage - 1}
      )"
      ${
        currentPage === 1
          ? 'disabled'
          : ''
      }>

      ‹

    </button>
  `;

  const pages =calcularPaginasVisibles(totalPages,currentPage);

  pages.forEach(
    page => {

      if (
        page === '...'
      ) {

        html += `

          <span
            class="pagination-dots">

            ...

          </span>
        `;

        return;
      }

      html += `

        <button
          type="button"
          class="
            pagination-btn
            ${
              page === currentPage
                ? 'active'
                : ''
            }
          "
          onclick="
            cambiarPagina(
              ${page}
            )
          ">

          ${page}

        </button>
      `;
    }
  );

  html += `

    <button
      type="button"
      class="pagination-btn"
      onclick="
        cambiarPagina(
          ${currentPage + 1}
        )
      "
      ${
        currentPage === totalPages
          ? 'disabled'
          : ''
      }>

      ›

    </button>
  `;

  container.innerHTML =
    html;
}


function calcularPaginasVisibles(totalPages,current) {

  if (
    totalPages <= 7
  ) {

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

  if (
    current > 4
  ) {

    pages.push(
      '...'
    );
  }

  const start =
    Math.max(
      2,
      current - 1
    );

  const end =
    Math.min(
      totalPages - 1,
      current + 1
    );

  for (
    let page = start;
    page <= end;
    page++
  ) {

    pages.push(
      page
    );
  }

  if (current < totalPages - 3) {

    pages.push('...');
  }

  pages.push(totalPages);

  return pages;
}


/* ============================================================
 * CONTADOR
 * ============================================================ */

function actualizarContadorResultados(total,start,end) {

  const results =document.getElementById('catalogResults');

  const info =document.getElementById('catalogPageInfo');

  if (results) {

    results.textContent =
      `${total} ${
        total === 1
          ? 'unidad encontrada'
          : 'unidades encontradas'
      }`;
  }

  if (info) {

    if (!total) {

      info.textContent =
        'Sin resultados disponibles';

      return;
    }

    info.textContent =
      `Mostrando ${
        start + 1
      } - ${
        Math.min(
          end,
          total
        )
      } de ${total}`;
  }
}


/* ============================================================
 * RENDER PRODUCTOS
 * ============================================================ */

function renderProducts(list) {

  const grid =document.getElementById('productsGrid');

  if (!grid) {
    return;
  }

  if (!list.length) {

    grid.innerHTML = `

      <div class="catalog-empty">

        <div class="catalog-empty-icon">
          🚘
        </div>

        <h3>
          No encontramos unidades
        </h3>

        <p>
          Intenta modificar los filtros
          de búsqueda.
        </p>

      </div>
    `;

    return;
  }

  grid.innerHTML =
    list.map(
      item => {

        const stockDisponible =
          item.stock > 0;

        return `

          <article class="product-card">

            <div class="product-image-wrapper">

              <img
                src="${item.img}"
                alt="${
                  escapeHtml(
                    item.nombre
                  )
                }"
                loading="lazy"
                onerror="
                  this.onerror=null;
                  this.src=
                    '${base_url}/Assets/images/no-image.png';
                "
              >

              <span
                class="
                  product-stock-badge
                  ${
                    stockDisponible
                      ? 'available'
                      : 'unavailable'
                  }
                ">

                ${
                  stockDisponible
                    ? `${item.stock} disponibles`
                    : 'Sin disponibilidad'
                }

              </span>

            </div>


            <div class="product-body">

              <div class="product-card-top">

                <span class="product-brand">
                  ${escapeHtml(item.marca)}
                </span>

                ${
                  item.anio
                    ? `
                      <span class="product-year">
                        ${item.anio}
                      </span>
                    `
                    : ''
                }

              </div>


              <h3>

                ${escapeHtml(item.nombre)}

              </h3>


              <span class="product-version">

                ${escapeHtml(item.version)}

              </span>


              <p>

                ${escapeHtml(item.descripcion)}

              </p>


              <div class="product-summary-data">

                <div>

                  <span>
                    Modelo
                  </span>

                  <strong>

                    ${escapeHtml(item.modelo)}

                  </strong>

                </div>


                <div>

                  <span>
                    Motor
                  </span>

                  <strong>

                    ${escapeHtml(item.motor|| 'Consultar')}

                  </strong>

                </div>

              </div>


              <div class="product-price">

                <span>
                  Precio estimado
                </span>

                <strong>

                  ${formatMoney(item.precio)}

                </strong>

              </div>


              <div class="product-actions">

                <a
                  href="${getUrlDetalleUnidad(item.id)}"
                  class="
                    btn
                    btn-outline
                    btn-small
                  ">

                  Ver detalle

                </a>


                <button
                  type="button"
                  class="
                    btn
                    btn-primary
                    btn-small
                  "
                  onclick="
                    addToCart(
                      ${item.id}
                    )
                  ">

                  Agregar

                </button>

              </div>

            </div>

          </article>
        `;

      }
    ).join('');
}


/* ============================================================
 * LIMPIAR FILTROS
 * ============================================================ */

function limpiarFiltros() {

  const ids = [

    'searchInput',

    'priceFromFilter',

    'priceToFilter'

  ];

  ids.forEach(
    id => {

      const element =
        document.getElementById(
          id
        );

      if (element) {

        element.value = '';
      }
    }
  );

  const selects = [

    'brandFilter',

    'modelFilter',

    'stockFilter'

  ];

  selects.forEach(
    id => {

      const element =
        document.getElementById(
          id
        );

      if (element) {

        element.value =
          'todos';
      }
    }
  );

  const yearFrom =document.getElementById('yearFromFilter');

  const yearTo =document.getElementById('yearToFilter');

  if (yearFrom) {

    yearFrom.value = '';
  }

  if (yearTo) {

    yearTo.value = '';
  }

  const sort =document.getElementById('sortProducts');

  if (sort) {

    sort.value =
      'nombre';
  }

  currentPage = 1;

  aplicarFiltros();
}


/* ============================================================
 * ERROR
 * ============================================================ */

function mostrarErrorCatalogo(mensaje) {

  const grid =document.getElementById('productsGrid');

  if (!grid) {
    return;
  }

  grid.innerHTML = `

    <div class="catalog-empty">

      <h3>
        No fue posible cargar el catálogo
      </h3>

      <p>

        ${escapeHtml(mensaje)}

      </p>

      <button
        type="button"
        class="btn btn-primary"
        onclick="cargarProductos()">

        Intentar nuevamente

      </button>

    </div>
  `;
}


/* ============================================================
 * MENÚ
 * ============================================================ */

function setupMenu() {

  document
    .getElementById(
      'menuToggle'
    )
    ?.addEventListener(
      'click',
      () => {

        document
          .getElementById(
            'navMenu'
          )
          ?.classList
          .toggle(
            'open'
          );
      }
    );
}


/* ============================================================
 * EVENTOS
 * ============================================================ */

function configurarEventosFiltros() {

  document.getElementById('btnApplyFilters')?.addEventListener('click',() =>
        aplicarFiltros()
    );


  document.getElementById('btnClearFilters') ?.addEventListener('click',
      limpiarFiltros
    );


  document
    .getElementById(
      'searchInput'
    )
    ?.addEventListener(
      'input',
      () =>
        aplicarFiltros()
    );


  [
    'brandFilter',

    'modelFilter',

    'yearFromFilter',

    'yearToFilter',

    'stockFilter'

  ].forEach(
    id => {

      document
        .getElementById(id)
        ?.addEventListener(
          'change',
          () =>
            aplicarFiltros()
        );
    }
  );


  document
    .getElementById(
      'sortProducts'
    )
    ?.addEventListener(
      'change',
      () => {

        currentPage = 1;

        ordenarProductos();

        renderCatalogPage();
      }
    );
}


/* ============================================================
 * INICIALIZAR
 * ============================================================ */

document.addEventListener(
  'DOMContentLoaded',
  async () => {

  
    updateCartCount();

    setupMenu();

    configurarEventosFiltros();

     mostrarAvisoCatalogoEdicion();

     inicializarModoEdicionGlobal();

    await cargarProductos();

  }
);






function mostrarAvisoCatalogoEdicion() {

    const context =getCatalogContext();

    const notice = document.getElementById('catalogEditNotice');

    if (!notice) {
        return;
    }

    notice.hidden =
        !context.esEdicion;
}






function getOrdersEditContext() {

    try {

        const data =JSON.parse(sessionStorage.getItem('orders_edit_context') || 'null' );

        if (
            !data
            || data.modo !== 'editar'
            || !data.pedido
        ) {

            return {
                activo: false,
                pedido: '',
                folio: ''
            };
        }

        return {
            activo: true,
            pedido:
                String(
                    data.pedido
                ),

            folio:
                String(
                    data.folio || ''
                )
        };

    } catch (error) {

        console.error(
            'No fue posible leer el contexto de edición:',
            error
        );

        return {
            activo: false,
            pedido: '',
            folio: ''
        };
    }
}




function salirModoEdicionPedido() {
 

    const context =getOrdersEditContext();

    // console.log(context);

    if (!context.activo) {

        window.location.href =`${base_url}/orders/home`;

        return;
    }

    if (typeof Swal !== 'undefined'&& Swal.fire) {

        Swal.fire({

            icon:'warning',

            title:'¿Salir del modo edición?',

            html:`Estás editando el pedido <strong>${escapeHtml(context.folio|| context.pedido)}</strong>.<br><br>
                Las unidades agregadas temporalmente al pedido dejarán de mostrarse en el flujo de edición hasta que vuelvas a editarlo.`,

            showCancelButton:true,

            confirmButtonText:'Sí, salir',

            cancelButtonText: 'Continuar editando',

            reverseButtons:true

        }).then(
            result => {

                if (!result.isConfirmed) {
                    return;
                }

                limpiarModoEdicionPedido();

                window.location.href =`${base_url}/orders/home`;
            }
        );

        return;
    }

    if (confirm(
            '¿Deseas salir del modo edición del pedido?'
        )
    ) {

        limpiarModoEdicionPedido();

        window.location.href =`${base_url}/orders/home`;
    }
}




function limpiarModoEdicionPedido() {

    sessionStorage.removeItem('orders_edit_context');
}





function aplicarContextoEdicionEnlaces() {

    const context =getOrdersEditContext();

    if (!context.activo) {
        return;
    }

    const links =document.querySelectorAll('a[href]');

    links.forEach(
        link => {

            const href =
                link.getAttribute(
                    'href'
                );

            if (
                !href
                || href.startsWith('#')
                || href.startsWith('mailto:')
                || href.startsWith('tel:')
                || href.includes('/orders/logout')
                || href.includes('/orders/editarpedido/')
            ) {

                return;
            }

            /*
             * Solo modificar URLs pertenecientes
             * al portal Orders.
             */
            if (!href.includes('/orders/')) {
                return;
            }

            try {

                const url =
                    new URL(
                        href,
                        window.location.origin
                    );

                url.searchParams.set(
                    'modo',
                    'editar'
                );

                url.searchParams.set(
                    'pedido',
                    context.pedido
                );

                /*
                 * Conservar hash como #catalogo.
                 */
                link.href =
                    url.toString();

            } catch (error) {

                console.error(
                    'No fue posible actualizar enlace:',
                    href,
                    error
                );
            }
        }
    );
}




function inicializarModoEdicionGlobal() {

    const context = getOrdersEditContext();

    const banner =document.getElementById('orderEditModeBanner');

    if (!context.activo) {

        if (banner) {
            banner.hidden = true;
        }

        return;
    }

    /*
     * Banner.
     */
    if (banner) {
        banner.hidden = false;
    }

    const text =document.getElementById('orderEditModeText');

    if (text) {

        text.textContent =
            `Editando ${
                context.folio
                || context.pedido
            }`;
    }


    /*
     * Regresar al pedido.
     */
    const returnButton =document.getElementById('btnReturnEditOrder');

    if (returnButton) {

        returnButton.href =`${base_url}/orders/editarpedido/${encodeURIComponent(context.pedido)}`;
    }


    /*
     * Salir.
     */
    document.getElementById('btnExitEditMode') ?.addEventListener('click',salirModoEdicionPedido);


    /*
     * Propagar contexto a enlaces.
     */
    aplicarContextoEdicionEnlaces();
}
