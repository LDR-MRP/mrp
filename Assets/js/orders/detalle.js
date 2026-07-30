'use strict';

/* ============================================================
 * DETALLE DE UNIDAD
 * ============================================================ */

document.addEventListener(
  'DOMContentLoaded',
  () => {

    /*
     * Actualiza el contador aunque por algún motivo
     * no exista información de la unidad.
     */
    actualizarContadorDetalle();

    const unidad =
      window.UNIDAD_DETALLE || null;

    if (!unidad) {
      return;
    }

    configurarSlider();
    configurarZoom();
    configurarCantidad();
    configurarAgregarCarrito(unidad);
  }
);


/* ============================================================
 * SLIDER
 * ============================================================ */

function configurarSlider() {

  const slides = Array.from(
    document.querySelectorAll(
      '.unit-slide'
    )
  );

  const thumbnails = Array.from(
    document.querySelectorAll(
      '.unit-thumbnail'
    )
  );

  const prev =
    document.getElementById(
      'unitSliderPrev'
    );

  const next =
    document.getElementById(
      'unitSliderNext'
    );

  const counter =
    document.getElementById(
      'unitSliderCounter'
    );

  const slider =
    document.getElementById(
      'unitSlider'
    );

  if (!slides.length) {
    return;
  }

  let currentIndex = 0;
  let autoplay = null;

  function mostrarSlide(index) {

    if (index < 0) {
      index = slides.length - 1;
    }

    if (index >= slides.length) {
      index = 0;
    }

    currentIndex = index;

    slides.forEach(
      (slide, slideIndex) => {

        slide.classList.toggle(
          'active',
          slideIndex === currentIndex
        );
      }
    );

    thumbnails.forEach(
      (thumbnail, thumbnailIndex) => {

        thumbnail.classList.toggle(
          'active',
          thumbnailIndex === currentIndex
        );
      }
    );

    if (counter) {
      counter.textContent =
        `${currentIndex + 1} / ${slides.length}`;
    }

    thumbnails[currentIndex]
      ?.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest',
        inline: 'center'
      });

    window.dispatchEvent(
      new CustomEvent(
        'unitSlideChanged',
        {
          detail: {
            index: currentIndex
          }
        }
      )
    );
  }

  function siguiente() {
    mostrarSlide(
      currentIndex + 1
    );
  }

  function anterior() {
    mostrarSlide(
      currentIndex - 1
    );
  }

  function iniciarAutoplay() {

    detenerAutoplay();

    if (slides.length <= 1) {
      return;
    }

    autoplay = window.setInterval(
      siguiente,
      4500
    );
  }

  function detenerAutoplay() {

    if (autoplay) {
      window.clearInterval(
        autoplay
      );

      autoplay = null;
    }
  }

  prev?.addEventListener(
    'click',
    () => {
      anterior();
      iniciarAutoplay();
    }
  );

  next?.addEventListener(
    'click',
    () => {
      siguiente();
      iniciarAutoplay();
    }
  );

  thumbnails.forEach(
    thumbnail => {

      thumbnail.addEventListener(
        'click',
        () => {

          const index =
            Number(
              thumbnail.dataset
                .slideIndex
            );

          mostrarSlide(index);

          iniciarAutoplay();
        }
      );
    }
  );

  slider?.addEventListener(
    'mouseenter',
    detenerAutoplay
  );

  slider?.addEventListener(
    'mouseleave',
    iniciarAutoplay
  );

  slider?.addEventListener(
    'focusin',
    detenerAutoplay
  );

  slider?.addEventListener(
    'focusout',
    iniciarAutoplay
  );

  document.addEventListener(
    'visibilitychange',
    () => {

      if (document.hidden) {
        detenerAutoplay();
      } else {
        iniciarAutoplay();
      }
    }
  );

  /*
   * Gestos móviles.
   */
  let touchStartX = 0;

  slider?.addEventListener(
    'touchstart',
    event => {

      touchStartX =
        event.changedTouches[0]
          .clientX;
    },
    {
      passive: true
    }
  );

  slider?.addEventListener(
    'touchend',
    event => {

      const touchEndX =
        event.changedTouches[0]
          .clientX;

      const difference =
        touchEndX - touchStartX;

      if (
        Math.abs(difference) < 45
      ) {
        return;
      }

      if (difference > 0) {
        anterior();
      } else {
        siguiente();
      }

      iniciarAutoplay();
    },
    {
      passive: true
    }
  );

  mostrarSlide(0);
  iniciarAutoplay();
}


/* ============================================================
 * ZOOM
 * ============================================================ */

function configurarZoom() {

  const modal =
    document.getElementById(
      'unitZoomModal'
    );

  const zoomImage =
    document.getElementById(
      'unitZoomImage'
    );

  const closeButton =
    document.getElementById(
      'unitZoomClose'
    );

  const prevButton =
    document.getElementById(
      'unitZoomPrev'
    );

  const nextButton =
    document.getElementById(
      'unitZoomNext'
    );

  const images = Array.from(
    document.querySelectorAll(
      '.unit-slide-image'
    )
  ).map(
    image =>
      image.dataset.fullImage
      || image.src
  );

  if (
    !modal
    || !zoomImage
    || !images.length
  ) {
    return;
  }

  let currentZoomIndex = 0;

  function mostrarImagen(index) {

    if (index < 0) {
      index = images.length - 1;
    }

    if (index >= images.length) {
      index = 0;
    }

    currentZoomIndex = index;

    zoomImage.src =
      images[currentZoomIndex];

    zoomImage.style.transform =
      'scale(1)';

    zoomImage.style.transformOrigin =
      'center center';
  }

  function abrir(index) {

    mostrarImagen(index);

    modal.classList.add(
      'active'
    );

    modal.setAttribute(
      'aria-hidden',
      'false'
    );

    document.body.classList.add(
      'zoom-modal-open'
    );

    closeButton?.focus();
  }

  function cerrar() {

    modal.classList.remove(
      'active'
    );

    modal.setAttribute(
      'aria-hidden',
      'true'
    );

    document.body.classList.remove(
      'zoom-modal-open'
    );

    zoomImage.style.transform =
      'scale(1)';
  }

  document
    .querySelectorAll(
      '[data-open-zoom]'
    )
    .forEach(
      button => {

        button.addEventListener(
          'click',
          () => {

            abrir(
              Number(
                button.dataset
                  .openZoom
              )
            );
          }
        );
      }
    );

  closeButton?.addEventListener(
    'click',
    cerrar
  );

  document
    .querySelectorAll(
      '[data-close-zoom]'
    )
    .forEach(
      element => {

        element.addEventListener(
          'click',
          cerrar
        );
      }
    );

  prevButton?.addEventListener(
    'click',
    () => {

      mostrarImagen(
        currentZoomIndex - 1
      );
    }
  );

  nextButton?.addEventListener(
    'click',
    () => {

      mostrarImagen(
        currentZoomIndex + 1
      );
    }
  );

  /*
   * Zoom siguiendo el cursor.
   */
  zoomImage.addEventListener(
    'mousemove',
    event => {

      /*
       * Solo aplicar este comportamiento
       * en dispositivos con mouse.
       */
      if (
        window.matchMedia(
          '(pointer: coarse)'
        ).matches
      ) {
        return;
      }

      const rect =
        zoomImage.getBoundingClientRect();

      const x =
        (
          (
            event.clientX
            - rect.left
          )
          / rect.width
        )
        * 100;

      const y =
        (
          (
            event.clientY
            - rect.top
          )
          / rect.height
        )
        * 100;

      zoomImage.style.transformOrigin =
        `${x}% ${y}%`;

      zoomImage.style.transform =
        'scale(1.7)';
    }
  );

  zoomImage.addEventListener(
    'mouseleave',
    () => {

      zoomImage.style.transform =
        'scale(1)';
    }
  );

  /*
   * En móvil, tocar la imagen activa
   * o desactiva el zoom.
   */
  zoomImage.addEventListener(
    'click',
    () => {

      if (
        !window.matchMedia(
          '(pointer: coarse)'
        ).matches
      ) {
        return;
      }

      const zoomed =
        zoomImage.dataset.zoomed
        === '1';

      zoomImage.dataset.zoomed =
        zoomed
          ? '0'
          : '1';

      zoomImage.style.transform =
        zoomed
          ? 'scale(1)'
          : 'scale(1.8)';
    }
  );

  document.addEventListener(
    'keydown',  
    event => {

      if (
        !modal.classList.contains(
          'active'
        )
      ) {
        return;
      }

      if (event.key === 'Escape') {
        cerrar();
      }

      if (event.key === 'ArrowLeft') {
        mostrarImagen(
          currentZoomIndex - 1
        );
      }

      if (event.key === 'ArrowRight') {
        mostrarImagen(
          currentZoomIndex + 1
        );
      }
    }
  );

  window.addEventListener(
    'unitSlideChanged',
    event => {

      currentZoomIndex =
        Number(
          event.detail.index
        );
    }
  );
}


/* ============================================================
 * CANTIDAD
 * ============================================================ */

function configurarCantidad() {

  const input =
    document.getElementById(
      'detailQuantity'
    );

  const minus =
    document.getElementById(
      'detailQuantityMinus'
    );

  const plus =
    document.getElementById(
      'detailQuantityPlus'
    );

  if (!input) {
    return;
  }

  function normalizar() {

    let quantity =
      parseInt(
        input.value,
        10
      );

    if (
      !Number.isInteger(quantity)
      || quantity < 1
    ) {
      quantity = 1;
    }

    input.value = quantity;

    if (minus) {
      minus.disabled =
        quantity <= 1;
    }

    return quantity;
  }

  minus?.addEventListener(
    'click',
    () => {

      const current =
        normalizar();

      input.value =
        Math.max(
          1,
          current - 1
        );

      normalizar();
    }
  );

  plus?.addEventListener(
    'click',
    () => {

      const current =
        normalizar();

      input.value =
        current + 1;

      normalizar();
    }
  );

  input.addEventListener(
    'change',
    normalizar
  );

  input.addEventListener(
    'input',
    () => {

      input.value =
        input.value.replace(
          /\D/g,
          ''
        );
    }
  );

  normalizar();
}























/* ============================================================
 * CARRITO
 * ============================================================ */
/* ============================================================
 * CARRITO
 * ============================================================ */

/**
 * Obtiene el ID del distribuidor autenticado.
 *
 * Esta función debe ser idéntica a la utilizada
 * dentro de home.js y carrito.js.
 */
function getPortalClientIdDetalle() {

  return Number(
    window.ordersPortal?.idcliente
    || 0
  );
}


/**
 * Genera la clave de almacenamiento correspondiente
 * al distribuidor autenticado.
 *
 * Ejemplo:
 *
 * cartAD_cliente_25
 */
function getCartStorageKeyDetalle() {

  const idcliente =
    getPortalClientIdDetalle();

  if (idcliente <= 0) {
    return null;
  }

  return `cartAD_cliente_${idcliente}`;
}


/**
 * Obtiene el carrito del distribuidor actual.
 */
function obtenerCarritoDetalle() {

  const storageKey =
    getCartStorageKeyDetalle();

  if (!storageKey) {

    console.error(
      'No fue posible leer el carrito porque no existe un distribuidor autenticado.'
    );

    return [];
  }

  try {

    const storedCart =
      localStorage.getItem(
        storageKey
      );

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
      'No fue posible leer el carrito:',
      error
    );

    return [];
  }
}


/**
 * Guarda el carrito utilizando la misma clave
 * utilizada en Home y en la vista Carrito.
 */
function guardarCarritoDetalle(cart) {

  const storageKey =
    getCartStorageKeyDetalle();

  if (!storageKey) {

    mostrarMensajeDetalle(
      'error',
      'Sesión no válida',
      'No fue posible identificar al distribuidor. Inicia sesión nuevamente.'
    );

    return false;
  }

  const carritoValido =
    Array.isArray(cart)
      ? cart
      : [];

  localStorage.setItem(
    storageKey,
    JSON.stringify(
      carritoValido
    )
  );

  actualizarContadorDetalle();

  return true;
}


/**
 * Actualiza el contador del header sumando todas
 * las cantidades registradas en el carrito.
 */
function actualizarContadorDetalle() {

  const counter =
    document.getElementById(
      'cartCount'
    );

  if (!counter) {
    return;
  }

  const totalUnidades =
    obtenerCarritoDetalle()
      .reduce(
        (total, item) => {

          const cantidad =
            Math.max(
              1,
              Number(item.qty || 1)
            );

          return total + cantidad;
        },
        0
      );

  counter.textContent =
    String(totalUnidades);
}


/**
 * Configura el botón de agregar al carrito
 * desde el detalle de la unidad.
 */
function configurarAgregarCarrito(
  unidad
) {

  const button =
    document.getElementById(
      'btnAddDetailCart'
    );

  const quantityInput =
    document.getElementById(
      'detailQuantity'
    );

  if (
    !button
    || !quantityInput
  ) {
    return;
  }

  button.addEventListener(
    'click',
    () => {

      const idcliente =
        getPortalClientIdDetalle();

      /*
       * Validar sesión antes de agregar.
       */
      if (idcliente <= 0) {

        mostrarMensajeDetalle(
          'warning',
          'Inicia sesión',
          'Debes iniciar sesión para agregar unidades al carrito.'
        ).then(() => {

          window.location.href =
            `${base_url}/orders/login`;
        });

        return;
      }

      /*
       * Validar disponibilidad.
       */
      if (
        Number(
          unidad.stock || 0
        ) <= 0
      ) {

        mostrarMensajeDetalle(
          'warning',
          'Sin disponibilidad',
          'Esta unidad no cuenta con disponibilidad actualmente.'
        );

        return;
      }

      let quantity =
        parseInt(
          quantityInput.value,
          10
        );

      if (
        !Number.isInteger(quantity)
        || quantity < 1
      ) {
        quantity = 1;
      }

      const cart =
        obtenerCarritoDetalle();

      /*
       * Buscar utilizando idunidad para evitar
       * registros duplicados por diferencias
       * entre las propiedades id e idunidad.
       */
      const existing =
        cart.find(
          item =>
            Number(
              item.idunidad
              || item.id
            )
            === Number(
              unidad.idunidad
              || unidad.id
            )
        );

      if (existing) {

        existing.qty =
          Math.max(
            1,
            Number(
              existing.qty || 1
            )
          )
          + quantity;

      } else {

        cart.push({

          id:
            Number(
              unidad.id
              || unidad.idunidad
            ),

          idunidad:
            Number(
              unidad.idunidad
              || unidad.id
            ),

          modelo:
            String(
              unidad.modelo || ''
            ),

          clave_modelo:
            String(
              unidad.clave_modelo || ''
            ),

          nombre:
            String(
              unidad.nombre || ''
            ),

          version:
            String(
              unidad.version || ''
            ),

          marca:
            String(
              unidad.marca || ''
            ),

          anio:
            Number(
              unidad.anio || 0
            ),

          motor:
            String(
              unidad.motor || ''
            ),

          stock:
            Number(
              unidad.stock || 0
            ),

          cat:
            String(
              unidad.modelo
              || 'unidad'
            ).toLowerCase(),

          precio:
            Number(
              unidad.precio
              || unidad.precio_estimado
              || 0
            ),

          precio_estimado:
            Number(
              unidad.precio_estimado
              || unidad.precio
              || 0
            ),

          img:
            String(
              unidad.img || ''
            ),

          desc:
            String(
              unidad.desc || ''
            ),

          qty:
            quantity,

          /*
           * Mantener la misma estructura
           * utilizada desde home.js.
           */
          tipo_entrega:
            '',

          idsucursal_entrega:
            null,

          direccion_entrega:
            ''
        });
      }

      const guardado =
        guardarCarritoDetalle(
          cart
        );

      if (!guardado) {
        return;
      }

      mostrarMensajeDetalle(
        'success',
        'Unidad agregada',
        `${quantity} ${
          quantity === 1
            ? 'unidad fue agregada'
            : 'unidades fueron agregadas'
        } al carrito.`,
        {
          showCancelButton:
            true,

          confirmButtonText:
            'Ver carrito',

          cancelButtonText:
            'Continuar consultando'
        }
      ).then(
        result => {

          if (
            result.isConfirmed
          ) {

            window.location.href =
              `${base_url}/orders/carrito`;
          }
        }
      );
    }
  );
}


/**
 * Mensajes utilizados en la vista detalle.
 */
function mostrarMensajeDetalle(
  icon,
  title,
  text,
  options = {}
) {

  if (
    typeof Swal !== 'undefined'
    && Swal.fire
  ) {

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

  return Promise.resolve({
    isConfirmed: false
  });
}