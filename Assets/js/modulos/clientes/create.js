"use strict";

/* ============================================================
 * MÓDULO: CLIENTES Y DISTRIBUIDORES
 * ARCHIVO: cli_clientes.js
 *
 * PARTE 1:
 * - Configuración general.
 * - Inicialización del módulo.
 * - Bloqueo y desbloqueo de pestañas.
 * - Secciones dinámicas por tipo de cliente.
 * - Generación automática del código.
 * - Validaciones generales.
 * - Guardado de información general.
 * - Validación de RFC.
 * - Funciones auxiliares comunes.
 * ============================================================ */

/* ============================================================
 * 1. CONFIGURACIÓN GENERAL
 * ============================================================ */

/**
 * Obtiene la URL base declarada globalmente por el proyecto.
 *
 * Normalmente en el layout existe algo parecido a:
 *
 * const base_url = "http://localhost/mrp";
 *
 * La función también elimina diagonales finales para evitar rutas
 * como:
 *
 * http://localhost/mrp//cli_clientes/setGeneral
 */
function obtenerBaseUrl() {
  let url = "";

  if (typeof window.base_url !== "undefined" && window.base_url) {
    url = window.base_url;
  } else if (typeof base_url !== "undefined" && base_url) {
    url = base_url;
  }

  return String(url).replace(/\/+$/, "");
}

/**
 * Endpoints utilizados por el módulo.
 *
 * Las siguientes partes agregarán el funcionamiento completo
 * de cada uno.
 */
const CLIENTES_ENDPOINTS = {
  codigoCliente: `${obtenerBaseUrl()}/cli_clientes/getCodigoCliente`,
  guardarGeneral: `${obtenerBaseUrl()}/cli_clientes/setGeneral`,
  validarRFC: `${obtenerBaseUrl()}/cli_clientes/validarRFC`,

  guardarFiscal: `${obtenerBaseUrl()}/cli_clientes/setFiscal`,

  guardarContacto: `${obtenerBaseUrl()}/cli_clientes/setContacto`,
  eliminarContacto: `${obtenerBaseUrl()}/cli_clientes/delContacto`,
  listarContactos: `${obtenerBaseUrl()}/cli_clientes/getContactos`,

  guardarSucursal: `${obtenerBaseUrl()}/cli_clientes/setSucursal`,
  eliminarSucursal: `${obtenerBaseUrl()}/cli_clientes/delSucursal`,
  listarSucursales: `${obtenerBaseUrl()}/cli_clientes/getSucursales`,

  guardarDireccion: `${obtenerBaseUrl()}/cli_clientes/setDireccion`,
  listarDirecciones: `${obtenerBaseUrl()}/cli_clientes/getDirecciones`,
  eliminarDireccion: `${obtenerBaseUrl()}/cli_clientes/delDireccion`,

  guardarComercial: `${obtenerBaseUrl()}/cli_clientes/setComercial`,

  guardarBanco: `${obtenerBaseUrl()}/cli_clientes/setBanco`,
  listarBancos: `${obtenerBaseUrl()}/cli_clientes/getBancos`,
  eliminarBanco: `${obtenerBaseUrl()}/cli_clientes/delBanco`,

  guardarDocumento: `${obtenerBaseUrl()}/cli_clientes/setDocumento`,
  listarDocumentos: `${obtenerBaseUrl()}/cli_clientes/getDocumentos`,
  eliminarDocumento: `${obtenerBaseUrl()}/cli_clientes/delDocumento`,

  obtenerGeneral: `${obtenerBaseUrl()}/cli_clientes/getGeneral`,

  obtenerFiscal: `${obtenerBaseUrl()}/cli_clientes/getFiscal`,

  obtenerComercial: `${obtenerBaseUrl()}/cli_clientes/getComercial`,
};

/**
 * Selectores principales del formulario.
 */
const SELECTORES_CLIENTE = {
  formulario: "#formCliente",
  idcliente: "#idcliente",
  tabs: "#clientTabs",
  tabGeneral: "#tab-general",
  tabFiscal: "#tab-fiscal",
  tabContactos: "#tab-contactos",
  tabSucursales: "#tab-sucursales",
  tabDirecciones: "#tab-direcciones",
  tabComercial: "#tab-comercial",
  tabBancos: "#tab-bancos",
  tabDocumentos: "#tab-documentos",
};

/**
 * Estado interno del módulo.
 */
const estadoModuloClientes = {
  idcliente: 0,
  guardandoGeneral: false,
  generandoCodigo: false,
  codigoGenerado: "",
  clienteGuardado: false,
  contadorContactos: 0,
  contadorSucursales: 0,
  cargandoGeneral: false,
};

/**
 * Pestañas que deben permanecer bloqueadas hasta que se registre
 * correctamente la información general del cliente.
 */
const PESTANAS_DEPENDIENTES = [
  "#tab-fiscal",
  "#tab-contactos",
  "#tab-sucursales",
  "#tab-direcciones",
  "#tab-comercial",
  "#tab-bancos",
  "#tab-documentos",
];

/* ============================================================
 * 2. INICIALIZACIÓN
 * ============================================================ */

document.addEventListener("DOMContentLoaded", function () {
  inicializarModuloClientes();
});

/**
 * Inicializa todos los eventos correspondientes a esta primera parte.
 */
function inicializarModuloClientes() {
  const formulario = document.querySelector(SELECTORES_CLIENTE.formulario);

  if (!formulario) {
    console.warn("No se encontró el formulario #formCliente.");

    return;
  }

  /*
   * Recuperamos el ID colocado en el campo oculto.
   *
   * Cuando el ID es mayor a cero, significa que estamos editando
   * un cliente previamente registrado.
   */
  estadoModuloClientes.idcliente = obtenerIdCliente();

  estadoModuloClientes.clienteGuardado = estadoModuloClientes.idcliente > 0;

  configurarPestanasCliente();
  configurarTipoCliente();
  configurarTipoPersona();
  configurarGeneracionCodigo();
  configurarValidacionRFC();
  configurarFormularioGeneral();
  configurarBotonLimpiar();
  configurarTransformacionesCampos();

  /*
   * Funciones agregadas en la Parte 2.
   */
  configurarSeccionFiscal();
  configurarSeccionContactos();
  configurarSeccionSucursales();
  configurarSeccionDirecciones();
  configurarSeccionComercial();
  configurarSeccionBancos();
  configurarSeccionDocumentos();

  /*
   * Al abrir el formulario, mostramos la sección dinámica
   * correspondiente al tipo de cliente actualmente seleccionado.
   */
  mostrarSeccionTipoCliente();

  /*
   * Oculta o muestra CURP dependiendo del tipo de persona.
   */
  actualizarCamposTipoPersona();

  /*
   * Si el formulario ya contiene idcliente, se interpreta como edición
   * y las pestañas pueden utilizarse inmediatamente.
   */
 if (estadoModuloClientes.clienteGuardado) {

    desbloquearPestanasCliente();

    cargarInformacionGeneralCliente();

} else {

    bloquearPestanasCliente();

}
}

/* ============================================================
 * 3. CONTROL DE PESTAÑAS
 * ============================================================ */

/**
 * Agrega protección adicional para impedir que el usuario abra
 * una pestaña dependiente antes de guardar la información general.
 */
function configurarPestanasCliente() {
  const contenedorTabs = document.querySelector(SELECTORES_CLIENTE.tabs);

  if (!contenedorTabs) {
    return;
  }

  contenedorTabs.addEventListener(
    "click",
    function (event) {
      const botonTab = event.target.closest('[data-bs-toggle="tab"]');

      if (!botonTab) {
        return;
      }

      const destino = botonTab.getAttribute("data-bs-target");

      if (PESTANAS_DEPENDIENTES.includes(destino) && obtenerIdCliente() <= 0) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();

        mostrarAdvertencia(
          "Primero debe guardar la información general del cliente.",
        );

        abrirPestana("#tab-general");
      }
    },
    true,
  );
}

/**
 * Bloquea las pestañas dependientes.
 */
function bloquearPestanasCliente() {
  PESTANAS_DEPENDIENTES.forEach(function (destino) {
    const boton = obtenerBotonTab(destino);

    if (!boton) {
      return;
    }

    boton.classList.add("disabled");
    boton.setAttribute("aria-disabled", "true");
    boton.setAttribute("tabindex", "-1");
    boton.style.pointerEvents = "none";
    boton.style.opacity = "0.55";

    /*
     * Se agrega un indicador visual de bloqueo.
     */
    if (!boton.querySelector(".tab-lock-icon")) {
      const icono = document.createElement("i");

      icono.className = "ri-lock-2-line ms-1 tab-lock-icon";

      boton.appendChild(icono);
    }
  });
}

/**
 * Habilita todas las pestañas después de guardar General.
 */
function desbloquearPestanasCliente() {
  PESTANAS_DEPENDIENTES.forEach(function (destino) {
    const boton = obtenerBotonTab(destino);

    if (!boton) {
      return;
    }

    boton.classList.remove("disabled");
    boton.removeAttribute("aria-disabled");
    boton.removeAttribute("tabindex");
    boton.style.pointerEvents = "";
    boton.style.opacity = "";

    const iconoCandado = boton.querySelector(".tab-lock-icon");

    if (iconoCandado) {
      iconoCandado.remove();
    }
  });
}

/**
 * Obtiene el botón Bootstrap que abre una pestaña determinada.
 *
 * @param {string} destino Ejemplo: #tab-fiscal
 * @returns {HTMLElement|null}
 */
function obtenerBotonTab(destino) {
  return document.querySelector(`[data-bs-target="${destino}"]`);
}

/**
 * Abre una pestaña de Bootstrap.
 *
 * @param {string} destino Ejemplo: #tab-general
 */
function abrirPestana(destino) {
  const boton = obtenerBotonTab(destino);

  if (!boton) {
    return;
  }

  if (typeof bootstrap !== "undefined" && bootstrap.Tab) {
    const instancia = bootstrap.Tab.getOrCreateInstance(boton);

    instancia.show();
  } else {
    /*
     * Respaldo para proyectos donde Bootstrap no está expuesto
     * como objeto global.
     */
    boton.click();
  }
}

/* ============================================================
 * 4. TIPO DE CLIENTE Y SECCIONES DINÁMICAS
 * ============================================================ */

/**
 * Configura el cambio del tipo de cliente.
 */
function configurarTipoCliente() {
  const selectTipoCliente = document.querySelector("#idtipo_cliente");

  if (!selectTipoCliente) {
    return;
  }

  selectTipoCliente.addEventListener("change", async function () {
    mostrarSeccionTipoCliente();

    /*
     * Cuando se selecciona un tipo, se solicita el código
     * correspondiente al servidor.
     */
    if (selectTipoCliente.value) {
      await generarCodigoCliente();
    } else {
      limpiarCodigoCliente();
    }
  });
}

/**
 * Muestra únicamente la sección dinámica correspondiente al tipo
 * de cliente seleccionado.
 *
 * También deshabilita los campos de secciones ocultas para evitar
 * que sean enviados al servidor.
 */
function mostrarSeccionTipoCliente() {
  const selectTipo = document.querySelector("#idtipo_cliente");

  const tipoSeleccionado = selectTipo
    ? String(selectTipo.value).trim().toUpperCase()
    : "";

  const secciones = {
    1: document.querySelector("#sectionDistribuidor"),

    2: document.querySelector("#sectionInterno"),

    3: document.querySelector("#sectionExterno"),

    4: document.querySelector("#sectionGubernamental"),
  };

  /*
   * Primero ocultamos y deshabilitamos todas las secciones.
   */
  Object.values(secciones).forEach(function (seccion) {
    if (!seccion) {
      return;
    }

    seccion.style.display = "none";

    seccion
      .querySelectorAll("input, select, textarea, button")
      .forEach(function (campo) {
        campo.disabled = true;

        if (campo.classList.contains("dynamic-required")) {
          campo.required = false;
        }
      });
  });

  /*
   * Mostramos y habilitamos únicamente la sección seleccionada.
   */
  const seccionActiva = secciones[tipoSeleccionado];

  if (!seccionActiva) {
    return;
  }

  seccionActiva.style.display = "block";

  seccionActiva
    .querySelectorAll("input, select, textarea, button")
    .forEach(function (campo) {
      campo.disabled = false;

      if (campo.classList.contains("dynamic-required")) {
        campo.required = true;
      }
    });
}

/* ============================================================
 * 5. GENERACIÓN DEL CÓDIGO DEL CLIENTE
 * ============================================================ */

/**
 * Configura la generación inicial cuando el formulario ya carga
 * con un tipo seleccionado pero todavía no cuenta con código.
 */
function configurarGeneracionCodigo() {
  const tipoCliente = document.querySelector("#idtipo_cliente");

  const codigoCliente = document.querySelector("#codigo_cliente");

  if (!tipoCliente || !codigoCliente) {
    return;
  }

  /*
   * En edición no se reemplaza automáticamente un código existente.
   */
  if (tipoCliente.value && !codigoCliente.value && obtenerIdCliente() <= 0) {
    generarCodigoCliente();
  }
}

/**
 * Solicita al backend el siguiente código disponible.
 */
async function generarCodigoCliente() {
  const selectTipo = document.querySelector("#idtipo_cliente");

  const inputCodigo = document.querySelector("#codigo_cliente");

  if (!selectTipo || !inputCodigo) {
    return false;
  }

  const tipoCliente = String(selectTipo.value).trim();

  if (!tipoCliente) {
    limpiarCodigoCliente();

    return false;
  }

  if (estadoModuloClientes.generandoCodigo) {
    return false;
  }

  estadoModuloClientes.generandoCodigo = true;

  const valorAnterior = inputCodigo.value;

  inputCodigo.value = "Generando...";
  inputCodigo.classList.add("text-muted");

  try {
    const url =
      `${CLIENTES_ENDPOINTS.codigoCliente}/` + encodeURIComponent(tipoCliente);

    const respuesta = await peticionJson(url, {
      method: "GET",
    });

    if (!respuesta.status) {
      throw new Error(
        respuesta.message || "No fue posible generar el código del cliente.",
      );
    }

    const codigo = respuesta.data?.codigo_cliente || "";

    if (!codigo) {
      throw new Error("El servidor no devolvió un código válido.");
    }

    inputCodigo.value = codigo;
    inputCodigo.classList.remove("text-muted");

    estadoModuloClientes.codigoGenerado = codigo;

    return true;
  } catch (error) {
    console.error("Error al generar código:", error);

    inputCodigo.value = valorAnterior;
    inputCodigo.classList.remove("text-muted");

    mostrarError(
      error.message || "No fue posible generar el código del cliente.",
    );

    return false;
  } finally {
    estadoModuloClientes.generandoCodigo = false;
  }
}

/**
 * Limpia el código generado.
 */
function limpiarCodigoCliente() {
  const inputCodigo = document.querySelector("#codigo_cliente");

  if (inputCodigo) {
    inputCodigo.value = "";
    inputCodigo.classList.remove("text-muted");
  }

  estadoModuloClientes.codigoGenerado = "";
}

/* ============================================================
 * 6. TIPO DE PERSONA
 * ============================================================ */

/**
 * Configura los cambios del campo tipo de persona.
 */
function configurarTipoPersona() {
  const selectTipoPersona = document.querySelector("#tipo_persona");

  if (!selectTipoPersona) {
    return;
  }

  selectTipoPersona.addEventListener("change", actualizarCamposTipoPersona);
}

/**
 * El campo CURP únicamente se muestra para persona física.
 */
function actualizarCamposTipoPersona() {
  const selectTipoPersona = document.querySelector("#tipo_persona");

  const camposPersonaFisica = document.querySelectorAll(
    ".persona-fisica-field",
  );

  const tipoPersona = selectTipoPersona
    ? String(selectTipoPersona.value).toUpperCase()
    : "";

  camposPersonaFisica.forEach(function (contenedor) {
    const campos = contenedor.querySelectorAll("input, select, textarea");

    if (tipoPersona === "FISICA") {
      contenedor.style.display = "";

      campos.forEach(function (campo) {
        campo.disabled = false;
      });
    } else {
      contenedor.style.display = "none";

      campos.forEach(function (campo) {
        campo.disabled = true;
        campo.value = "";
      });
    }
  });
}

/* ============================================================
 * 7. GUARDADO DE INFORMACIÓN GENERAL
 * ============================================================ */

/**
 * Intercepta el submit principal del formulario.
 *
 * En esta etapa el botón principal solamente guarda la pestaña
 * General. Las otras pestañas tendrán botones independientes.
 */
function configurarFormularioGeneral() {
  const formulario = document.querySelector(SELECTORES_CLIENTE.formulario);

  if (!formulario) {
    return;
  }

  formulario.addEventListener("submit", guardarInformacionGeneral);
}

/**
 * Guarda la información de la pestaña General.
 *
 * @param {SubmitEvent} event
 */
async function guardarInformacionGeneral(event) {
  event.preventDefault();

  if (estadoModuloClientes.guardandoGeneral) {
    return;
  }

  const seccionGeneral = document.querySelector(SELECTORES_CLIENTE.tabGeneral);

  if (!seccionGeneral) {
    mostrarError("No se encontró la sección de información general.");

    return;
  }

  /*
   * Validamos exclusivamente los campos visibles de General.
   *
   * No usamos form.checkValidity() porque el formulario también
   * contiene campos required de las demás pestañas.
   */
  if (!validarContenedor(seccionGeneral)) {
    mostrarAdvertencia(
      "Complete correctamente los campos obligatorios de la información general.",
    );

    return;
  }

  const selectTipo = document.querySelector("#idtipo_cliente");

  const inputCodigo = document.querySelector("#codigo_cliente");

  /*
   * Si es un registro nuevo y todavía no existe código, intentamos
   * generarlo antes de guardar.
   */
  if (obtenerIdCliente() <= 0 && selectTipo?.value && !inputCodigo?.value) {
    const generado = await generarCodigoCliente();

    if (!generado) {
      return;
    }
  }

  const confirmado = await confirmarAccion(
    obtenerIdCliente() > 0
      ? "¿Desea actualizar la información general del cliente?"
      : "¿Desea guardar la información general del cliente?",
    obtenerIdCliente() > 0 ? "Actualizar cliente" : "Guardar cliente",
  );

  if (!confirmado) {
    return;
  }

  estadoModuloClientes.guardandoGeneral = true;

  const botonSubmit = document.querySelector(
    `${SELECTORES_CLIENTE.formulario} button[type="submit"]`,
  );

  const contenidoOriginalBoton = botonSubmit?.innerHTML || "";

  establecerEstadoBoton(
    botonSubmit,
    true,
    obtenerIdCliente() > 0 ? "Actualizando..." : "Guardando...",
  );

  try {
    const formData = crearFormDataDesdeContenedor(seccionGeneral);

    /*
     * Se agrega el ID porque está fuera de tab-general.
     */
    formData.set("idcliente", String(obtenerIdCliente()));

    const respuesta = await peticionJson(CLIENTES_ENDPOINTS.guardarGeneral, {
      method: "POST",
      body: formData,
    });

    if (!respuesta.status) {
      throw new Error(
        respuesta.message || "No fue posible guardar la información general.",
      );
    }

    const idclienteRespuesta = Number(
      respuesta.data?.idcliente || respuesta.idcliente || obtenerIdCliente(),
    );

    if (!Number.isInteger(idclienteRespuesta) || idclienteRespuesta <= 0) {
      throw new Error("El servidor no devolvió el ID del cliente registrado.");
    }

    establecerIdCliente(idclienteRespuesta);

    estadoModuloClientes.clienteGuardado = true;

    desbloquearPestanasCliente();

    mostrarExito(
      respuesta.message || "La información general se guardó correctamente.",
    );

    /*
     * Después del primer guardado abrimos Fiscal.
     * Durante una actualización permanecemos en General.
     */
    if (
      respuesta.data?.nuevo_registro === true ||
      respuesta.data?.nuevo_registro === 1 ||
      respuesta.data?.nuevo_registro === "1"
    ) {
      abrirPestana("#tab-fiscal");
    } else if (respuesta.data?.accion === "insertar") {
      abrirPestana("#tab-fiscal");
    } else if (
      !respuesta.data?.accion &&
      estadoModuloClientes.idcliente === idclienteRespuesta
    ) {
      /*
       * Respaldo para respuestas que no indican si fue inserción.
       */
      abrirPestana("#tab-fiscal");
    }
  } catch (error) {
    console.error("Error al guardar información general:", error);

    mostrarError(error.message || "Ocurrió un error al guardar el cliente.");
  } finally {
    estadoModuloClientes.guardandoGeneral = false;

    restaurarBoton(botonSubmit, contenidoOriginalBoton);
  }
}




async function cargarInformacionGeneralCliente(forzar = false) {

    const idcliente = obtenerIdCliente();

    if (idcliente <= 0) {
        return;
    }

    if (
        estadoModuloClientes.cargandoGeneral &&
        !forzar
    ) {
        return;
    }

    estadoModuloClientes.cargandoGeneral = true;

    try {

        const respuesta = await peticionJson(

            `${CLIENTES_ENDPOINTS.obtenerGeneral}/${idcliente}`

        );

        if (!respuesta.status) {

            throw new Error(

                respuesta.message ||
                "No fue posible obtener la información del cliente."

            );

        }

        const datos = respuesta.data || {};

        Object.keys(datos).forEach(function(nombre){

            const campo =
                document.querySelector(
                    `[name="${nombre}"]`
                );

            if(!campo){
                return;
            }

            if(campo.type==="checkbox"){

                campo.checked =
                    Number(datos[nombre])===1;

                return;
            }

            campo.value =
                datos[nombre] ?? "";

        });

        mostrarSeccionTipoCliente();

        actualizarCamposTipoPersona();

    }
    catch(error){

        console.error(error);

        mostrarError(error.message);

    }
    finally{

        estadoModuloClientes.cargandoGeneral = false;

    }

}





/* ============================================================
 * 8. VALIDACIÓN DEL RFC
 * ============================================================ */

/**
 * Configura el botón y el campo RFC.
 */
function configurarValidacionRFC() {
  const inputRFC = document.querySelector("#rfc");
  const botonValidar = document.querySelector("#btnValidarRFC");

  if (inputRFC) {
    inputRFC.addEventListener("input", function () {
      inputRFC.value = normalizarTextoMayusculas(inputRFC.value);

      limpiarEstadoRFC();
    });

    inputRFC.addEventListener("blur", function () {
      if (inputRFC.value) {
        validarFormatoRFC();
      }
    });
  }

  if (botonValidar) {
    botonValidar.addEventListener("click", validarRFCCliente);
  }
}

/**
 * Valida localmente el formato del RFC según el tipo de persona.
 *
 * Persona física: 13 caracteres.
 * Persona moral: 12 caracteres.
 *
 * @returns {boolean}
 */
function validarFormatoRFC() {
  const inputRFC = document.querySelector("#rfc");
  const tipoPersona = document.querySelector("#tipo_persona");

  if (!inputRFC) {
    return false;
  }

  const rfc = normalizarTextoMayusculas(inputRFC.value);

  inputRFC.value = rfc;

  if (!rfc) {
    establecerEstadoRFC(false, "Ingrese el RFC.");

    return false;
  }

  /*
   * Expresión para persona física.
   */
  const regexFisica = /^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/;

  /*
   * Expresión para persona moral.
   */
  const regexMoral = /^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$/;

  let valido = false;

  if (tipoPersona?.value === "FISICA") {
    valido = regexFisica.test(rfc);
  } else if (tipoPersona?.value === "MORAL") {
    valido = regexMoral.test(rfc);
  } else {
    valido = regexFisica.test(rfc) || regexMoral.test(rfc);
  }

  if (!valido) {
    establecerEstadoRFC(false, "El formato del RFC no es válido.");

    inputRFC.classList.add("is-invalid");
    inputRFC.classList.remove("is-valid");

    return false;
  }

  inputRFC.classList.remove("is-invalid");

  establecerEstadoRFC(true, "El formato del RFC es correcto.");

  return true;
}

/**
 * Valida el RFC en el backend.
 *
 * El endpoint puede verificar:
 *
 * - Formato.
 * - Duplicidad.
 * - Coincidencia con otro cliente.
 */
async function validarRFCCliente() {
  const inputRFC = document.querySelector("#rfc");
  const boton = document.querySelector("#btnValidarRFC");

  if (!inputRFC) {
    return;
  }

  if (!validarFormatoRFC()) {
    inputRFC.focus();

    return;
  }

  const contenidoOriginal = boton?.innerHTML || "";

  establecerEstadoBoton(boton, true, "Validando...");

  try {
    const formData = new FormData();

    formData.append("idcliente", String(obtenerIdCliente()));

    formData.append("rfc", inputRFC.value);

    formData.append(
      "tipo_persona",
      document.querySelector("#tipo_persona")?.value || "",
    );

    const respuesta = await peticionJson(CLIENTES_ENDPOINTS.validarRFC, {
      method: "POST",
      body: formData,
    });

    if (!respuesta.status) {
      inputRFC.classList.add("is-invalid");
      inputRFC.classList.remove("is-valid");

      establecerEstadoRFC(
        false,
        respuesta.message || "El RFC no pudo ser validado.",
      );

      return;
    }

    inputRFC.classList.remove("is-invalid");
    inputRFC.classList.add("is-valid");

    establecerEstadoRFC(
      true,
      respuesta.message || "RFC disponible y validado correctamente.",
    );
  } catch (error) {
    console.error("Error al validar RFC:", error);

    inputRFC.classList.add("is-invalid");
    inputRFC.classList.remove("is-valid");

    establecerEstadoRFC(
      false,
      error.message || "No fue posible validar el RFC.",
    );
  } finally {
    restaurarBoton(boton, contenidoOriginal);
  }
}

/**
 * Muestra el resultado de la validación del RFC.
 *
 * @param {boolean} valido
 * @param {string} mensaje
 */
function establecerEstadoRFC(valido, mensaje) {
  const contenedor = document.querySelector("#rfcStatus");

  if (!contenedor) {
    return;
  }

  contenedor.className = valido
    ? "rfc-status text-success"
    : "rfc-status text-danger";

  contenedor.innerHTML = valido
    ? `<i class="ri-checkbox-circle-line me-1"></i>${escaparHtml(mensaje)}`
    : `<i class="ri-close-circle-line me-1"></i>${escaparHtml(mensaje)}`;
}

/**
 * Limpia los estilos y mensajes del RFC.
 */
function limpiarEstadoRFC() {
  const contenedor = document.querySelector("#rfcStatus");

  const inputRFC = document.querySelector("#rfc");

  if (contenedor) {
    contenedor.innerHTML = "";
    contenedor.className = "rfc-status";
  }

  if (inputRFC) {
    inputRFC.classList.remove("is-valid", "is-invalid");
  }
}

/* ============================================================
 * 9. TRANSFORMACIÓN Y RESTRICCIÓN DE CAMPOS
 * ============================================================ */

/**
 * Aplica transformaciones básicas conservando las validaciones
 * existentes del HTML.
 */
function configurarTransformacionesCampos() {
  const camposMayusculas = ["#rfc", "#curp", "#codigo_cliente"];

  camposMayusculas.forEach(function (selector) {
    const campo = document.querySelector(selector);

    if (!campo) {
      return;
    }

    campo.addEventListener("input", function () {
      campo.value = normalizarTextoMayusculas(campo.value);
    });
  });

  const camposSoloNumeros = [
    "#telefono",
    "#celular",
    "#codigo_postal_fiscal",
    "#clabe",
  ];

  camposSoloNumeros.forEach(function (selector) {
    const campo = document.querySelector(selector);

    if (!campo) {
      return;
    }

    campo.addEventListener("input", function () {
      campo.value = campo.value.replace(/\D/g, "");
    });
  });

  const inputCurp = document.querySelector("#curp");

  if (inputCurp) {
    inputCurp.addEventListener("blur", function () {
      validarCURP();
    });
  }

  const inputClabe = document.querySelector("#clabe");

  if (inputClabe) {
    inputClabe.addEventListener("blur", function () {
      validarCLABE();
    });
  }
}

/**
 * Valida el formato básico de CURP.
 *
 * @returns {boolean}
 */
function validarCURP() {
  const inputCurp = document.querySelector("#curp");

  if (!inputCurp || inputCurp.disabled || !inputCurp.value) {
    return true;
  }

  const curp = normalizarTextoMayusculas(inputCurp.value);

  inputCurp.value = curp;

  const regexCURP = /^[A-Z][AEIOU][A-Z]{2}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/;

  const valido = regexCURP.test(curp);

  inputCurp.classList.toggle("is-invalid", !valido);

  inputCurp.classList.toggle("is-valid", valido);

  return valido;
}

/**
 * Valida que la CLABE tenga exactamente 18 números.
 *
 * @returns {boolean}
 */
function validarCLABE() {
  const inputClabe = document.querySelector("#clabe");

  if (!inputClabe || !inputClabe.value) {
    return true;
  }

  const valido = /^\d{18}$/.test(inputClabe.value);

  inputClabe.classList.toggle("is-invalid", !valido);

  inputClabe.classList.toggle("is-valid", valido);

  return valido;
}

/* ============================================================
 * 10. BOTÓN LIMPIAR
 * ============================================================ */

/**
 * Configura el botón Limpiar.
 */
function configurarBotonLimpiar() {
  const botonLimpiar = document.querySelector("#btnLimpiar");

  if (!botonLimpiar) {
    return;
  }

  botonLimpiar.addEventListener("click", async function (event) {
    event.preventDefault();

    const confirmado = await confirmarAccion(
      "¿Desea limpiar la información capturada?",
      "Limpiar formulario",
    );

    if (!confirmado) {
      return;
    }

    limpiarFormularioCliente();
  });
}

/**
 * Limpia el formulario.
 *
 * Si el cliente ya fue registrado, conserva su ID para evitar que
 * los registros dependientes queden desligados accidentalmente.
 */
function limpiarFormularioCliente() {
  const formulario = document.querySelector(SELECTORES_CLIENTE.formulario);

  if (!formulario) {
    return;
  }

  const idclienteActual = obtenerIdCliente();

  formulario.reset();

  establecerIdCliente(idclienteActual);

  mostrarSeccionTipoCliente();
  actualizarCamposTipoPersona();
  limpiarEstadoRFC();

  formulario
    .querySelectorAll(".is-valid, .is-invalid")
    .forEach(function (campo) {
      campo.classList.remove("is-valid", "is-invalid");
    });

  if (idclienteActual <= 0) {
    limpiarCodigoCliente();
    bloquearPestanasCliente();
    abrirPestana("#tab-general");
  } else {
    desbloquearPestanasCliente();
  }
}

/* ============================================================
 * 11. FUNCIONES AUXILIARES COMUNES
 * ============================================================ */

/**
 * Obtiene el ID del cliente almacenado en el campo oculto.
 *
 * @returns {number}
 */
function obtenerIdCliente() {
  const inputIdCliente = document.querySelector(SELECTORES_CLIENTE.idcliente);

  const idcliente = Number(inputIdCliente?.value || 0);

  return Number.isInteger(idcliente) && idcliente > 0 ? idcliente : 0;
}

/**
 * Guarda el ID del cliente en el campo oculto y en el estado interno.
 *
 * @param {number} idcliente
 */
function establecerIdCliente(idcliente) {
  const id = Number(idcliente);

  if (!Number.isInteger(id) || id <= 0) {
    return;
  }

  const inputIdCliente = document.querySelector(SELECTORES_CLIENTE.idcliente);

  if (inputIdCliente) {
    inputIdCliente.value = String(id);
  }

  estadoModuloClientes.idcliente = id;
  estadoModuloClientes.clienteGuardado = true;
}

/**
 * Verifica que exista un cliente guardado.
 *
 * @returns {boolean}
 */
function validarClienteGuardado() {
  if (obtenerIdCliente() > 0) {
    return true;
  }

  mostrarAdvertencia(
    "Primero debe guardar la información general del cliente.",
  );

  abrirPestana("#tab-general");

  return false;
}

/**
 * Valida los campos requeridos de un contenedor.
 *
 * Solo toma en cuenta campos visibles y habilitados.
 *
 * @param {HTMLElement} contenedor
 * @returns {boolean}
 */
function validarContenedor(contenedor) {
  if (!contenedor) {
    return false;
  }

  const campos = Array.from(
    contenedor.querySelectorAll("input, select, textarea"),
  ).filter(function (campo) {
    return (
      !campo.disabled && campo.type !== "hidden" && esElementoVisible(campo)
    );
  });

  let primerCampoInvalido = null;
  let formularioValido = true;

  campos.forEach(function (campo) {
    /*
     * Limpiamos clases anteriores.
     */
    campo.classList.remove("is-valid", "is-invalid");

    const esValido = campo.checkValidity();

    if (!esValido) {
      formularioValido = false;
      campo.classList.add("is-invalid");

      if (!primerCampoInvalido) {
        primerCampoInvalido = campo;
      }
    } else if (campo.required && campo.value) {
      campo.classList.add("is-valid");
    }
  });

  if (primerCampoInvalido) {
    primerCampoInvalido.focus();

    if (typeof primerCampoInvalido.reportValidity === "function") {
      primerCampoInvalido.reportValidity();
    }
  }

  return formularioValido;
}

/**
 * Determina si un elemento se encuentra visible.
 *
 * @param {HTMLElement} elemento
 * @returns {boolean}
 */
function esElementoVisible(elemento) {
  if (!elemento) {
    return false;
  }

  return Boolean(
    elemento.offsetWidth ||
    elemento.offsetHeight ||
    elemento.getClientRects().length,
  );
}

/**
 * Genera un FormData utilizando únicamente campos habilitados
 * pertenecientes a un contenedor.
 *
 * @param {HTMLElement} contenedor
 * @returns {FormData}
 */
function crearFormDataDesdeContenedor(contenedor) {
  const formData = new FormData();

  if (!contenedor) {
    return formData;
  }

  const campos = contenedor.querySelectorAll("input, select, textarea");

  campos.forEach(function (campo) {
    if (campo.disabled || !campo.name) {
      return;
    }

    if (
      (campo.type === "checkbox" || campo.type === "radio") &&
      !campo.checked
    ) {
      return;
    }

    if (campo.type === "file") {
      Array.from(campo.files || []).forEach(function (archivo) {
        formData.append(campo.name, archivo);
      });

      return;
    }

    formData.append(campo.name, campo.value);
  });

  return formData;
}

/**
 * Realiza una petición y exige una respuesta JSON válida.
 *
 * @param {string} url
 * @param {RequestInit} opciones
 * @returns {Promise<object>}
 */
async function peticionJson(url, opciones = {}) {
  const configuracion = {
    method: "GET",
    credentials: "same-origin",
    headers: {
      "X-Requested-With": "XMLHttpRequest",
    },
    ...opciones,
  };

  /*
   * No establecemos Content-Type cuando el body es FormData.
   * El navegador debe crear automáticamente el boundary.
   */
  if (configuracion.body instanceof FormData && configuracion.headers) {
    delete configuracion.headers["Content-Type"];
  }

  let respuestaHttp;

  try {
    respuestaHttp = await fetch(url, configuracion);
  } catch (error) {
    throw new Error("No fue posible establecer comunicación con el servidor.");
  }

  const textoRespuesta = await respuestaHttp.text();

  let respuestaJson;

  try {
    respuestaJson = textoRespuesta ? JSON.parse(textoRespuesta) : {};
  } catch (error) {
    console.error("Respuesta no JSON:", textoRespuesta);

    throw new Error("La respuesta del servidor no tiene formato JSON.");
  }

  if (!respuestaHttp.ok) {
    throw new Error(
      respuestaJson.message || `Error HTTP ${respuestaHttp.status}.`,
    );
  }

  return respuestaJson;
}

/**
 * Activa o desactiva un botón durante una petición.
 *
 * @param {HTMLElement|null} boton
 * @param {boolean} cargando
 * @param {string} texto
 */
function establecerEstadoBoton(boton, cargando, texto = "Procesando...") {
  if (!boton) {
    return;
  }

  boton.disabled = cargando;

  if (cargando) {
    boton.innerHTML = `
            <span
                class="spinner-border spinner-border-sm me-1"
                role="status"
                aria-hidden="true">
            </span>
            ${escaparHtml(texto)}
        `;
  }
}

/**
 * Restaura el contenido original de un botón.
 *
 * @param {HTMLElement|null} boton
 * @param {string} contenidoOriginal
 */
function restaurarBoton(boton, contenidoOriginal) {
  if (!boton) {
    return;
  }

  boton.disabled = false;
  boton.innerHTML = contenidoOriginal;
}

/**
 * Convierte texto a mayúsculas y elimina espacios laterales.
 *
 * @param {string} valor
 * @returns {string}
 */
function normalizarTextoMayusculas(valor) {
  return String(valor || "")
    .toLocaleUpperCase("es-MX")
    .trimStart();
}

/**
 * Escapa contenido antes de colocarlo con innerHTML.
 *
 * @param {*} valor
 * @returns {string}
 */
function escaparHtml(valor) {
  const elemento = document.createElement("div");

  elemento.textContent = String(valor ?? "");

  return elemento.innerHTML;
}

/**
 * Muestra una alerta de éxito.
 *
 * @param {string} mensaje
 */
function mostrarExito(mensaje) {
  if (typeof Swal !== "undefined" && Swal.fire) {
    Swal.fire({
      icon: "success",
      title: "Proceso correcto",
      text: mensaje,
      confirmButtonText: "Aceptar",
    });

    return;
  }

  alert(mensaje);
}

/**
 * Muestra una alerta de error.
 *
 * @param {string} mensaje
 */
function mostrarError(mensaje) {
  if (typeof Swal !== "undefined" && Swal.fire) {
    Swal.fire({
      icon: "error",
      title: "Ocurrió un error",
      text: mensaje,
      confirmButtonText: "Aceptar",
    });

    return;
  }

  alert(mensaje);
}

/**
 * Muestra una advertencia.
 *
 * @param {string} mensaje
 */
function mostrarAdvertencia(mensaje) {
  if (typeof Swal !== "undefined" && Swal.fire) {
    Swal.fire({
      icon: "warning",
      title: "Atención",
      text: mensaje,
      confirmButtonText: "Aceptar",
    });

    return;
  }

  alert(mensaje);
}

/**
 * Solicita confirmación antes de realizar una acción.
 *
 * @param {string} mensaje
 * @param {string} textoConfirmar
 * @returns {Promise<boolean>}
 */
async function confirmarAccion(mensaje, textoConfirmar = "Confirmar") {
  if (typeof Swal !== "undefined" && Swal.fire) {
    const resultado = await Swal.fire({
      icon: "question",
      title: "Confirmar operación",
      text: mensaje,
      showCancelButton: true,
      confirmButtonText: textoConfirmar,
      cancelButtonText: "Cancelar",
      reverseButtons: true,
    });

    return resultado.isConfirmed;
  }

  return window.confirm(mensaje);
}

/* ============================================================
 * PARTE 2
 *
 * - Guardado de información fiscal.
 * - Validaciones fiscales.
 * - Creación dinámica de contactos.
 * - Guardado individual de contactos.
 * - Actualización de contactos.
 * - Eliminación de contactos.
 * - Carga de contactos registrados.
 * - Contador de contactos en la pestaña.
 * ============================================================ */

/* ============================================================
 * 12. INFORMACIÓN FISCAL
 * ============================================================ */

/**
 * Estado interno de la sección fiscal.
 */
const estadoFiscalCliente = {
  guardando: false,
  botonCreado: false,
  informacionGuardada: false,

  cargando: false,
  cargado: false,
};

/**
 * Inicializa la sección de información fiscal.
 *
 * Esta función:
 *
 * - Crea el botón Guardar información fiscal.
 * - Configura el evento del botón.
 * - Valida RFC, CURP y código postal.
 * - Controla si el cliente requiere factura.
 */
function configurarSeccionFiscal() {
  const contenedorFiscal = document.querySelector(SELECTORES_CLIENTE.tabFiscal);

  if (!contenedorFiscal) {
    return;
  }

  crearBotonGuardarFiscal();
  configurarCamposFiscales();

  const botonTab = obtenerBotonTab("#tab-fiscal");

  if (botonTab) {
    botonTab.addEventListener("shown.bs.tab", function () {
      if (validarClienteGuardado()) {
        cargarInformacionFiscalCliente();
      }
    });
  }
}

/**
 * Agrega el botón para guardar la información fiscal.
 *
 * El HTML original no contiene un botón exclusivo para Fiscal,
 * por lo que se genera dinámicamente al cargar el módulo.
 */
function crearBotonGuardarFiscal() {
  const contenedorFiscal = document.querySelector(SELECTORES_CLIENTE.tabFiscal);

  if (!contenedorFiscal || estadoFiscalCliente.botonCreado) {
    return;
  }

  /*
   * Evita crear dos veces el botón si la función se ejecuta
   * nuevamente.
   */
  const botonExistente = document.querySelector("#btnGuardarFiscal");

  if (botonExistente) {
    botonExistente.addEventListener("click", guardarInformacionFiscal);

    estadoFiscalCliente.botonCreado = true;

    return;
  }

  const contenedorAcciones = document.createElement("div");

  contenedorAcciones.className = "d-flex justify-content-end gap-2 mt-4";

  contenedorAcciones.innerHTML = `
        <button
            type="button"
            class="btn btn-primary btn-label"
            id="btnGuardarFiscal">

            <i
                class="ri-save-3-line label-icon align-middle fs-16 me-2">
            </i>

            Guardar información fiscal
        </button>
    `;

  contenedorFiscal.appendChild(contenedorAcciones);

  const botonGuardar = document.querySelector("#btnGuardarFiscal");

  botonGuardar?.addEventListener("click", guardarInformacionFiscal);

  estadoFiscalCliente.botonCreado = true;
}

/**
 * Configura las validaciones y cambios de los campos fiscales.
 */
function configurarCamposFiscales() {
  const inputRFC = document.querySelector("#rfc");
  const inputCURP = document.querySelector("#curp");
  const inputCodigoPostal = document.querySelector("#codigo_postal_fiscal");

  const selectRequiereFactura = document.querySelector("#requiere_factura");

  const selectRegimenFiscal = document.querySelector("#regimen_fiscal");

  const selectUsoCFDI = document.querySelector("#uso_cfdi");

  if (inputRFC) {
    inputRFC.addEventListener("input", function () {
      inputRFC.value = normalizarTextoMayusculas(inputRFC.value);
    });
  }

  if (inputCURP) {
    inputCURP.addEventListener("input", function () {
      inputCURP.value = normalizarTextoMayusculas(inputCURP.value);
    });
  }

  if (inputCodigoPostal) {
    inputCodigoPostal.addEventListener("input", function () {
      inputCodigoPostal.value = inputCodigoPostal.value
        .replace(/\D/g, "")
        .slice(0, 5);
    });
  }

  if (selectRequiereFactura) {
    selectRequiereFactura.addEventListener(
      "change",
      actualizarRequerimientosFiscales,
    );
  }

  if (selectRegimenFiscal) {
    selectRegimenFiscal.addEventListener("change", validarRegimenFiscalPersona);
  }

  if (selectUsoCFDI) {
    selectUsoCFDI.addEventListener("change", function () {
      selectUsoCFDI.classList.remove("is-invalid");
    });
  }

  actualizarRequerimientosFiscales();
}

/**
 * Cambia la obligatoriedad de ciertos campos según si el cliente
 * requiere factura.
 */
function actualizarRequerimientosFiscales() {
  const selectRequiereFactura = document.querySelector("#requiere_factura");

  const requiereFactura = String(selectRequiereFactura?.value || "0") === "1";

  const camposObligatorios = [
    document.querySelector("#rfc"),
    document.querySelector("#regimen_fiscal"),
    document.querySelector("#uso_cfdi"),
    document.querySelector("#codigo_postal_fiscal"),
  ];

  camposObligatorios.forEach(function (campo) {
    if (!campo) {
      return;
    }

    campo.required = requiereFactura;

    const label = document.querySelector(`label[for="${campo.id}"]`);

    if (label) {
      label.classList.toggle("required", requiereFactura);
    }
  });
}

/**
 * Valida si el régimen fiscal tiene sentido para el tipo de
 * persona seleccionado.
 *
 * Esta validación es orientativa. El backend debe realizar la
 * validación definitiva.
 *
 * @returns {boolean}
 */
function validarRegimenFiscalPersona() {
  const tipoPersona = String(
    document.querySelector("#tipo_persona")?.value || "",
  ).toUpperCase();

  const selectRegimen = document.querySelector("#regimen_fiscal");

  if (!selectRegimen || !selectRegimen.value) {
    return true;
  }

  const regimen = String(selectRegimen.value);

  /*
   * Regímenes normalmente asociados con personas morales.
   */
  const regimenesMorales = ["601", "603"];

  /*
   * Regímenes normalmente asociados con personas físicas.
   */
  const regimenesFisicas = ["605", "606", "612", "616", "621", "626"];

  let valido = true;
  let mensaje = "";

  if (tipoPersona === "FISICA" && regimenesMorales.includes(regimen)) {
    valido = false;
    mensaje =
      "El régimen seleccionado normalmente corresponde a una persona moral.";
  }

  if (tipoPersona === "MORAL" && regimenesFisicas.includes(regimen)) {
    valido = false;
    mensaje =
      "El régimen seleccionado normalmente corresponde a una persona física.";
  }

  selectRegimen.classList.toggle("is-invalid", !valido);

  if (!valido) {
    mostrarAdvertencia(mensaje);
  } else {
    selectRegimen.classList.remove("is-invalid");
  }

  return valido;
}

/**
 * Valida los datos fiscales antes de enviarlos al servidor.
 *
 * @returns {boolean}
 */
function validarDatosFiscales() {
  const contenedorFiscal = document.querySelector(SELECTORES_CLIENTE.tabFiscal);

  if (!contenedorFiscal) {
    return false;
  }

  actualizarRequerimientosFiscales();

  if (!validarContenedor(contenedorFiscal)) {
    return false;
  }

  const requiereFactura =
    document.querySelector("#requiere_factura")?.value === "1";

  /*
   * Si no requiere factura, los campos fiscales pueden estar
   * vacíos. Si están capturados, se validan.
   */
  const inputRFC = document.querySelector("#rfc");

  if (requiereFactura || inputRFC?.value) {
    if (!validarFormatoRFC()) {
      inputRFC?.focus();

      return false;
    }
  }

  const tipoPersona = document.querySelector("#tipo_persona")?.value;

  const inputCURP = document.querySelector("#curp");

  if (tipoPersona === "FISICA" && inputCURP?.value && !validarCURP()) {
    mostrarAdvertencia("La CURP capturada no tiene un formato válido.");

    inputCURP.focus();

    return false;
  }

  const inputCodigoPostal = document.querySelector("#codigo_postal_fiscal");

  if (inputCodigoPostal?.value && !/^\d{5}$/.test(inputCodigoPostal.value)) {
    inputCodigoPostal.classList.add("is-invalid");

    mostrarAdvertencia(
      "El código postal fiscal debe contener exactamente 5 números.",
    );

    inputCodigoPostal.focus();

    return false;
  }

  if (!validarRegimenFiscalPersona()) {
    return false;
  }

  return true;
}

/**
 * Guarda o actualiza la información fiscal del cliente.
 */
async function guardarInformacionFiscal() {
  if (!validarClienteGuardado()) {
    return;
  }

  if (estadoFiscalCliente.guardando) {
    return;
  }

  if (!validarDatosFiscales()) {
    mostrarAdvertencia("Revise los datos fiscales capturados.");

    return;
  }

  const confirmado = await confirmarAccion(
    "¿Desea guardar la información fiscal del cliente?",
    "Guardar información fiscal",
  );

  if (!confirmado) {
    return;
  }

  const boton = document.querySelector("#btnGuardarFiscal");

  const contenidoOriginal = boton?.innerHTML || "";

  estadoFiscalCliente.guardando = true;

  establecerEstadoBoton(boton, true, "Guardando...");

  try {
    const contenedorFiscal = document.querySelector(
      SELECTORES_CLIENTE.tabFiscal,
    );

    const formData = crearFormDataDesdeContenedor(contenedorFiscal);

    formData.set("idcliente", String(obtenerIdCliente()));

    /*
     * El tipo de persona se encuentra en General, pero puede
     * ser requerido por el endpoint fiscal.
     */
    formData.set(
      "tipo_persona",
      document.querySelector("#tipo_persona")?.value || "",
    );

    const respuesta = await peticionJson(CLIENTES_ENDPOINTS.guardarFiscal, {
      method: "POST",
      body: formData,
    });

    if (!respuesta.status) {
      throw new Error(
        respuesta.message || "No fue posible guardar la información fiscal.",
      );
    }

    estadoFiscalCliente.informacionGuardada = true;

    mostrarExito(
      respuesta.message || "La información fiscal se guardó correctamente.",
    );

    /*
     * Después del guardado se abre Contactos para continuar
     * con la captura.
     */
    abrirPestana("#tab-contactos");
  } catch (error) {
    console.error("Error al guardar información fiscal:", error);

    mostrarError(
      error.message || "Ocurrió un error al guardar los datos fiscales.",
    );
  } finally {
    estadoFiscalCliente.guardando = false;

    restaurarBoton(boton, contenidoOriginal);
  }
}

/* ============================================================
 * 13. CONTACTOS
 * ============================================================ */

/**
 * Estado interno de los contactos.
 */
const estadoContactosCliente = {
  cargando: false,
  inicializado: false,
  registros: new Map(),
};

/**
 * Inicializa todos los eventos de la sección Contactos.
 */
function configurarSeccionContactos() {
  const contenedorContactos = document.querySelector(
    SELECTORES_CLIENTE.tabContactos,
  );

  if (!contenedorContactos || estadoContactosCliente.inicializado) {
    return;
  }

  const botonAgregar = document.querySelector("#btnAgregarContacto");

  if (botonAgregar) {
    botonAgregar.addEventListener("click", agregarFilaContacto);
  }

  /*
   * Delegación de eventos para botones creados dinámicamente.
   */
  const tbody = document.querySelector("#tbodyContactos");

  if (tbody) {
    tbody.addEventListener("click", manejarAccionesContacto);

    tbody.addEventListener("input", manejarCambioContacto);

    tbody.addEventListener("change", manejarCambioContacto);
  }

  /*
   * Cuando se abre la pestaña, se consultan los registros
   * guardados del cliente.
   */
  const botonTabContactos = obtenerBotonTab("#tab-contactos");

  if (botonTabContactos) {
    botonTabContactos.addEventListener("shown.bs.tab", function () {
      if (validarClienteGuardado()) {
        cargarContactosCliente();
      }
    });
  }

  /*
   * Si estamos editando un cliente existente, podemos cargar los
   * contactos desde el inicio.
   */


  actualizarEstadoVacioContactos();

  estadoContactosCliente.inicializado = true;
}

/**
 * Crea una nueva fila para capturar un contacto.
 */
function agregarFilaContacto() {
  if (!validarClienteGuardado()) {
    return;
  }

  estadoModuloClientes.contadorContactos += 1;

  const identificadorTemporal =
    `nuevo-${Date.now()}-` + estadoModuloClientes.contadorContactos;

  const contacto = {
    idcontacto: 0,
    identificadorTemporal: identificadorTemporal,
    nombre: "",
    puesto: "",
    correo: "",
    telefono: "",
    tipo_contacto: "COMERCIAL",
    notificar: "1",
    nuevo: true,
  };

  const fila = construirFilaContacto(contacto);

  const tbody = document.querySelector("#tbodyContactos");

  if (!tbody) {
    return;
  }

  eliminarFilaVaciaContactos();

  tbody.appendChild(fila);

  estadoContactosCliente.registros.set(identificadorTemporal, contacto);

  actualizarContadorContactos();

  const inputNombre = fila.querySelector('[name="contacto_nombre"]');

  inputNombre?.focus();
}

/**
 * Construye el elemento TR correspondiente a un contacto.
 *
 * @param {object} contacto
 * @returns {HTMLTableRowElement}
 */
function construirFilaContacto(contacto) {
  const fila = document.createElement("tr");

  const identificador =
    contacto.idcontacto > 0
      ? String(contacto.idcontacto)
      : contacto.identificadorTemporal;

  fila.dataset.contactoId = identificador;
  fila.dataset.guardado = contacto.idcontacto > 0 ? "1" : "0";

  fila.innerHTML = `
        <td style="min-width: 190px;">
            <input
                type="hidden"
                name="idcontacto"
                value="${Number(contacto.idcontacto || 0)}">

            <input
                type="text"
                class="form-control form-control-sm"
                name="contacto_nombre"
                value="${escaparAtributo(contacto.nombre)}"
                placeholder="Nombre completo"
                maxlength="150"
                required>
        </td>

        <td style="min-width: 150px;">
            <input
                type="text"
                class="form-control form-control-sm"
                name="contacto_puesto"
                value="${escaparAtributo(contacto.puesto)}"
                placeholder="Puesto"
                maxlength="100">
        </td>

        <td style="min-width: 210px;">
            <input
                type="email"
                class="form-control form-control-sm"
                name="contacto_correo"
                value="${escaparAtributo(contacto.correo)}"
                placeholder="correo@empresa.com"
                maxlength="150"
                required>
        </td>

        <td style="min-width: 150px;">
            <input
                type="tel"
                class="form-control form-control-sm"
                name="contacto_telefono"
                value="${escaparAtributo(contacto.telefono)}"
                placeholder="7221234567"
                maxlength="15">
        </td>

        <td style="min-width: 150px;">
            <select
                class="form-select form-select-sm"
                name="tipo_contacto"
                required>

                <option
                    value="ADMINISTRATIVO"
                    ${
                      contacto.tipo_contacto === "ADMINISTRATIVO"
                        ? "selected"
                        : ""
                    }>
                    Administrativo
                </option>

                <option
                    value="COMERCIAL"
                    ${contacto.tipo_contacto === "COMERCIAL" ? "selected" : ""}>
                    Comercial
                </option>

                <option
                    value="FACTURACION"
                    ${
                      contacto.tipo_contacto === "FACTURACION" ? "selected" : ""
                    }>
                    Facturación
                </option>

                <option
                    value="COBRANZA"
                    ${contacto.tipo_contacto === "COBRANZA" ? "selected" : ""}>
                    Cobranza
                </option>

                <option
                    value="ENTREGAS"
                    ${contacto.tipo_contacto === "ENTREGAS" ? "selected" : ""}>
                    Entregas
                </option>

                <option
                    value="OTRO"
                    ${contacto.tipo_contacto === "OTRO" ? "selected" : ""}>
                    Otro
                </option>
            </select>
        </td>

        <td class="text-center">
            <div class="form-check form-switch d-inline-block">
                <input
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    name="contacto_notificar"
                    value="1"
                    ${String(contacto.notificar) === "1" ? "checked" : ""}>
            </div>
        </td>

        <td class="text-end text-nowrap">
            <button
                type="button"
                class="btn btn-sm btn-soft-success me-1 btn-guardar-contacto"
                title="Guardar contacto">

                <i class="ri-save-3-line"></i>
            </button>

            <button
                type="button"
                class="btn btn-sm btn-soft-danger btn-eliminar-contacto"
                title="Eliminar contacto">

                <i class="ri-delete-bin-6-line"></i>
            </button>
        </td>
    `;

  /*
   * Limita el teléfono a caracteres válidos.
   */
  const inputTelefono = fila.querySelector('[name="contacto_telefono"]');

  inputTelefono?.addEventListener("input", function () {
    inputTelefono.value = inputTelefono.value.replace(/[^\d+\s()-]/g, "");
  });

  return fila;
}

/**
 * Maneja los botones Guardar y Eliminar de los contactos.
 *
 * @param {MouseEvent} event
 */
function manejarAccionesContacto(event) {
  const botonGuardar = event.target.closest(".btn-guardar-contacto");

  if (botonGuardar) {
    const fila = botonGuardar.closest("tr");

    guardarContacto(fila);

    return;
  }

  const botonEliminar = event.target.closest(".btn-eliminar-contacto");

  if (botonEliminar) {
    const fila = botonEliminar.closest("tr");

    eliminarContacto(fila);
  }
}

/**
 * Marca visualmente la fila cuando el usuario cambia información.
 *
 * @param {Event} event
 */
function manejarCambioContacto(event) {
  const fila = event.target.closest("tr");

  if (!fila) {
    return;
  }

  fila.dataset.modificado = "1";

  fila.classList.add("table-warning");

  const botonGuardar = fila.querySelector(".btn-guardar-contacto");

  if (botonGuardar) {
    botonGuardar.title = "Guardar cambios del contacto";
  }
}

/**
 * Obtiene los datos contenidos en una fila de contacto.
 *
 * @param {HTMLTableRowElement} fila
 * @returns {object}
 */
function obtenerDatosFilaContacto(fila) {
  const obtenerValor = function (nombre) {
    return fila.querySelector(`[name="${nombre}"]`)?.value?.trim() || "";
  };

  return {
    idcontacto: Number(obtenerValor("idcontacto") || 0),

    nombre: obtenerValor("contacto_nombre"),

    puesto: obtenerValor("contacto_puesto"),

    correo: obtenerValor("contacto_correo").toLowerCase(),

    telefono: obtenerValor("contacto_telefono"),

    tipo_contacto: obtenerValor("tipo_contacto"),

    notificar: fila.querySelector('[name="contacto_notificar"]')?.checked
      ? "1"
      : "0",
  };
}

/**
 * Valida una fila de contacto.
 *
 * @param {HTMLTableRowElement} fila
 * @returns {boolean}
 */
function validarFilaContacto(fila) {
  if (!fila) {
    return false;
  }

  const campos = fila.querySelectorAll('input:not([type="hidden"]), select');

  let valido = true;
  let primerInvalido = null;

  campos.forEach(function (campo) {
    campo.classList.remove("is-invalid", "is-valid");

    if (!campo.checkValidity()) {
      campo.classList.add("is-invalid");
      valido = false;

      if (!primerInvalido) {
        primerInvalido = campo;
      }
    } else if (campo.required && campo.value) {
      campo.classList.add("is-valid");
    }
  });

  const datos = obtenerDatosFilaContacto(fila);

  if (datos.correo && !validarCorreoElectronico(datos.correo)) {
    const inputCorreo = fila.querySelector('[name="contacto_correo"]');

    inputCorreo?.classList.add("is-invalid");

    valido = false;

    if (!primerInvalido) {
      primerInvalido = inputCorreo;
    }
  }

  if (datos.telefono && datos.telefono.replace(/\D/g, "").length < 10) {
    const inputTelefono = fila.querySelector('[name="contacto_telefono"]');

    inputTelefono?.classList.add("is-invalid");

    valido = false;

    if (!primerInvalido) {
      primerInvalido = inputTelefono;
    }
  }

  primerInvalido?.focus();

  return valido;
}

/**
 * Guarda o actualiza un contacto individual.
 *
 * @param {HTMLTableRowElement} fila
 */
async function guardarContacto(fila) {
  if (!validarClienteGuardado()) {
    return;
  }

  if (!fila || fila.dataset.guardando === "1") {
    return;
  }

  if (!validarFilaContacto(fila)) {
    mostrarAdvertencia(
      "Complete correctamente los datos obligatorios del contacto.",
    );

    return;
  }

  const datos = obtenerDatosFilaContacto(fila);

  const confirmado = await confirmarAccion(
    datos.idcontacto > 0
      ? "¿Desea actualizar este contacto?"
      : "¿Desea guardar este contacto?",
    datos.idcontacto > 0 ? "Actualizar contacto" : "Guardar contacto",
  );

  if (!confirmado) {
    return;
  }

  const botonGuardar = fila.querySelector(".btn-guardar-contacto");

  const contenidoOriginal = botonGuardar?.innerHTML || "";

  fila.dataset.guardando = "1";

  establecerEstadoBoton(botonGuardar, true, "");

  try {
    const formData = new FormData();

    formData.append("idcliente", String(obtenerIdCliente()));

    formData.append("idcontacto", String(datos.idcontacto));

    formData.append("nombre", datos.nombre);

    formData.append("puesto", datos.puesto);

    formData.append("correo", datos.correo);

    formData.append("telefono", datos.telefono);

    formData.append("tipo_contacto", datos.tipo_contacto);

    formData.append("notificar", datos.notificar);

    const respuesta = await peticionJson(CLIENTES_ENDPOINTS.guardarContacto, {
      method: "POST",
      body: formData,
    });

    if (!respuesta.status) {
      throw new Error(
        respuesta.message || "No fue posible guardar el contacto.",
      );
    }

    const idcontacto = Number(
      respuesta.data?.idcontacto || respuesta.idcontacto || datos.idcontacto,
    );

    if (!Number.isInteger(idcontacto) || idcontacto <= 0) {
      throw new Error("El servidor no devolvió un ID de contacto válido.");
    }

    const inputIdContacto = fila.querySelector('[name="idcontacto"]');

    if (inputIdContacto) {
      inputIdContacto.value = String(idcontacto);
    }

    const identificadorAnterior = fila.dataset.contactoId;

    fila.dataset.contactoId = String(idcontacto);

    fila.dataset.guardado = "1";
    fila.dataset.modificado = "0";

    fila.classList.remove("table-warning", "table-danger");

    fila.classList.add("table-success");

    setTimeout(function () {
      fila.classList.remove("table-success");
    }, 1200);

    estadoContactosCliente.registros.delete(identificadorAnterior);

    estadoContactosCliente.registros.set(String(idcontacto), {
      ...datos,
      idcontacto: idcontacto,
      nuevo: false,
    });

    actualizarContadorContactos();

    mostrarExito(respuesta.message || "El contacto se guardó correctamente.");
  } catch (error) {
    console.error("Error al guardar contacto:", error);

    fila.classList.add("table-danger");

    mostrarError(error.message || "Ocurrió un error al guardar el contacto.");
  } finally {
    fila.dataset.guardando = "0";

    restaurarBoton(botonGuardar, contenidoOriginal);
  }
}

/**
 * Elimina un contacto.
 *
 * Si el contacto todavía no está guardado, solamente elimina
 * la fila del HTML.
 *
 * @param {HTMLTableRowElement} fila
 */
async function eliminarContacto(fila) {
  if (!fila) {
    return;
  }

  const datos = obtenerDatosFilaContacto(fila);

  /*
   * El contacto aún no existe en la base de datos.
   */
  if (datos.idcontacto <= 0) {
    const confirmado = await confirmarAccion(
      "¿Desea quitar este contacto sin guardar?",
      "Quitar contacto",
    );

    if (!confirmado) {
      return;
    }

    estadoContactosCliente.registros.delete(fila.dataset.contactoId);

    fila.remove();

    actualizarEstadoVacioContactos();
    actualizarContadorContactos();

    return;
  }

  const confirmado = await confirmarAccion(
    `¿Desea eliminar el contacto "${datos.nombre}"?`,
    "Eliminar contacto",
  );

  if (!confirmado) {
    return;
  }

  const botonEliminar = fila.querySelector(".btn-eliminar-contacto");

  const contenidoOriginal = botonEliminar?.innerHTML || "";

  establecerEstadoBoton(botonEliminar, true, "");

  try {
    const formData = new FormData();

    formData.append("idcliente", String(obtenerIdCliente()));

    formData.append("idcontacto", String(datos.idcontacto));

    const respuesta = await peticionJson(CLIENTES_ENDPOINTS.eliminarContacto, {
      method: "POST",
      body: formData,
    });

    if (!respuesta.status) {
      throw new Error(
        respuesta.message || "No fue posible eliminar el contacto.",
      );
    }

    estadoContactosCliente.registros.delete(String(datos.idcontacto));

    fila.remove();

    actualizarEstadoVacioContactos();
    actualizarContadorContactos();

    mostrarExito(respuesta.message || "El contacto se eliminó correctamente.");
  } catch (error) {
    console.error("Error al eliminar contacto:", error);

    mostrarError(error.message || "Ocurrió un error al eliminar el contacto.");

    restaurarBoton(botonEliminar, contenidoOriginal);
  }
}

async function cargarInformacionFiscalCliente(forzar = false) {
  const idcliente = obtenerIdCliente();

  if (idcliente <= 0) {
    return;
  }

  if (estadoFiscalCliente.cargando) {
    return;
  }

  if (estadoFiscalCliente.cargado && !forzar) {
    return;
  }

  estadoFiscalCliente.cargando = true;

  try {
    const respuesta = await peticionJson(
      `${CLIENTES_ENDPOINTS.obtenerFiscal}/${idcliente}`,
    );

    if (!respuesta.status) {
      throw new Error(
        respuesta.message || "No fue posible obtener la información fiscal.",
      );
    }

    const datos = respuesta.data || {};

    Object.keys(datos).forEach(function (nombre) {
      const campo = document.querySelector(`[name="${nombre}"]`);

      if (!campo) {
        return;
      }

      if (campo.type === "checkbox") {
        campo.checked = Number(datos[nombre]) === 1;

        return;
      }

      campo.value = datos[nombre] ?? "";
    });

    estadoFiscalCliente.cargado = true;
  } catch (error) {
    console.error(error);

    mostrarError(error.message);
  } finally {
    estadoFiscalCliente.cargando = false;
  }
}

/**
 * Consulta y muestra los contactos registrados del cliente.
 *
 * @param {boolean} forzar Fuerza una nueva consulta.
 */
async function cargarContactosCliente(forzar = false) {
  const idcliente = obtenerIdCliente();

  if (idcliente <= 0 || estadoContactosCliente.cargando) {
    return;
  }

  if (estadoContactosCliente.registros.size > 0 && !forzar) {
    return;
  }

  const tbody = document.querySelector("#tbodyContactos");

  if (!tbody) {
    return;
  }

  estadoContactosCliente.cargando = true;

  tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-4">
                <span
                    class="spinner-border spinner-border-sm me-2"
                    role="status">
                </span>

                Cargando contactos...
            </td>
        </tr>
    `;

  try {
    const url =
      `${CLIENTES_ENDPOINTS.listarContactos}/` + encodeURIComponent(idcliente);

    const respuesta = await peticionJson(url, {
      method: "GET",
    });

    if (!respuesta.status) {
      throw new Error(
        respuesta.message || "No fue posible consultar los contactos.",
      );
    }

    const contactos = Array.isArray(respuesta.data)
      ? respuesta.data
      : Array.isArray(respuesta.data?.contactos)
        ? respuesta.data.contactos
        : [];

    tbody.innerHTML = "";

    estadoContactosCliente.registros.clear();

    contactos.forEach(function (registro) {
      const contacto = {
        idcontacto: Number(registro.idcontacto || registro.id || 0),

        nombre: registro.nombre || "",

        puesto: registro.puesto || "",

        correo: registro.correo || registro.email || "",

        telefono: registro.telefono || "",

        tipo_contacto: registro.tipo_contacto || registro.tipo || "COMERCIAL",

        notificar: String(registro.notificar ?? "1"),

        nuevo: false,
      };

      const fila = construirFilaContacto(contacto);

      tbody.appendChild(fila);

      estadoContactosCliente.registros.set(
        String(contacto.idcontacto),
        contacto,
      );
    });

    actualizarEstadoVacioContactos();
    actualizarContadorContactos();
  } catch (error) {
    console.error("Error al cargar contactos:", error);

    tbody.innerHTML = `
            <tr>
                <td
                    colspan="7"
                    class="text-center text-danger py-4">

                    <i class="ri-error-warning-line me-1"></i>

                    ${escaparHtml(
                      error.message || "No fue posible cargar los contactos.",
                    )}

                    <div class="mt-2">
                        <button
                            type="button"
                            class="btn btn-sm btn-light"
                            id="btnReintentarContactos">

                            <i class="ri-refresh-line me-1"></i>
                            Reintentar
                        </button>
                    </div>
                </td>
            </tr>
        `;

    document
      .querySelector("#btnReintentarContactos")
      ?.addEventListener("click", function () {
        cargarContactosCliente(true);
      });
  } finally {
    estadoContactosCliente.cargando = false;
  }
}

/**
 * Muestra una fila informativa cuando no existen contactos.
 */
function actualizarEstadoVacioContactos() {
  const tbody = document.querySelector("#tbodyContactos");

  if (!tbody) {
    return;
  }

  const filasReales = tbody.querySelectorAll("tr[data-contacto-id]");

  if (filasReales.length > 0) {
    eliminarFilaVaciaContactos();

    return;
  }

  tbody.innerHTML = `
        <tr class="fila-contactos-vacia">
            <td
                colspan="7"
                class="text-center text-muted py-4">

                <i class="ri-contacts-book-line fs-4 d-block mb-1"></i>

                No hay contactos registrados.

                <div class="small mt-1">
                    Presiona “Agregar contacto” para crear uno.
                </div>
            </td>
        </tr>
    `;
}

/**
 * Elimina la fila que indica que no existen contactos.
 */
function eliminarFilaVaciaContactos() {
  const filaVacia = document.querySelector(
    "#tbodyContactos .fila-contactos-vacia",
  );

  filaVacia?.remove();
}

/**
 * Actualiza el contador que aparece en la pestaña Contactos.
 */
function actualizarContadorContactos() {
  const botonTab = obtenerBotonTab("#tab-contactos");

  if (!botonTab) {
    return;
  }

  const total = document.querySelectorAll(
    "#tbodyContactos tr[data-contacto-id]",
  ).length;

  let contador = botonTab.querySelector(".tab-counter-contactos");

  if (!contador) {
    contador = document.createElement("span");

    contador.className =
      "badge bg-primary-subtle text-primary tab-counter tab-counter-contactos";

    botonTab.appendChild(contador);
  }

  contador.textContent = String(total);

  contador.style.display = total > 0 ? "" : "none";
}

/* ============================================================
 * 14. FUNCIONES AUXILIARES DE LA PARTE 2
 * ============================================================ */

/**
 * Valida una dirección de correo electrónico.
 *
 * @param {string} correo
 * @returns {boolean}
 */
function validarCorreoElectronico(correo) {
  const expresion = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

  return expresion.test(String(correo || "").trim());
}

/**
 * Escapa un valor para colocarlo dentro de un atributo HTML.
 *
 * @param {*} valor
 * @returns {string}
 */
function escaparAtributo(valor) {
  return escaparHtml(String(valor ?? ""))
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

/* ============================================================
 * PARTE 3
 *
 * - Creación dinámica de sucursales.
 * - Guardado y actualización de sucursales.
 * - Eliminación de sucursales.
 * - Consulta de sucursales registradas.
 * - Guardado de direcciones.
 * - Consulta de direcciones registradas.
 * - Edición y eliminación de direcciones.
 * ============================================================ */

/* ============================================================
 * 15. SUCURSALES
 * ============================================================ */

/**
 * Estado interno de la sección Sucursales.
 */
const estadoSucursalesCliente = {
  inicializado: false,
  cargando: false,
  registros: new Map(),
};

/**
 * Inicializa los eventos correspondientes a Sucursales.
 */
function configurarSeccionSucursales() {
  const contenedor = document.querySelector(SELECTORES_CLIENTE.tabSucursales);

  if (!contenedor || estadoSucursalesCliente.inicializado) {
    return;
  }

  const botonAgregar = document.querySelector("#btnAgregarSucursal");

  if (botonAgregar) {
    botonAgregar.addEventListener("click", agregarFormularioSucursal);
  }

  const contenedorSucursales = document.querySelector("#contenedorSucursales");

  if (contenedorSucursales) {
    contenedorSucursales.addEventListener("click", manejarAccionesSucursal);

    contenedorSucursales.addEventListener("input", manejarCambioSucursal);

    contenedorSucursales.addEventListener("change", manejarCambioSucursal);
  }

  const botonTab = obtenerBotonTab("#tab-sucursales");

  if (botonTab) {
    botonTab.addEventListener("shown.bs.tab", function () {
      if (validarClienteGuardado()) {
        cargarSucursalesCliente();
      }
    });
  }

  actualizarEstadoVacioSucursales();

  estadoSucursalesCliente.inicializado = true;
}

/**
 * Agrega un nuevo formulario para registrar una sucursal.
 */
function agregarFormularioSucursal() {
  if (!validarClienteGuardado()) {
    return;
  }

  estadoModuloClientes.contadorSucursales += 1;

  const identificadorTemporal =
    `nueva-${Date.now()}-` + estadoModuloClientes.contadorSucursales;

  const sucursal = {
    idsucursal: 0,
    identificadorTemporal: identificadorTemporal,
    nombre_sucursal: "",
    responsable: "",
    correo: "",
    telefono: "",
    calle: "",
    numero_exterior: "",
    numero_interior: "",
    colonia: "",
    codigo_postal: "",
    municipio: "",
    estado_republica: "",
    pais: "México",
    estado: "2",
    nuevo: true,
  };

  const tarjeta = construirTarjetaSucursal(sucursal);

  const contenedor = document.querySelector("#contenedorSucursales");

  if (!contenedor) {
    return;
  }

  eliminarEstadoVacioSucursales();

  contenedor.appendChild(tarjeta);

  estadoSucursalesCliente.registros.set(identificadorTemporal, sucursal);

  actualizarContadorSucursales();

  tarjeta.querySelector('[name="sucursal_nombre"]')?.focus();
}

/**
 * Construye una tarjeta completa para una sucursal.
 *
 * @param {object} sucursal
 * @returns {HTMLDivElement}
 */
function construirTarjetaSucursal(sucursal) {
  const tarjeta = document.createElement("div");

  const identificador =
    sucursal.idsucursal > 0
      ? String(sucursal.idsucursal)
      : sucursal.identificadorTemporal;

  tarjeta.className = "card border shadow-none mb-3 tarjeta-sucursal";

  tarjeta.dataset.sucursalId = identificador;
  tarjeta.dataset.guardado = sucursal.idsucursal > 0 ? "1" : "0";

  tarjeta.innerHTML = `
        <div class="card-header bg-light">
            <div
                class="d-flex justify-content-between align-items-center gap-2">

                <div>
                    <h6 class="mb-0 titulo-sucursal">
                        <i class="ri-building-2-line me-1"></i>

                        ${
                          sucursal.nombre_sucursal
                            ? escaparHtml(sucursal.nombre_sucursal)
                            : "Nueva sucursal"
                        }
                    </h6>

                    <small class="text-muted">
                        Información operativa y ubicación.
                    </small>
                </div>

                <div class="d-flex gap-1">
                    <button
                        type="button"
                        class="btn btn-sm btn-soft-success btn-guardar-sucursal">

                        <i class="ri-save-3-line me-1"></i>
                        Guardar
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-soft-danger btn-eliminar-sucursal">

                        <i class="ri-delete-bin-6-line me-1"></i>
                        Eliminar
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <input
                type="hidden"
                name="idsucursal"
                value="${Number(sucursal.idsucursal || 0)}">

            <div class="row g-3">
                <div class="col-lg-4 col-md-6">
                    <label class="form-label required">
                        Nombre de la sucursal
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        name="sucursal_nombre"
                        value="${escaparAtributo(sucursal.nombre_sucursal)}"
                        placeholder="Ej. Sucursal Toluca"
                        maxlength="150"
                        required>
                </div>

                <div class="col-lg-4 col-md-6">
                    <label class="form-label">
                        Responsable
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        name="sucursal_responsable"
                        value="${escaparAtributo(sucursal.responsable)}"
                        placeholder="Nombre del responsable"
                        maxlength="150">
                </div>

                <div class="col-lg-4 col-md-6">
                    <label class="form-label">
                        Correo
                    </label>

                    <input
                        type="email"
                        class="form-control"
                        name="sucursal_correo"
                        value="${escaparAtributo(sucursal.correo)}"
                        placeholder="sucursal@empresa.com"
                        maxlength="150">
                </div>

                <div class="col-lg-4 col-md-6">
                    <label class="form-label">
                        Teléfono
                    </label>

                    <input
                        type="tel"
                        class="form-control"
                        name="sucursal_telefono"
                        value="${escaparAtributo(sucursal.telefono)}"
                        placeholder="7221234567"
                        maxlength="15">
                </div>

                <div class="col-lg-5 col-md-6">
                    <label class="form-label required">
                        Calle
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        name="sucursal_calle"
                        value="${escaparAtributo(sucursal.calle)}"
                        placeholder="Nombre de la calle"
                        maxlength="180"
                        required>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="form-label required">
                        Número exterior
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        name="sucursal_numero_exterior"
                        value="${escaparAtributo(sucursal.numero_exterior)}"
                        placeholder="Ej. 125"
                        maxlength="30"
                        required>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="form-label">
                        Número interior
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        name="sucursal_numero_interior"
                        value="${escaparAtributo(sucursal.numero_interior)}"
                        placeholder="Ej. Local 3"
                        maxlength="30">
                </div>

                <div class="col-lg-4 col-md-6">
                    <label class="form-label required">
                        Colonia
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        name="sucursal_colonia"
                        value="${escaparAtributo(sucursal.colonia)}"
                        placeholder="Nombre de la colonia"
                        maxlength="150"
                        required>
                </div>

                <div class="col-lg-2 col-md-6">
                    <label class="form-label required">
                        Código postal
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        name="sucursal_codigo_postal"
                        value="${escaparAtributo(sucursal.codigo_postal)}"
                        placeholder="50000"
                        maxlength="5"
                        required>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="form-label required">
                        Municipio
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        name="sucursal_municipio"
                        value="${escaparAtributo(sucursal.municipio)}"
                        placeholder="Ej. Toluca"
                        maxlength="150"
                        required>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="form-label required">
                        Estado
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        name="sucursal_estado_republica"
                        value="${escaparAtributo(sucursal.estado_republica)}"
                        placeholder="Estado de México"
                        maxlength="150"
                        required>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="form-label required">
                        País
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        name="sucursal_pais"
                        value="${escaparAtributo(sucursal.pais || "México")}"
                        maxlength="100"
                        required>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="form-label">
                        Estado del registro
                    </label>

                    <select
                        class="form-select"
                        name="sucursal_estado">

                        <option
                            value="2"
                            ${
                              String(sucursal.estado) === "2" ? "selected" : ""
                            }>
                            Activa
                        </option>

                        <option
                            value="1"
                            ${
                              String(sucursal.estado) === "1" ? "selected" : ""
                            }>
                            Inactiva
                        </option>
                    </select>
                </div>
            </div>
        </div>
    `;

  configurarCamposTarjetaSucursal(tarjeta);

  return tarjeta;
}

/**
 * Configura restricciones de los campos de una sucursal.
 *
 * @param {HTMLElement} tarjeta
 */
function configurarCamposTarjetaSucursal(tarjeta) {
  const telefono = tarjeta.querySelector('[name="sucursal_telefono"]');

  const codigoPostal = tarjeta.querySelector('[name="sucursal_codigo_postal"]');

  const nombre = tarjeta.querySelector('[name="sucursal_nombre"]');

  telefono?.addEventListener("input", function () {
    telefono.value = telefono.value.replace(/[^\d+\s()-]/g, "");
  });

  codigoPostal?.addEventListener("input", function () {
    codigoPostal.value = codigoPostal.value.replace(/\D/g, "").slice(0, 5);
  });

  nombre?.addEventListener("input", function () {
    const titulo = tarjeta.querySelector(".titulo-sucursal");

    if (titulo) {
      titulo.innerHTML = `
                    <i class="ri-building-2-line me-1"></i>
                    ${
                      nombre.value.trim()
                        ? escaparHtml(nombre.value.trim())
                        : "Nueva sucursal"
                    }
                `;
    }
  });
}

/**
 * Maneja los botones de las tarjetas de sucursales.
 *
 * @param {MouseEvent} event
 */
function manejarAccionesSucursal(event) {
  const botonGuardar = event.target.closest(".btn-guardar-sucursal");

  if (botonGuardar) {
    guardarSucursal(botonGuardar.closest(".tarjeta-sucursal"));

    return;
  }

  const botonEliminar = event.target.closest(".btn-eliminar-sucursal");

  if (botonEliminar) {
    eliminarSucursal(botonEliminar.closest(".tarjeta-sucursal"));
  }
}

/**
 * Marca la tarjeta como modificada.
 *
 * @param {Event} event
 */
function manejarCambioSucursal(event) {
  const tarjeta = event.target.closest(".tarjeta-sucursal");

  if (!tarjeta) {
    return;
  }

  tarjeta.dataset.modificado = "1";
  tarjeta.classList.add("border-warning");
}

/**
 * Obtiene los datos de una tarjeta de sucursal.
 *
 * @param {HTMLElement} tarjeta
 * @returns {object}
 */
function obtenerDatosSucursal(tarjeta) {
  const valor = function (nombre) {
    return tarjeta.querySelector(`[name="${nombre}"]`)?.value?.trim() || "";
  };

  return {
    idsucursal: Number(valor("idsucursal") || 0),

    nombre_sucursal: valor("sucursal_nombre"),

    responsable: valor("sucursal_responsable"),

    correo: valor("sucursal_correo").toLowerCase(),

    telefono: valor("sucursal_telefono"),

    calle: valor("sucursal_calle"),

    numero_exterior: valor("sucursal_numero_exterior"),

    numero_interior: valor("sucursal_numero_interior"),

    colonia: valor("sucursal_colonia"),

    codigo_postal: valor("sucursal_codigo_postal"),

    municipio: valor("sucursal_municipio"),

    estado_republica: valor("sucursal_estado_republica"),

    pais: valor("sucursal_pais"),

    estado: valor("sucursal_estado") || "2",
  };
}

/**
 * Valida todos los campos de una sucursal.
 *
 * @param {HTMLElement} tarjeta
 * @returns {boolean}
 */
function validarTarjetaSucursal(tarjeta) {
  if (!tarjeta) {
    return false;
  }

  const campos = tarjeta.querySelectorAll(
    'input:not([type="hidden"]), select, textarea',
  );

  let valido = true;
  let primerInvalido = null;

  campos.forEach(function (campo) {
    campo.classList.remove("is-valid", "is-invalid");

    if (!campo.checkValidity()) {
      campo.classList.add("is-invalid");
      valido = false;

      primerInvalido = primerInvalido || campo;
    } else if (campo.required && campo.value) {
      campo.classList.add("is-valid");
    }
  });

  const datos = obtenerDatosSucursal(tarjeta);

  if (datos.correo && !validarCorreoElectronico(datos.correo)) {
    const correo = tarjeta.querySelector('[name="sucursal_correo"]');

    correo?.classList.add("is-invalid");

    valido = false;
    primerInvalido = primerInvalido || correo;
  }

  if (datos.telefono && datos.telefono.replace(/\D/g, "").length < 10) {
    const telefono = tarjeta.querySelector('[name="sucursal_telefono"]');

    telefono?.classList.add("is-invalid");

    valido = false;
    primerInvalido = primerInvalido || telefono;
  }

  if (!/^\d{5}$/.test(datos.codigo_postal)) {
    const cp = tarjeta.querySelector('[name="sucursal_codigo_postal"]');

    cp?.classList.add("is-invalid");

    valido = false;
    primerInvalido = primerInvalido || cp;
  }

  primerInvalido?.focus();

  return valido;
}

/**
 * Guarda o actualiza una sucursal.
 *
 * @param {HTMLElement} tarjeta
 */
async function guardarSucursal(tarjeta) {
  if (!validarClienteGuardado()) {
    return;
  }

  if (!tarjeta || tarjeta.dataset.guardando === "1") {
    return;
  }

  if (!validarTarjetaSucursal(tarjeta)) {
    mostrarAdvertencia(
      "Complete correctamente los datos obligatorios de la sucursal.",
    );

    return;
  }

  const datos = obtenerDatosSucursal(tarjeta);

  const confirmado = await confirmarAccion(
    datos.idsucursal > 0
      ? "¿Desea actualizar esta sucursal?"
      : "¿Desea guardar esta sucursal?",
    datos.idsucursal > 0 ? "Actualizar sucursal" : "Guardar sucursal",
  );

  if (!confirmado) {
    return;
  }

  const boton = tarjeta.querySelector(".btn-guardar-sucursal");

  const contenidoOriginal = boton?.innerHTML || "";

  tarjeta.dataset.guardando = "1";

  establecerEstadoBoton(boton, true, "Guardando...");

  try {
    const formData = new FormData();

    formData.append("idcliente", String(obtenerIdCliente()));

    Object.entries(datos).forEach(function ([clave, valor]) {
      formData.append(clave, String(valor ?? ""));
    });

    const respuesta = await peticionJson(CLIENTES_ENDPOINTS.guardarSucursal, {
      method: "POST",
      body: formData,
    });

    if (!respuesta.status) {
      throw new Error(
        respuesta.message || "No fue posible guardar la sucursal.",
      );
    }

    const idsucursal = Number(
      respuesta.data?.idsucursal || respuesta.idsucursal || datos.idsucursal,
    );

    if (!Number.isInteger(idsucursal) || idsucursal <= 0) {
      throw new Error("El servidor no devolvió un ID de sucursal válido.");
    }

    const identificadorAnterior = tarjeta.dataset.sucursalId;

    tarjeta.dataset.sucursalId = String(idsucursal);

    tarjeta.dataset.guardado = "1";
    tarjeta.dataset.modificado = "0";

    const inputId = tarjeta.querySelector('[name="idsucursal"]');

    if (inputId) {
      inputId.value = String(idsucursal);
    }

    tarjeta.classList.remove("border-warning", "border-danger");

    tarjeta.classList.add("border-success");

    setTimeout(function () {
      tarjeta.classList.remove("border-success");
    }, 1200);

    estadoSucursalesCliente.registros.delete(identificadorAnterior);

    estadoSucursalesCliente.registros.set(String(idsucursal), {
      ...datos,
      idsucursal: idsucursal,
      nuevo: false,
    });

    actualizarContadorSucursales();

    mostrarExito(respuesta.message || "La sucursal se guardó correctamente.");
  } catch (error) {
    console.error("Error al guardar sucursal:", error);

    tarjeta.classList.add("border-danger");

    mostrarError(error.message || "Ocurrió un error al guardar la sucursal.");
  } finally {
    tarjeta.dataset.guardando = "0";

    restaurarBoton(boton, contenidoOriginal);
  }
}

/**
 * Elimina una sucursal.
 *
 * @param {HTMLElement} tarjeta
 */
async function eliminarSucursal(tarjeta) {
  if (!tarjeta) {
    return;
  }

  const datos = obtenerDatosSucursal(tarjeta);

  if (datos.idsucursal <= 0) {
    const confirmado = await confirmarAccion(
      "¿Desea quitar esta sucursal sin guardar?",
      "Quitar sucursal",
    );

    if (!confirmado) {
      return;
    }

    estadoSucursalesCliente.registros.delete(tarjeta.dataset.sucursalId);

    tarjeta.remove();

    actualizarEstadoVacioSucursales();
    actualizarContadorSucursales();

    return;
  }

  const confirmado = await confirmarAccion(
    `¿Desea eliminar la sucursal "${datos.nombre_sucursal}"?`,
    "Eliminar sucursal",
  );

  if (!confirmado) {
    return;
  }

  const boton = tarjeta.querySelector(".btn-eliminar-sucursal");

  const contenidoOriginal = boton?.innerHTML || "";

  establecerEstadoBoton(boton, true, "Eliminando...");

  try {
    const formData = new FormData();

    formData.append("idcliente", String(obtenerIdCliente()));

    formData.append("idsucursal", String(datos.idsucursal));

    const respuesta = await peticionJson(CLIENTES_ENDPOINTS.eliminarSucursal, {
      method: "POST",
      body: formData,
    });

    if (!respuesta.status) {
      throw new Error(
        respuesta.message || "No fue posible eliminar la sucursal.",
      );
    }

    estadoSucursalesCliente.registros.delete(String(datos.idsucursal));

    tarjeta.remove();

    actualizarEstadoVacioSucursales();
    actualizarContadorSucursales();

    mostrarExito(respuesta.message || "La sucursal se eliminó correctamente.");
  } catch (error) {
    console.error("Error al eliminar sucursal:", error);

    mostrarError(error.message || "Ocurrió un error al eliminar la sucursal.");

    restaurarBoton(boton, contenidoOriginal);
  }
}

/**
 * Consulta las sucursales registradas.
 *
 * @param {boolean} forzar
 */
async function cargarSucursalesCliente(forzar = false) {
  const idcliente = obtenerIdCliente();

  if (idcliente <= 0 || estadoSucursalesCliente.cargando) {
    return;
  }

  if (estadoSucursalesCliente.registros.size > 0 && !forzar) {
    return;
  }

  const contenedor = document.querySelector("#contenedorSucursales");

  if (!contenedor) {
    return;
  }

  estadoSucursalesCliente.cargando = true;

  contenedor.innerHTML = `
        <div class="text-center py-4">
            <span
                class="spinner-border spinner-border-sm me-2">
            </span>

            Cargando sucursales...
        </div>
    `;

  try {
    const url =
      `${CLIENTES_ENDPOINTS.listarSucursales}/` + encodeURIComponent(idcliente);

    const respuesta = await peticionJson(url, {
      method: "GET",
    });

    if (!respuesta.status) {
      throw new Error(
        respuesta.message || "No fue posible consultar las sucursales.",
      );
    }

    const sucursales = Array.isArray(respuesta.data)
      ? respuesta.data
      : Array.isArray(respuesta.data?.sucursales)
        ? respuesta.data.sucursales
        : [];

    contenedor.innerHTML = "";

    estadoSucursalesCliente.registros.clear();

    sucursales.forEach(function (registro) {
      const sucursal = {
        idsucursal: Number(registro.idsucursal || registro.id || 0),

        nombre_sucursal: registro.nombre_sucursal || registro.nombre || "",

        responsable: registro.responsable || "",

        correo: registro.correo || "",

        telefono: registro.telefono || "",

        calle: registro.calle || "",

        numero_exterior: registro.numero_exterior || registro.num_ext || "",

        numero_interior: registro.numero_interior || registro.num_int || "",

        colonia: registro.colonia || "",

        codigo_postal: registro.codigo_postal || registro.cp || "",

        municipio: registro.municipio || "",

        estado_republica:
          registro.estado_republica || registro.estado_nombre || "",

        pais: registro.pais || "México",

        estado: String(registro.estado ?? "2"),

        nuevo: false,
      };

      const tarjeta = construirTarjetaSucursal(sucursal);

      contenedor.appendChild(tarjeta);

      estadoSucursalesCliente.registros.set(
        String(sucursal.idsucursal),
        sucursal,
      );
    });

    actualizarEstadoVacioSucursales();
    actualizarContadorSucursales();
  } catch (error) {
    console.error("Error al cargar sucursales:", error);

    contenedor.innerHTML = `
            <div class="text-center text-danger py-4">
                <i class="ri-error-warning-line me-1"></i>

                ${escaparHtml(
                  error.message || "No fue posible cargar las sucursales.",
                )}

                <div class="mt-2">
                    <button
                        type="button"
                        class="btn btn-sm btn-light"
                        id="btnReintentarSucursales">

                        <i class="ri-refresh-line me-1"></i>
                        Reintentar
                    </button>
                </div>
            </div>
        `;

    document
      .querySelector("#btnReintentarSucursales")
      ?.addEventListener("click", function () {
        cargarSucursalesCliente(true);
      });
  } finally {
    estadoSucursalesCliente.cargando = false;
  }
}

/**
 * Muestra el estado vacío cuando no existen sucursales.
 */
function actualizarEstadoVacioSucursales() {
  const contenedor = document.querySelector("#contenedorSucursales");

  if (!contenedor) {
    return;
  }

  const tarjetas = contenedor.querySelectorAll(".tarjeta-sucursal");

  if (tarjetas.length > 0) {
    eliminarEstadoVacioSucursales();

    return;
  }

  contenedor.innerHTML = `
        <div
            class="text-center text-muted py-5 estado-vacio-sucursales">

            <i class="ri-building-2-line fs-1 d-block mb-2"></i>

            No hay sucursales registradas.

            <div class="small mt-1">
                Presiona “Agregar sucursal” para crear una.
            </div>
        </div>
    `;
}

/**
 * Elimina el estado vacío de sucursales.
 */
function eliminarEstadoVacioSucursales() {
  document
    .querySelector("#contenedorSucursales .estado-vacio-sucursales")
    ?.remove();
}

/**
 * Actualiza el contador de la pestaña Sucursales.
 */
function actualizarContadorSucursales() {
  const botonTab = obtenerBotonTab("#tab-sucursales");

  if (!botonTab) {
    return;
  }

  const total = document.querySelectorAll(
    "#contenedorSucursales .tarjeta-sucursal",
  ).length;

  let contador = botonTab.querySelector(".tab-counter-sucursales");

  if (!contador) {
    contador = document.createElement("span");

    contador.className =
      "badge bg-primary-subtle text-primary tab-counter tab-counter-sucursales";

    botonTab.appendChild(contador);
  }

  contador.textContent = String(total);

  contador.style.display = total > 0 ? "" : "none";
}

/* ============================================================
 * 16. DIRECCIONES
 * ============================================================ */

/**
 * Estado interno de Direcciones.
 */
const estadoDireccionesCliente = {
  inicializado: false,
  guardando: false,
  cargando: false,
  iddireccionActual: 0,
  registros: [],
};

/**
 * Inicializa la sección Direcciones.
 */
function configurarSeccionDirecciones() {
  const contenedor = document.querySelector(SELECTORES_CLIENTE.tabDirecciones);

  if (!contenedor || estadoDireccionesCliente.inicializado) {
    return;
  }

  crearAccionesDirecciones();
  crearListadoDirecciones();
  configurarCamposDireccion();

  const botonTab = obtenerBotonTab("#tab-direcciones");

  botonTab?.addEventListener("shown.bs.tab", function () {
    if (validarClienteGuardado()) {
      cargarDireccionesCliente();
    }
  });

  estadoDireccionesCliente.inicializado = true;
}

/**
 * Agrega los botones Guardar y Limpiar dirección.
 */
function crearAccionesDirecciones() {
  const contenedor = document.querySelector(SELECTORES_CLIENTE.tabDirecciones);

  if (!contenedor || document.querySelector("#accionesDirecciones")) {
    return;
  }

  const acciones = document.createElement("div");

  acciones.id = "accionesDirecciones";

  acciones.className = "d-flex justify-content-end gap-2 mt-4";

  acciones.innerHTML = `
        <button
            type="button"
            class="btn btn-light"
            id="btnNuevaDireccion">

            <i class="ri-add-line me-1"></i>
            Nueva dirección
        </button>

        <button
            type="button"
            class="btn btn-primary btn-label"
            id="btnGuardarDireccion">

            <i
                class="ri-save-3-line label-icon align-middle fs-16 me-2">
            </i>

            Guardar dirección
        </button>
    `;

  contenedor.appendChild(acciones);

  document
    .querySelector("#btnGuardarDireccion")
    ?.addEventListener("click", guardarDireccion);

  document
    .querySelector("#btnNuevaDireccion")
    ?.addEventListener("click", limpiarFormularioDireccion);
}

/**
 * Crea el contenedor donde se mostrarán las direcciones.
 */
function crearListadoDirecciones() {
  const contenedor = document.querySelector(SELECTORES_CLIENTE.tabDirecciones);

  if (!contenedor || document.querySelector("#listadoDirecciones")) {
    return;
  }

  const listado = document.createElement("div");

  listado.id = "listadoDirecciones";
  listado.className = "mt-4";

  listado.innerHTML = `
        <hr>

        <div class="section-title">
            Direcciones registradas
        </div>

        <div
            class="row g-3"
            id="contenedorListadoDirecciones">
        </div>
    `;

  contenedor.appendChild(listado);

  listado.addEventListener("click", manejarAccionesDireccion);
}

/**
 * Configura restricciones de campos de dirección.
 */
function configurarCamposDireccion() {
  const cp = obtenerCampoDireccion("codigo_postal");

  cp?.addEventListener("input", function () {
    cp.value = cp.value.replace(/\D/g, "").slice(0, 5);
  });
}

/**
 * Obtiene un campo únicamente dentro de tab-direcciones.
 *
 * @param {string} nombre
 * @returns {HTMLElement|null}
 */
function obtenerCampoDireccion(nombre) {
  return document.querySelector(
    `${SELECTORES_CLIENTE.tabDirecciones} [name="${nombre}"]`,
  );
}

/**
 * Obtiene los datos capturados en la dirección.
 *
 * @returns {object}
 */
function obtenerDatosDireccion() {
  const valor = function (nombre) {
    return obtenerCampoDireccion(nombre)?.value?.trim() || "";
  };

  return {
    iddireccion: estadoDireccionesCliente.iddireccionActual,

    tipo_direccion: valor("tipo_direccion"),

    calle: valor("calle"),

    numero_exterior: valor("numero_exterior"),

    numero_interior: valor("numero_interior"),

    colonia: valor("colonia"),

    codigo_postal: valor("codigo_postal"),

    municipio: valor("municipio"),

    estado_republica: valor("estado_republica"),

    pais: valor("pais"),

    referencias: valor("referencias"),
  };
}

/**
 * Valida la información de una dirección.
 *
 * @returns {boolean}
 */
function validarDatosDireccion() {
  const contenedor = document.querySelector(SELECTORES_CLIENTE.tabDirecciones);

  if (!validarContenedor(contenedor)) {
    return false;
  }

  const datos = obtenerDatosDireccion();

  if (!/^\d{5}$/.test(datos.codigo_postal)) {
    const cp = obtenerCampoDireccion("codigo_postal");

    cp?.classList.add("is-invalid");
    cp?.focus();

    mostrarAdvertencia("El código postal debe contener exactamente 5 números.");

    return false;
  }

  return true;
}

/**
 * Guarda o actualiza una dirección.
 */
async function guardarDireccion() {
  if (!validarClienteGuardado()) {
    return;
  }

  if (estadoDireccionesCliente.guardando) {
    return;
  }

  if (!validarDatosDireccion()) {
    mostrarAdvertencia(
      "Complete correctamente los datos obligatorios de la dirección.",
    );

    return;
  }

  const datos = obtenerDatosDireccion();

  const confirmado = await confirmarAccion(
    datos.iddireccion > 0
      ? "¿Desea actualizar esta dirección?"
      : "¿Desea guardar esta dirección?",
    datos.iddireccion > 0 ? "Actualizar dirección" : "Guardar dirección",
  );

  if (!confirmado) {
    return;
  }

  const boton = document.querySelector("#btnGuardarDireccion");

  const contenidoOriginal = boton?.innerHTML || "";

  estadoDireccionesCliente.guardando = true;

  establecerEstadoBoton(boton, true, "Guardando...");

  try {
    const formData = new FormData();

    formData.append("idcliente", String(obtenerIdCliente()));

    Object.entries(datos).forEach(function ([clave, valor]) {
      formData.append(clave, String(valor ?? ""));
    });

    const respuesta = await peticionJson(CLIENTES_ENDPOINTS.guardarDireccion, {
      method: "POST",
      body: formData,
    });

    if (!respuesta.status) {
      throw new Error(
        respuesta.message || "No fue posible guardar la dirección.",
      );
    }

    mostrarExito(respuesta.message || "La dirección se guardó correctamente.");

    limpiarFormularioDireccion();

    await cargarDireccionesCliente(true);
  } catch (error) {
    console.error("Error al guardar dirección:", error);

    mostrarError(error.message || "Ocurrió un error al guardar la dirección.");
  } finally {
    estadoDireccionesCliente.guardando = false;

    restaurarBoton(boton, contenidoOriginal);
  }
}

/**
 * Consulta las direcciones registradas.
 *
 * @param {boolean} forzar
 */
async function cargarDireccionesCliente(forzar = false) {
  const idcliente = obtenerIdCliente();

  if (idcliente <= 0 || estadoDireccionesCliente.cargando) {
    return;
  }

  if (estadoDireccionesCliente.registros.length > 0 && !forzar) {
    return;
  }

  const contenedor = document.querySelector("#contenedorListadoDirecciones");

  if (!contenedor) {
    return;
  }

  estadoDireccionesCliente.cargando = true;

  contenedor.innerHTML = `
        <div class="col-12 text-center py-4">
            <span
                class="spinner-border spinner-border-sm me-2">
            </span>

            Cargando direcciones...
        </div>
    `;

  try {
    const url =
      `${CLIENTES_ENDPOINTS.listarDirecciones}/` +
      encodeURIComponent(idcliente);

    const respuesta = await peticionJson(url, {
      method: "GET",
    });

    if (!respuesta.status) {
      throw new Error(
        respuesta.message || "No fue posible consultar las direcciones.",
      );
    }

    estadoDireccionesCliente.registros = Array.isArray(respuesta.data)
      ? respuesta.data
      : Array.isArray(respuesta.data?.direcciones)
        ? respuesta.data.direcciones
        : [];

    renderizarDirecciones();
  } catch (error) {
    console.error("Error al cargar direcciones:", error);

    contenedor.innerHTML = `
            <div class="col-12 text-center text-danger py-4">
                ${escaparHtml(error.message)}
            </div>
        `;
  } finally {
    estadoDireccionesCliente.cargando = false;
  }
}

/**
 * Renderiza las tarjetas de direcciones.
 */
function renderizarDirecciones() {
  const contenedor = document.querySelector("#contenedorListadoDirecciones");

  if (!contenedor) {
    return;
  }

  if (estadoDireccionesCliente.registros.length === 0) {
    contenedor.innerHTML = `
            <div class="col-12 text-center text-muted py-4">
                No hay direcciones registradas.
            </div>
        `;

    actualizarContadorDirecciones();

    return;
  }

  contenedor.innerHTML = estadoDireccionesCliente.registros
    .map(function (direccion) {
      const iddireccion = Number(direccion.iddireccion || direccion.id || 0);

      return `
                    <div class="col-lg-6">
                        <div class="card border shadow-none h-100">
                            <div class="card-body">
                                <div
                                    class="d-flex justify-content-between gap-2">

                                    <div>
                                        <span
                                            class="badge bg-primary-subtle text-primary">

                                            ${escaparHtml(
                                              direccion.tipo_direccion,
                                            )}
                                        </span>

                                        <h6 class="mt-2 mb-1">
                                            ${escaparHtml(direccion.calle)}
                                            ${escaparHtml(
                                              direccion.numero_exterior,
                                            )}
                                        </h6>

                                        <p class="text-muted mb-1">
                                            ${escaparHtml(direccion.colonia)},
                                            ${escaparHtml(direccion.municipio)}
                                        </p>

                                        <small class="text-muted">
                                            ${escaparHtml(
                                              direccion.estado_republica,
                                            )},
                                            CP ${escaparHtml(
                                              direccion.codigo_postal,
                                            )}
                                        </small>
                                    </div>

                                    <div class="text-nowrap">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-soft-info btn-editar-direccion"
                                            data-id="${iddireccion}">

                                            <i class="ri-edit-line"></i>
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-soft-danger btn-eliminar-direccion"
                                            data-id="${iddireccion}">

                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
    })
    .join("");

  actualizarContadorDirecciones();
}

/**
 * Maneja editar y eliminar dirección.
 *
 * @param {MouseEvent} event
 */
function manejarAccionesDireccion(event) {
  const botonEditar = event.target.closest(".btn-editar-direccion");

  if (botonEditar) {
    editarDireccion(Number(botonEditar.dataset.id));

    return;
  }

  const botonEliminar = event.target.closest(".btn-eliminar-direccion");

  if (botonEliminar) {
    eliminarDireccion(Number(botonEliminar.dataset.id));
  }
}

/**
 * Carga una dirección en el formulario.
 *
 * @param {number} iddireccion
 */
function editarDireccion(iddireccion) {
  const direccion = estadoDireccionesCliente.registros.find(
    function (registro) {
      return Number(registro.iddireccion || registro.id) === iddireccion;
    },
  );

  if (!direccion) {
    mostrarError("No se encontró la dirección seleccionada.");

    return;
  }

  estadoDireccionesCliente.iddireccionActual = iddireccion;

  const campos = {
    tipo_direccion: direccion.tipo_direccion,

    calle: direccion.calle,

    numero_exterior: direccion.numero_exterior,

    numero_interior: direccion.numero_interior,

    colonia: direccion.colonia,

    codigo_postal: direccion.codigo_postal,

    municipio: direccion.municipio,

    estado_republica: direccion.estado_republica,

    pais: direccion.pais,

    referencias: direccion.referencias,
  };

  Object.entries(campos).forEach(function ([nombre, valor]) {
    const campo = obtenerCampoDireccion(nombre);

    if (campo) {
      campo.value = valor || "";
    }
  });

  document.querySelector("#btnGuardarDireccion").innerHTML = `
        <i
            class="ri-save-3-line label-icon align-middle fs-16 me-2">
        </i>

        Actualizar dirección
    `;

  document.querySelector(SELECTORES_CLIENTE.tabDirecciones)?.scrollIntoView({
    behavior: "smooth",
    block: "start",
  });
}

/**
 * Elimina una dirección.
 *
 * @param {number} iddireccion
 */
async function eliminarDireccion(iddireccion) {
  if (iddireccion <= 0) {
    return;
  }

  const confirmado = await confirmarAccion(
    "¿Desea eliminar esta dirección?",
    "Eliminar dirección",
  );

  if (!confirmado) {
    return;
  }

  try {
    const formData = new FormData();

    formData.append("idcliente", String(obtenerIdCliente()));

    formData.append("iddireccion", String(iddireccion));

    const respuesta = await peticionJson(CLIENTES_ENDPOINTS.eliminarDireccion, {
      method: "POST",
      body: formData,
    });

    if (!respuesta.status) {
      throw new Error(
        respuesta.message || "No fue posible eliminar la dirección.",
      );
    }

    mostrarExito(respuesta.message || "La dirección se eliminó correctamente.");

    if (estadoDireccionesCliente.iddireccionActual === iddireccion) {
      limpiarFormularioDireccion();
    }

    await cargarDireccionesCliente(true);
  } catch (error) {
    console.error("Error al eliminar dirección:", error);

    mostrarError(error.message || "Ocurrió un error al eliminar la dirección.");
  }
}

/**
 * Limpia el formulario de dirección.
 */
function limpiarFormularioDireccion() {
  estadoDireccionesCliente.iddireccionActual = 0;

  const nombres = [
    "tipo_direccion",
    "calle",
    "numero_exterior",
    "numero_interior",
    "colonia",
    "codigo_postal",
    "municipio",
    "estado_republica",
    "pais",
    "referencias",
  ];

  nombres.forEach(function (nombre) {
    const campo = obtenerCampoDireccion(nombre);

    if (!campo) {
      return;
    }

    campo.value = nombre === "pais" ? "México" : "";

    campo.classList.remove("is-valid", "is-invalid");
  });

  const boton = document.querySelector("#btnGuardarDireccion");

  if (boton) {
    boton.innerHTML = `
            <i
                class="ri-save-3-line label-icon align-middle fs-16 me-2">
            </i>

            Guardar dirección
        `;
  }
}

/**
 * Actualiza el contador de direcciones.
 */
function actualizarContadorDirecciones() {
  const botonTab = obtenerBotonTab("#tab-direcciones");

  if (!botonTab) {
    return;
  }

  const total = estadoDireccionesCliente.registros.length;

  let contador = botonTab.querySelector(".tab-counter-direcciones");

  if (!contador) {
    contador = document.createElement("span");

    contador.className =
      "badge bg-primary-subtle text-primary tab-counter tab-counter-direcciones";

    botonTab.appendChild(contador);
  }

  contador.textContent = String(total);

  contador.style.display = total > 0 ? "" : "none";
}

/* ============================================================
 * PARTE 4
 *
 * - Información comercial.
 * - Crédito y condiciones de pago.
 * - Registro de cuentas bancarias.
 * - Consulta, edición y eliminación de bancos.
 * - Carga de documentos.
 * - Validación de extensiones y tamaños.
 * - Consulta, descarga y eliminación de documentos.
 * ============================================================ */

/* ============================================================
 * 17. INFORMACIÓN COMERCIAL
 * ============================================================ */

/**
 * Estado interno de la sección Comercial.
 */
const estadoComercialCliente = {

    guardando:false,

    botonCreado:false,

    informacionGuardada:false,

    cargando:false,

    cargado:false

};

/**
 * Inicializa la sección de información comercial.
 */
function configurarSeccionComercial() {
  const contenedor = document.querySelector(SELECTORES_CLIENTE.tabComercial);

  if (!contenedor || estadoComercialCliente.inicializado) {
    return;
  }

  crearBotonGuardarComercial();
  configurarCamposComerciales();

  estadoComercialCliente.inicializado = true;





const botonTab = obtenerBotonTab("#tab-comercial");

if (botonTab) {

    botonTab.addEventListener(
        "shown.bs.tab",
        function(){

            if(validarClienteGuardado()){

                cargarInformacionComercialCliente();

            }

        }
    );

}


}



async function cargarInformacionComercialCliente(forzar = false) {

    const idcliente = obtenerIdCliente();

    if (idcliente <= 0) {
        return;
    }

    if (estadoComercialCliente.cargando) {
        return;
    }

    if (
        estadoComercialCliente.cargado &&
        !forzar
    ) {
        return;
    }

    estadoComercialCliente.cargando = true;

    try {

        const respuesta = await peticionJson(

            `${CLIENTES_ENDPOINTS.obtenerComercial}/${idcliente}`

        );

        if (!respuesta.status) {

            throw new Error(

                respuesta.message ||
                "No fue posible obtener la información comercial."

            );

        }

        const datos = respuesta.data || {};

        Object.keys(datos).forEach(function(nombre){

            const campo =
                document.querySelector(
                    `[name="${nombre}"]`
                );

            if(!campo){
                return;
            }

            if(campo.type==="checkbox"){

                campo.checked =
                    Number(datos[nombre])===1;

                return;
            }

            campo.value =
                datos[nombre] ?? "";

        });

        estadoComercialCliente.cargado = true;

    }
    catch(error){

        console.error(error);

        mostrarError(error.message);

    }
    finally{

        estadoComercialCliente.cargando = false;

    }

}

/**
 * Crea el botón para guardar información comercial.
 */
function crearBotonGuardarComercial() {
  const contenedor = document.querySelector(SELECTORES_CLIENTE.tabComercial);

  if (!contenedor || document.querySelector("#btnGuardarComercial")) {
    return;
  }

  const acciones = document.createElement("div");

  acciones.className = "d-flex justify-content-end gap-2 mt-4";

  acciones.innerHTML = `
        <button
            type="button"
            class="btn btn-primary btn-label"
            id="btnGuardarComercial">

            <i
                class="ri-save-3-line label-icon align-middle fs-16 me-2">
            </i>

            Guardar información comercial
        </button>
    `;

  contenedor.appendChild(acciones);

  document
    .querySelector("#btnGuardarComercial")
    ?.addEventListener("click", guardarInformacionComercial);
}

/**
 * Configura restricciones y eventos de campos comerciales.
 */
function configurarCamposComerciales() {
  const limiteCredito = document.querySelector(
    `${SELECTORES_CLIENTE.tabComercial} [name="limite_credito"]`,
  );

  const diasCredito = document.querySelector(
    `${SELECTORES_CLIENTE.tabComercial} [name="dias_credito"]`,
  );

  const descuento = document.querySelector(
    `${SELECTORES_CLIENTE.tabComercial} [name="porcentaje_descuento"]`,
  );

  const requiereCredito = document.querySelector(
    `${SELECTORES_CLIENTE.tabComercial} [name="maneja_credito"]`,
  );

  limiteCredito?.addEventListener("input", function () {
    limiteCredito.value = limpiarNumeroDecimal(limiteCredito.value, 2);
  });

  diasCredito?.addEventListener("input", function () {
    diasCredito.value = diasCredito.value.replace(/\D/g, "").slice(0, 3);
  });

  descuento?.addEventListener("input", function () {
    descuento.value = limpiarNumeroDecimal(descuento.value, 2);

    const valor = Number(descuento.value || 0);

    if (valor > 100) {
      descuento.value = "100";
    }
  });

  requiereCredito?.addEventListener("change", actualizarCamposCredito);

  actualizarCamposCredito();
}

/**
 * Habilita o deshabilita los campos de crédito.
 */
function actualizarCamposCredito() {
  const campoManejaCredito = document.querySelector(
    `${SELECTORES_CLIENTE.tabComercial} [name="maneja_credito"]`,
  );

  if (!campoManejaCredito) {
    return;
  }

  const manejaCredito = obtenerValorBooleanoCampo(campoManejaCredito);

  const camposCredito = [
    document.querySelector(
      `${SELECTORES_CLIENTE.tabComercial} [name="limite_credito"]`,
    ),

    document.querySelector(
      `${SELECTORES_CLIENTE.tabComercial} [name="dias_credito"]`,
    ),

    document.querySelector(
      `${SELECTORES_CLIENTE.tabComercial} [name="tipo_credito"]`,
    ),
  ];

  camposCredito.forEach(function (campo) {
    if (!campo) {
      return;
    }

    campo.disabled = !manejaCredito;
    campo.required = manejaCredito;

    if (!manejaCredito) {
      if (campo.name === "limite_credito") {
        campo.value = "0.00";
      } else if (campo.name === "dias_credito") {
        campo.value = "0";
      } else {
        campo.value = "";
      }

      campo.classList.remove("is-valid", "is-invalid");
    }
  });
}

/**
 * Obtiene los datos comerciales capturados.
 *
 * @returns {object}
 */
function obtenerDatosComerciales() {
  const contenedor = document.querySelector(SELECTORES_CLIENTE.tabComercial);

  const obtenerValor = function (nombre) {
    const campo = contenedor?.querySelector(`[name="${nombre}"]`);

    if (!campo) {
      return "";
    }

    if (campo.type === "checkbox") {
      return campo.checked ? "1" : "0";
    }

    return campo.value?.trim() || "";
  };

  return {
    idcliente: obtenerIdCliente(),

    maneja_credito: obtenerValor("maneja_credito") || "0",

    limite_credito: obtenerValor("limite_credito") || "0",

    dias_credito: obtenerValor("dias_credito") || "0",

    tipo_credito: obtenerValor("tipo_credito"),

    condicion_pago: obtenerValor("condicion_pago"),

    forma_pago: obtenerValor("forma_pago"),

    metodo_pago: obtenerValor("metodo_pago"),

    moneda: obtenerValor("moneda") || "MXN",

    lista_precio: obtenerValor("lista_precio"),

    porcentaje_descuento: obtenerValor("porcentaje_descuento") || "0",

    ejecutivo_asignado: obtenerValor("ejecutivo_asignado"),

    zona_comercial: obtenerValor("zona_comercial"),

    territorio: obtenerValor("territorio"),

    segmento_mercado: obtenerValor("segmento_mercado"),

    origen_cliente: obtenerValor("origen_cliente"),

    observaciones_comerciales: obtenerValor("observaciones_comerciales"),
  };
}

/**
 * Valida la información comercial.
 *
 * @returns {boolean}
 */
function validarDatosComerciales() {
  const contenedor = document.querySelector(SELECTORES_CLIENTE.tabComercial);

  if (!contenedor) {
    return false;
  }

  actualizarCamposCredito();

  if (!validarContenedor(contenedor)) {
    return false;
  }

  const datos = obtenerDatosComerciales();

  if (String(datos.maneja_credito) === "1") {
    const limite = Number(datos.limite_credito);

    const dias = Number(datos.dias_credito);

    if (!Number.isFinite(limite) || limite <= 0) {
      const campo = contenedor.querySelector('[name="limite_credito"]');

      campo?.classList.add("is-invalid");
      campo?.focus();

      mostrarAdvertencia("El límite de crédito debe ser mayor a cero.");

      return false;
    }

    if (!Number.isInteger(dias) || dias <= 0) {
      const campo = contenedor.querySelector('[name="dias_credito"]');

      campo?.classList.add("is-invalid");
      campo?.focus();

      mostrarAdvertencia("Los días de crédito deben ser mayores a cero.");

      return false;
    }
  }

  const descuento = Number(datos.porcentaje_descuento || 0);

  if (!Number.isFinite(descuento) || descuento < 0 || descuento > 100) {
    const campo = contenedor.querySelector('[name="porcentaje_descuento"]');

    campo?.classList.add("is-invalid");
    campo?.focus();

    mostrarAdvertencia("El porcentaje de descuento debe estar entre 0 y 100.");

    return false;
  }

  return true;
}

/**
 * Guarda la información comercial.
 */
async function guardarInformacionComercial() {
  if (!validarClienteGuardado()) {
    return;
  }

  if (estadoComercialCliente.guardando) {
    return;
  }

  if (!validarDatosComerciales()) {
    mostrarAdvertencia("Revise la información comercial capturada.");

    return;
  }

  const confirmado = await confirmarAccion(
    "¿Desea guardar la información comercial del cliente?",
    "Guardar información",
  );

  if (!confirmado) {
    return;
  }

  const boton = document.querySelector("#btnGuardarComercial");

  const contenidoOriginal = boton?.innerHTML || "";

  estadoComercialCliente.guardando = true;

  establecerEstadoBoton(boton, true, "Guardando...");

  try {
    const datos = obtenerDatosComerciales();
    const formData = new FormData();

    Object.entries(datos).forEach(function ([clave, valor]) {
      formData.append(clave, String(valor ?? ""));
    });

    const respuesta = await peticionJson(CLIENTES_ENDPOINTS.guardarComercial, {
      method: "POST",
      body: formData,
    });

    if (!respuesta.status) {
      throw new Error(
        respuesta.message || "No fue posible guardar la información comercial.",
      );
    }

    mostrarExito(
      respuesta.message || "La información comercial se guardó correctamente.",
    );

    abrirPestana("#tab-bancos");
  } catch (error) {
    console.error("Error al guardar información comercial:", error);

    mostrarError(
      error.message || "Ocurrió un error al guardar la información comercial.",
    );
  } finally {
    estadoComercialCliente.guardando = false;

    restaurarBoton(boton, contenidoOriginal);
  }
}

/* ============================================================
 * 18. CUENTAS BANCARIAS
 * ============================================================ */

/**
 * Estado interno de la sección Bancos.
 */
const estadoBancosCliente = {
  inicializado: false,
  guardando: false,
  cargando: false,
  idbancoActual: 0,
  registros: [],
};

/**
 * Inicializa la sección Bancos.
 */
function configurarSeccionBancos() {
  const contenedor = document.querySelector(SELECTORES_CLIENTE.tabBancos);

  if (!contenedor || estadoBancosCliente.inicializado) {
    return;
  }

  crearAccionesBancos();
  crearListadoBancos();
  configurarCamposBancarios();

  const botonTab = obtenerBotonTab("#tab-bancos");

  botonTab?.addEventListener("shown.bs.tab", function () {
    if (validarClienteGuardado()) {
      cargarBancosCliente();
    }
  });

  estadoBancosCliente.inicializado = true;
}

/**
 * Crea los botones para guardar y limpiar bancos.
 */
function crearAccionesBancos() {
  const contenedor = document.querySelector(SELECTORES_CLIENTE.tabBancos);

  if (!contenedor || document.querySelector("#accionesBancos")) {
    return;
  }

  const acciones = document.createElement("div");

  acciones.id = "accionesBancos";
  acciones.className = "d-flex justify-content-end gap-2 mt-4";

  acciones.innerHTML = `
        <button
            type="button"
            class="btn btn-light"
            id="btnNuevoBanco">

            <i class="ri-add-line me-1"></i>
            Nueva cuenta
        </button>

        <button
            type="button"
            class="btn btn-primary btn-label"
            id="btnGuardarBanco">

            <i
                class="ri-save-3-line label-icon align-middle fs-16 me-2">
            </i>

            Guardar cuenta bancaria
        </button>
    `;

  contenedor.appendChild(acciones);

  document
    .querySelector("#btnGuardarBanco")
    ?.addEventListener("click", guardarBanco);

  document
    .querySelector("#btnNuevoBanco")
    ?.addEventListener("click", limpiarFormularioBanco);
}

/**
 * Crea el listado visual de cuentas bancarias.
 */
function crearListadoBancos() {
  const contenedor = document.querySelector(SELECTORES_CLIENTE.tabBancos);

  if (!contenedor || document.querySelector("#listadoBancos")) {
    return;
  }

  const listado = document.createElement("div");

  listado.id = "listadoBancos";
  listado.className = "mt-4";

  listado.innerHTML = `
        <hr>

        <div class="section-title">
            Cuentas bancarias registradas
        </div>

        <div
            class="row g-3"
            id="contenedorListadoBancos">
        </div>
    `;

  contenedor.appendChild(listado);

  listado.addEventListener("click", manejarAccionesBanco);
}

/**
 * Configura validaciones de campos bancarios.
 */
function configurarCamposBancarios() {
  const cuenta = obtenerCampoBanco("numero_cuenta");

  const clabe = obtenerCampoBanco("clabe");

  cuenta?.addEventListener("input", function () {
    cuenta.value = cuenta.value.replace(/\D/g, "");
  });

  clabe?.addEventListener("input", function () {
    clabe.value = clabe.value.replace(/\D/g, "").slice(0, 18);

    clabe.classList.remove("is-valid", "is-invalid");
  });

  clabe?.addEventListener("blur", validarClabeBanco);
}

/**
 * Obtiene un campo de la pestaña Bancos.
 *
 * @param {string} nombre
 * @returns {HTMLElement|null}
 */
function obtenerCampoBanco(nombre) {
  return document.querySelector(
    `${SELECTORES_CLIENTE.tabBancos} [name="${nombre}"]`,
  );
}

/**
 * Obtiene los datos bancarios capturados.
 *
 * @returns {object}
 */
function obtenerDatosBanco() {
  const valor = function (nombre) {
    const campo = obtenerCampoBanco(nombre);

    if (!campo) {
      return "";
    }

    if (campo.type === "checkbox") {
      return campo.checked ? "1" : "0";
    }

    return campo.value?.trim() || "";
  };

  return {
    idbanco: estadoBancosCliente.idbancoActual,

    banco: valor("banco"),

    titular: valor("titular"),

    numero_cuenta: valor("numero_cuenta"),

    clabe: valor("clabe"),

    moneda: valor("moneda") || "MXN",

    sucursal_bancaria: valor("sucursal_bancaria"),

    tipo_cuenta: valor("tipo_cuenta"),

    es_principal: valor("es_principal") || "0",

    estado: valor("estado") || "2",
  };
}

/**
 * Valida CLABE de 18 dígitos.
 *
 * Además utiliza el algoritmo de validación de dígito verificador.
 *
 * @returns {boolean}
 */
function validarClabeBanco() {
  const inputClabe = obtenerCampoBanco("clabe");

  if (!inputClabe || !inputClabe.value) {
    return true;
  }

  const clabe = inputClabe.value.replace(/\D/g, "");

  inputClabe.value = clabe;

  if (!/^\d{18}$/.test(clabe)) {
    inputClabe.classList.add("is-invalid");
    inputClabe.classList.remove("is-valid");

    return false;
  }

  const factores = [3, 7, 1];
  let suma = 0;

  for (let indice = 0; indice < 17; indice++) {
    suma += (Number(clabe[indice]) * factores[indice % 3]) % 10;
  }

  const digitoEsperado = (10 - (suma % 10)) % 10;

  const valida = digitoEsperado === Number(clabe[17]);

  inputClabe.classList.toggle("is-valid", valida);

  inputClabe.classList.toggle("is-invalid", !valida);

  return valida;
}

/**
 * Valida el formulario bancario.
 *
 * @returns {boolean}
 */
function validarDatosBanco() {
  const contenedor = document.querySelector(SELECTORES_CLIENTE.tabBancos);

  if (!contenedor) {
    return false;
  }

  if (!validarContenedor(contenedor)) {
    return false;
  }

  const datos = obtenerDatosBanco();

  if (datos.numero_cuenta && datos.numero_cuenta.length < 6) {
    const campo = obtenerCampoBanco("numero_cuenta");

    campo?.classList.add("is-invalid");
    campo?.focus();

    mostrarAdvertencia("El número de cuenta debe contener al menos 6 dígitos.");

    return false;
  }

  if (datos.clabe && !validarClabeBanco()) {
    obtenerCampoBanco("clabe")?.focus();

    mostrarAdvertencia("La CLABE capturada no es válida.");

    return false;
  }

  if (!datos.numero_cuenta && !datos.clabe) {
    mostrarAdvertencia("Capture el número de cuenta o la CLABE bancaria.");

    obtenerCampoBanco("numero_cuenta")?.focus();

    return false;
  }

  return true;
}

/**
 * Guarda o actualiza una cuenta bancaria.
 */
async function guardarBanco() {
  if (!validarClienteGuardado()) {
    return;
  }

  if (estadoBancosCliente.guardando) {
    return;
  }

  if (!validarDatosBanco()) {
    return;
  }

  const datos = obtenerDatosBanco();

  const confirmado = await confirmarAccion(
    datos.idbanco > 0
      ? "¿Desea actualizar esta cuenta bancaria?"
      : "¿Desea guardar esta cuenta bancaria?",
    datos.idbanco > 0 ? "Actualizar cuenta" : "Guardar cuenta",
  );

  if (!confirmado) {
    return;
  }

  const boton = document.querySelector("#btnGuardarBanco");

  const contenidoOriginal = boton?.innerHTML || "";

  estadoBancosCliente.guardando = true;

  establecerEstadoBoton(boton, true, "Guardando...");

  try {
    const formData = new FormData();

    formData.append("idcliente", String(obtenerIdCliente()));

    Object.entries(datos).forEach(function ([clave, valor]) {
      formData.append(clave, String(valor ?? ""));
    });

    const respuesta = await peticionJson(CLIENTES_ENDPOINTS.guardarBanco, {
      method: "POST",
      body: formData,
    });

    if (!respuesta.status) {
      throw new Error(
        respuesta.message || "No fue posible guardar la cuenta bancaria.",
      );
    }

    mostrarExito(
      respuesta.message || "La cuenta bancaria se guardó correctamente.",
    );

    limpiarFormularioBanco();

    await cargarBancosCliente(true);
  } catch (error) {
    console.error("Error al guardar cuenta bancaria:", error);

    mostrarError(
      error.message || "Ocurrió un error al guardar la cuenta bancaria.",
    );
  } finally {
    estadoBancosCliente.guardando = false;

    restaurarBoton(boton, contenidoOriginal);
  }
}

/**
 * Consulta las cuentas bancarias registradas.
 *
 * @param {boolean} forzar
 */
async function cargarBancosCliente(forzar = false) {
  const idcliente = obtenerIdCliente();

  if (idcliente <= 0 || estadoBancosCliente.cargando) {
    return;
  }

  if (estadoBancosCliente.registros.length > 0 && !forzar) {
    return;
  }

  const contenedor = document.querySelector("#contenedorListadoBancos");

  if (!contenedor) {
    return;
  }

  estadoBancosCliente.cargando = true;

  contenedor.innerHTML = `
        <div class="col-12 text-center py-4">
            <span
                class="spinner-border spinner-border-sm me-2">
            </span>

            Cargando cuentas bancarias...
        </div>
    `;

  try {
    const url =
      `${CLIENTES_ENDPOINTS.listarBancos}/` + encodeURIComponent(idcliente);

    const respuesta = await peticionJson(url, {
      method: "GET",
    });

    if (!respuesta.status) {
      throw new Error(
        respuesta.message || "No fue posible consultar las cuentas bancarias.",
      );
    }

    estadoBancosCliente.registros = Array.isArray(respuesta.data)
      ? respuesta.data
      : Array.isArray(respuesta.data?.bancos)
        ? respuesta.data.bancos
        : [];

    renderizarBancos();
  } catch (error) {
    console.error("Error al cargar bancos:", error);

    contenedor.innerHTML = `
            <div class="col-12 text-center text-danger py-4">
                ${escaparHtml(
                  error.message ||
                    "No fue posible cargar las cuentas bancarias.",
                )}
            </div>
        `;
  } finally {
    estadoBancosCliente.cargando = false;
  }
}

/**
 * Renderiza las cuentas bancarias.
 */
function renderizarBancos() {
  const contenedor = document.querySelector("#contenedorListadoBancos");

  if (!contenedor) {
    return;
  }

  if (estadoBancosCliente.registros.length === 0) {
    contenedor.innerHTML = `
            <div class="col-12 text-center text-muted py-4">
                <i class="ri-bank-line fs-3 d-block mb-2"></i>
                No hay cuentas bancarias registradas.
            </div>
        `;

    actualizarContadorBancos();

    return;
  }

  contenedor.innerHTML = estadoBancosCliente.registros
    .map(function (banco) {
      const idbanco = Number(banco.idbanco || banco.id || 0);

      const clabeProtegida = protegerCuentaBancaria(
        banco.clabe || banco.numero_cuenta || "",
      );

      return `
                    <div class="col-lg-6">
                        <div class="card border shadow-none h-100">
                            <div class="card-body">
                                <div
                                    class="d-flex justify-content-between gap-3">

                                    <div>
                                        <div class="d-flex gap-2 align-items-center">
                                            <h6 class="mb-0">
                                                ${escaparHtml(
                                                  banco.banco || "",
                                                )}
                                            </h6>

                                            ${
                                              String(banco.es_principal) === "1"
                                                ? `
                                                        <span
                                                            class="badge bg-success-subtle text-success">
                                                            Principal
                                                        </span>
                                                    `
                                                : ""
                                            }
                                        </div>

                                        <p class="text-muted mb-1 mt-2">
                                            ${escaparHtml(banco.titular || "")}
                                        </p>

                                        <div class="fw-medium">
                                            ${escaparHtml(clabeProtegida)}
                                        </div>

                                        <small class="text-muted">
                                            ${escaparHtml(
                                              banco.tipo_cuenta || "",
                                            )}
                                            ·
                                            ${escaparHtml(
                                              banco.moneda || "MXN",
                                            )}
                                        </small>
                                    </div>

                                    <div class="text-nowrap">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-soft-info btn-editar-banco"
                                            data-id="${idbanco}"
                                            title="Editar">

                                            <i class="ri-edit-line"></i>
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-soft-danger btn-eliminar-banco"
                                            data-id="${idbanco}"
                                            title="Eliminar">

                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
    })
    .join("");

  actualizarContadorBancos();
}

/**
 * Maneja editar y eliminar cuentas.
 *
 * @param {MouseEvent} event
 */
function manejarAccionesBanco(event) {
  const botonEditar = event.target.closest(".btn-editar-banco");

  if (botonEditar) {
    editarBanco(Number(botonEditar.dataset.id));

    return;
  }

  const botonEliminar = event.target.closest(".btn-eliminar-banco");

  if (botonEliminar) {
    eliminarBanco(Number(botonEliminar.dataset.id));
  }
}

/**
 * Carga una cuenta en el formulario.
 *
 * @param {number} idbanco
 */
function editarBanco(idbanco) {
  const banco = estadoBancosCliente.registros.find(function (registro) {
    return Number(registro.idbanco || registro.id) === idbanco;
  });

  if (!banco) {
    mostrarError("No se encontró la cuenta bancaria seleccionada.");

    return;
  }

  estadoBancosCliente.idbancoActual = idbanco;

  const campos = {
    banco: banco.banco,
    titular: banco.titular,
    numero_cuenta: banco.numero_cuenta,
    clabe: banco.clabe,
    moneda: banco.moneda,
    sucursal_bancaria: banco.sucursal_bancaria,
    tipo_cuenta: banco.tipo_cuenta,
    estado: banco.estado,
  };

  Object.entries(campos).forEach(function ([nombre, valor]) {
    const campo = obtenerCampoBanco(nombre);

    if (campo) {
      campo.value = valor ?? "";
    }
  });

  const principal = obtenerCampoBanco("es_principal");

  if (principal) {
    principal.checked = String(banco.es_principal) === "1";
  }

  const boton = document.querySelector("#btnGuardarBanco");

  if (boton) {
    boton.innerHTML = `
            <i
                class="ri-save-3-line label-icon align-middle fs-16 me-2">
            </i>

            Actualizar cuenta bancaria
        `;
  }

  document.querySelector(SELECTORES_CLIENTE.tabBancos)?.scrollIntoView({
    behavior: "smooth",
    block: "start",
  });
}

/**
 * Elimina una cuenta bancaria.
 *
 * @param {number} idbanco
 */
async function eliminarBanco(idbanco) {
  if (idbanco <= 0) {
    return;
  }

  const confirmado = await confirmarAccion(
    "¿Desea eliminar esta cuenta bancaria?",
    "Eliminar cuenta",
  );

  if (!confirmado) {
    return;
  }

  try {
    const formData = new FormData();

    formData.append("idcliente", String(obtenerIdCliente()));

    formData.append("idbanco", String(idbanco));

    const respuesta = await peticionJson(CLIENTES_ENDPOINTS.eliminarBanco, {
      method: "POST",
      body: formData,
    });

    if (!respuesta.status) {
      throw new Error(
        respuesta.message || "No fue posible eliminar la cuenta bancaria.",
      );
    }

    if (estadoBancosCliente.idbancoActual === idbanco) {
      limpiarFormularioBanco();
    }

    mostrarExito(
      respuesta.message || "La cuenta bancaria se eliminó correctamente.",
    );

    await cargarBancosCliente(true);
  } catch (error) {
    console.error("Error al eliminar cuenta bancaria:", error);

    mostrarError(
      error.message || "Ocurrió un error al eliminar la cuenta bancaria.",
    );
  }
}

/**
 * Limpia el formulario bancario.
 */
function limpiarFormularioBanco() {
  estadoBancosCliente.idbancoActual = 0;

  const nombres = [
    "banco",
    "titular",
    "numero_cuenta",
    "clabe",
    "moneda",
    "sucursal_bancaria",
    "tipo_cuenta",
    "estado",
  ];

  nombres.forEach(function (nombre) {
    const campo = obtenerCampoBanco(nombre);

    if (!campo) {
      return;
    }

    if (nombre === "moneda") {
      campo.value = "MXN";
    } else if (nombre === "estado") {
      campo.value = "2";
    } else {
      campo.value = "";
    }

    campo.classList.remove("is-valid", "is-invalid");
  });

  const principal = obtenerCampoBanco("es_principal");

  if (principal) {
    principal.checked = false;
  }

  const boton = document.querySelector("#btnGuardarBanco");

  if (boton) {
    boton.innerHTML = `
            <i
                class="ri-save-3-line label-icon align-middle fs-16 me-2">
            </i>

            Guardar cuenta bancaria
        `;
  }
}

/**
 * Oculta la mayor parte de una cuenta o CLABE.
 *
 * @param {string} valor
 * @returns {string}
 */
function protegerCuentaBancaria(valor) {
  const texto = String(valor || "");

  if (texto.length <= 4) {
    return texto;
  }

  return `${"•".repeat(Math.max(texto.length - 4, 4))}${texto.slice(-4)}`;
}

/**
 * Actualiza el contador de bancos.
 */
function actualizarContadorBancos() {
  const botonTab = obtenerBotonTab("#tab-bancos");

  if (!botonTab) {
    return;
  }

  const total = estadoBancosCliente.registros.length;

  let contador = botonTab.querySelector(".tab-counter-bancos");

  if (!contador) {
    contador = document.createElement("span");

    contador.className =
      "badge bg-primary-subtle text-primary tab-counter tab-counter-bancos";

    botonTab.appendChild(contador);
  }

  contador.textContent = String(total);

  contador.style.display = total > 0 ? "" : "none";
}

/* ============================================================
 * 19. DOCUMENTOS
 * ============================================================ */

/**
 * Configuración permitida para documentos.
 */
const CONFIGURACION_DOCUMENTOS = {
  extensionesPermitidas: [
    "pdf",
    "jpg",
    "jpeg",
    "png",
    "doc",
    "docx",
    "xls",
    "xlsx",
  ],

  tiposMimePermitidos: [
    "application/pdf",
    "image/jpeg",
    "image/png",
    "application/msword",
    "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    "application/vnd.ms-excel",
    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
  ],

  tamanoMaximoBytes: 10 * 1024 * 1024,
};

/**
 * Estado interno de Documentos.
 */
const estadoDocumentosCliente = {
  inicializado: false,
  guardando: false,
  cargando: false,
  archivoSeleccionado: null,
  registros: [],
};

/**
 * Inicializa la sección Documentos.
 */
function configurarSeccionDocumentos() {
  const contenedor = document.querySelector(SELECTORES_CLIENTE.tabDocumentos);

  if (!contenedor || estadoDocumentosCliente.inicializado) {
    return;
  }

  crearAccionesDocumentos();
  crearListadoDocumentos();
  configurarCampoArchivoDocumento();

  const botonTab = obtenerBotonTab("#tab-documentos");

  botonTab?.addEventListener("shown.bs.tab", function () {
    if (validarClienteGuardado()) {
      cargarDocumentosCliente();
    }
  });

  estadoDocumentosCliente.inicializado = true;
}

/**
 * Crea el botón para subir documentos.
 */
function crearAccionesDocumentos() {
  const contenedor = document.querySelector(SELECTORES_CLIENTE.tabDocumentos);

  if (!contenedor || document.querySelector("#accionesDocumentos")) {
    return;
  }

  const acciones = document.createElement("div");

  acciones.id = "accionesDocumentos";
  acciones.className = "d-flex justify-content-end gap-2 mt-4";

  acciones.innerHTML = `
        <button
            type="button"
            class="btn btn-light"
            id="btnLimpiarDocumento">

            <i class="ri-eraser-line me-1"></i>
            Limpiar
        </button>

        <button
            type="button"
            class="btn btn-primary btn-label"
            id="btnGuardarDocumento">

            <i
                class="ri-upload-cloud-2-line label-icon align-middle fs-16 me-2">
            </i>

            Subir documento
        </button>
    `;

  contenedor.appendChild(acciones);

  document
    .querySelector("#btnGuardarDocumento")
    ?.addEventListener("click", guardarDocumento);

  document
    .querySelector("#btnLimpiarDocumento")
    ?.addEventListener("click", limpiarFormularioDocumento);
}

/**
 * Crea el listado de documentos.
 */
function crearListadoDocumentos() {
  const contenedor = document.querySelector(SELECTORES_CLIENTE.tabDocumentos);

  if (!contenedor || document.querySelector("#listadoDocumentos")) {
    return;
  }

  const listado = document.createElement("div");

  listado.id = "listadoDocumentos";
  listado.className = "mt-4";

  listado.innerHTML = `
        <hr>

        <div class="section-title">
            Documentos registrados
        </div>

        <div
            class="table-responsive"
            id="contenedorListadoDocumentos">
        </div>
    `;

  contenedor.appendChild(listado);

  listado.addEventListener("click", manejarAccionesDocumento);
}

/**
 * Configura el input file.
 */
function configurarCampoArchivoDocumento() {
  const inputArchivo =
    obtenerCampoDocumento("archivo") || obtenerCampoDocumento("documento");

  if (!inputArchivo) {
    return;
  }

  inputArchivo.setAttribute(
    "accept",
    ".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx",
  );

  inputArchivo.addEventListener("change", function () {
    const archivo = inputArchivo.files?.[0] || null;

    if (!archivo) {
      estadoDocumentosCliente.archivoSeleccionado = null;

      return;
    }

    if (!validarArchivoDocumento(archivo)) {
      inputArchivo.value = "";

      estadoDocumentosCliente.archivoSeleccionado = null;

      return;
    }

    estadoDocumentosCliente.archivoSeleccionado = archivo;

    mostrarInformacionArchivo(archivo);
  });
}

/**
 * Obtiene un campo dentro de Documentos.
 *
 * @param {string} nombre
 * @returns {HTMLElement|null}
 */
function obtenerCampoDocumento(nombre) {
  return document.querySelector(
    `${SELECTORES_CLIENTE.tabDocumentos} [name="${nombre}"]`,
  );
}

/**
 * Valida extensión y tamaño del documento.
 *
 * @param {File} archivo
 * @returns {boolean}
 */
function validarArchivoDocumento(archivo) {
  if (!(archivo instanceof File)) {
    mostrarAdvertencia("Seleccione un archivo válido.");

    return false;
  }

  const extension = obtenerExtensionArchivo(archivo.name);

  if (!CONFIGURACION_DOCUMENTOS.extensionesPermitidas.includes(extension)) {
    mostrarAdvertencia(
      "El formato del archivo no está permitido. Utilice PDF, imagen, Word o Excel.",
    );

    return false;
  }

  if (
    archivo.type &&
    !CONFIGURACION_DOCUMENTOS.tiposMimePermitidos.includes(archivo.type)
  ) {
    mostrarAdvertencia("El tipo de contenido del archivo no está permitido.");

    return false;
  }

  if (archivo.size > CONFIGURACION_DOCUMENTOS.tamanoMaximoBytes) {
    mostrarAdvertencia(
      "El archivo supera el tamaño máximo permitido de 10 MB.",
    );

    return false;
  }

  return true;
}

/**
 * Muestra la información del archivo seleccionado.
 *
 * @param {File} archivo
 */
function mostrarInformacionArchivo(archivo) {
  let contenedor = document.querySelector("#informacionArchivoSeleccionado");

  if (!contenedor) {
    contenedor = document.createElement("div");

    contenedor.id = "informacionArchivoSeleccionado";

    const inputArchivo =
      obtenerCampoDocumento("archivo") || obtenerCampoDocumento("documento");

    inputArchivo?.parentElement?.appendChild(contenedor);
  }

  if (!contenedor) {
    return;
  }

  contenedor.className = "alert alert-light border mt-2 mb-0";

  contenedor.innerHTML = `
        <div class="d-flex align-items-center gap-2">
            <i class="${obtenerIconoDocumento(
              obtenerExtensionArchivo(archivo.name),
            )} fs-4"></i>

            <div class="overflow-hidden">
                <div class="fw-medium text-truncate">
                    ${escaparHtml(archivo.name)}
                </div>

                <small class="text-muted">
                    ${formatearTamanoArchivo(archivo.size)}
                </small>
            </div>
        </div>
    `;
}

/**
 * Obtiene los datos del documento.
 *
 * @returns {object}
 */
function obtenerDatosDocumento() {
  const valor = function (nombre) {
    return obtenerCampoDocumento(nombre)?.value?.trim() || "";
  };

  return {
    tipo_documento: valor("tipo_documento"),

    nombre_documento: valor("nombre_documento"),

    descripcion: valor("descripcion"),

    fecha_vigencia: valor("fecha_vigencia"),

    requiere_vigencia: valor("requiere_vigencia"),

    estado: valor("estado") || "2",
  };
}

/**
 * Valida los datos antes de subir un documento.
 *
 * @returns {boolean}
 */
function validarDatosDocumento() {
  const contenedor = document.querySelector(SELECTORES_CLIENTE.tabDocumentos);

  if (!contenedor) {
    return false;
  }

  /*
   * El campo de archivo se valida manualmente para evitar conflictos
   * en ediciones o controles de arrastrar y soltar.
   */
  const campos = Array.from(
    contenedor.querySelectorAll('input:not([type="file"]), select, textarea'),
  ).filter(function (campo) {
    return !campo.disabled && esElementoVisible(campo);
  });

  let valido = true;
  let primerInvalido = null;

  campos.forEach(function (campo) {
    campo.classList.remove("is-valid", "is-invalid");

    if (!campo.checkValidity()) {
      campo.classList.add("is-invalid");
      valido = false;

      primerInvalido = primerInvalido || campo;
    } else if (campo.required && campo.value) {
      campo.classList.add("is-valid");
    }
  });

  if (!valido) {
    primerInvalido?.focus();

    return false;
  }

  const archivo = estadoDocumentosCliente.archivoSeleccionado;

  if (!archivo) {
    mostrarAdvertencia("Seleccione el documento que desea subir.");

    return false;
  }

  return validarArchivoDocumento(archivo);
}

/**
 * Guarda un documento.
 */
async function guardarDocumento() {
  if (!validarClienteGuardado()) {
    return;
  }

  if (estadoDocumentosCliente.guardando) {
    return;
  }

  if (!validarDatosDocumento()) {
    return;
  }

  const confirmado = await confirmarAccion(
    "¿Desea subir este documento?",
    "Subir documento",
  );

  if (!confirmado) {
    return;
  }

  const boton = document.querySelector("#btnGuardarDocumento");

  const contenidoOriginal = boton?.innerHTML || "";

  estadoDocumentosCliente.guardando = true;

  establecerEstadoBoton(boton, true, "Subiendo...");

  try {
    const datos = obtenerDatosDocumento();
    const formData = new FormData();

    formData.append("idcliente", String(obtenerIdCliente()));

    Object.entries(datos).forEach(function ([clave, valor]) {
      formData.append(clave, String(valor ?? ""));
    });

    formData.append("archivo", estadoDocumentosCliente.archivoSeleccionado);

    const respuesta = await peticionJson(CLIENTES_ENDPOINTS.guardarDocumento, {
      method: "POST",
      body: formData,
    });

    if (!respuesta.status) {
      throw new Error(
        respuesta.message || "No fue posible subir el documento.",
      );
    }

    mostrarExito(respuesta.message || "El documento se subió correctamente.");

    limpiarFormularioDocumento();

    await cargarDocumentosCliente(true);
  } catch (error) {
    console.error("Error al subir documento:", error);

    mostrarError(error.message || "Ocurrió un error al subir el documento.");
  } finally {
    estadoDocumentosCliente.guardando = false;

    restaurarBoton(boton, contenidoOriginal);
  }
}

/**
 * Consulta los documentos registrados.
 *
 * @param {boolean} forzar
 */
async function cargarDocumentosCliente(forzar = false) {
  const idcliente = obtenerIdCliente();

  if (idcliente <= 0 || estadoDocumentosCliente.cargando) {
    return;
  }

  if (estadoDocumentosCliente.registros.length > 0 && !forzar) {
    return;
  }

  const contenedor = document.querySelector("#contenedorListadoDocumentos");

  if (!contenedor) {
    return;
  }

  estadoDocumentosCliente.cargando = true;

  contenedor.innerHTML = `
        <div class="text-center py-4">
            <span
                class="spinner-border spinner-border-sm me-2">
            </span>

            Cargando documentos...
        </div>
    `;

  try {
    const url =
      `${CLIENTES_ENDPOINTS.listarDocumentos}/` + encodeURIComponent(idcliente);

    const respuesta = await peticionJson(url, {
      method: "GET",
    });

    if (!respuesta.status) {
      throw new Error(
        respuesta.message || "No fue posible consultar los documentos.",
      );
    }

    estadoDocumentosCliente.registros = Array.isArray(respuesta.data)
      ? respuesta.data
      : Array.isArray(respuesta.data?.documentos)
        ? respuesta.data.documentos
        : [];

    renderizarDocumentos();
  } catch (error) {
    console.error("Error al cargar documentos:", error);

    contenedor.innerHTML = `
            <div class="text-center text-danger py-4">
                ${escaparHtml(
                  error.message || "No fue posible cargar los documentos.",
                )}
            </div>
        `;
  } finally {
    estadoDocumentosCliente.cargando = false;
  }
}

/**
 * Renderiza la tabla de documentos.
 */
function renderizarDocumentos() {
  const contenedor = document.querySelector("#contenedorListadoDocumentos");

  if (!contenedor) {
    return;
  }

  if (estadoDocumentosCliente.registros.length === 0) {
    contenedor.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="ri-file-list-3-line fs-3 d-block mb-2"></i>
                No hay documentos registrados.
            </div>
        `;

    actualizarContadorDocumentos();

    return;
  }

  const filas = estadoDocumentosCliente.registros
    .map(function (documento) {
      const iddocumento = Number(documento.iddocumento || documento.id || 0);

      const nombreArchivo =
        documento.nombre_archivo ||
        documento.archivo ||
        documento.nombre_documento ||
        "";

      const extension =
        documento.extension || obtenerExtensionArchivo(nombreArchivo);

      const urlDocumento =
        documento.url || documento.ruta_publica || documento.ruta || "";

      return `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i
                                    class="${obtenerIconoDocumento(
                                      extension,
                                    )} fs-4">
                                </i>

                                <div>
                                    <div class="fw-medium">
                                        ${escaparHtml(
                                          documento.nombre_documento ||
                                            nombreArchivo,
                                        )}
                                    </div>

                                    <small class="text-muted">
                                        ${escaparHtml(nombreArchivo)}
                                    </small>
                                </div>
                            </div>
                        </td>

                        <td>
                            ${escaparHtml(documento.tipo_documento || "")}
                        </td>

                        <td>
                            ${
                              documento.tamano
                                ? formatearTamanoArchivo(
                                    Number(documento.tamano),
                                  )
                                : "—"
                            }
                        </td>

                        <td>
                            ${escaparHtml(
                              documento.fecha_vigencia || "Sin vigencia",
                            )}
                        </td>

                        <td>
                            <span
                                class="badge ${
                                  String(documento.estado) === "2"
                                    ? "bg-success-subtle text-success"
                                    : "bg-secondary-subtle text-secondary"
                                }">

                                ${
                                  String(documento.estado) === "2"
                                    ? "Activo"
                                    : "Inactivo"
                                }
                            </span>
                        </td>

                        <td class="text-end text-nowrap">
                            ${
                              urlDocumento
                                ? `
                                        <a
                                            href="${escaparAtributo(
                                              urlDocumento,
                                            )}"
                                            class="btn btn-sm btn-soft-primary"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            title="Ver documento">

                                            <i class="ri-eye-line"></i>
                                        </a>

                                        <a
                                            href="${escaparAtributo(
                                              urlDocumento,
                                            )}"
                                            class="btn btn-sm btn-soft-success"
                                            download
                                            title="Descargar documento">

                                            <i class="ri-download-2-line"></i>
                                        </a>
                                    `
                                : ""
                            }

                            <button
                                type="button"
                                class="btn btn-sm btn-soft-danger btn-eliminar-documento"
                                data-id="${iddocumento}"
                                title="Eliminar documento">

                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </td>
                    </tr>
                `;
    })
    .join("");

  contenedor.innerHTML = `
        <table
            class="table table-hover align-middle mb-0">

            <thead class="table-light">
                <tr>
                    <th>Documento</th>
                    <th>Tipo</th>
                    <th>Tamaño</th>
                    <th>Vigencia</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody>
                ${filas}
            </tbody>
        </table>
    `;

  actualizarContadorDocumentos();
}

/**
 * Maneja acciones de documentos.
 *
 * @param {MouseEvent} event
 */
function manejarAccionesDocumento(event) {
  const botonEliminar = event.target.closest(".btn-eliminar-documento");

  if (botonEliminar) {
    eliminarDocumento(Number(botonEliminar.dataset.id));
  }
}

/**
 * Elimina un documento.
 *
 * @param {number} iddocumento
 */
async function eliminarDocumento(iddocumento) {
  if (iddocumento <= 0) {
    return;
  }

  const confirmado = await confirmarAccion(
    "¿Desea eliminar este documento?",
    "Eliminar documento",
  );

  if (!confirmado) {
    return;
  }

  try {
    const formData = new FormData();

    formData.append("idcliente", String(obtenerIdCliente()));

    formData.append("iddocumento", String(iddocumento));

    const respuesta = await peticionJson(CLIENTES_ENDPOINTS.eliminarDocumento, {
      method: "POST",
      body: formData,
    });

    if (!respuesta.status) {
      throw new Error(
        respuesta.message || "No fue posible eliminar el documento.",
      );
    }

    mostrarExito(respuesta.message || "El documento se eliminó correctamente.");

    await cargarDocumentosCliente(true);
  } catch (error) {
    console.error("Error al eliminar documento:", error);

    mostrarError(error.message || "Ocurrió un error al eliminar el documento.");
  }
}

/**
 * Limpia el formulario de documentos.
 */
function limpiarFormularioDocumento() {
  const nombres = [
    "tipo_documento",
    "nombre_documento",
    "descripcion",
    "fecha_vigencia",
    "requiere_vigencia",
    "estado",
  ];

  nombres.forEach(function (nombre) {
    const campo = obtenerCampoDocumento(nombre);

    if (!campo) {
      return;
    }

    if (campo.type === "checkbox") {
      campo.checked = false;
    } else if (nombre === "estado") {
      campo.value = "2";
    } else {
      campo.value = "";
    }

    campo.classList.remove("is-valid", "is-invalid");
  });

  const archivo =
    obtenerCampoDocumento("archivo") || obtenerCampoDocumento("documento");

  if (archivo) {
    archivo.value = "";
  }

  estadoDocumentosCliente.archivoSeleccionado = null;

  document.querySelector("#informacionArchivoSeleccionado")?.remove();
}

/**
 * Obtiene la extensión de un archivo.
 *
 * @param {string} nombre
 * @returns {string}
 */
function obtenerExtensionArchivo(nombre) {
  const partes = String(nombre || "")
    .toLowerCase()
    .split(".");

  return partes.length > 1 ? partes.pop() : "";
}

/**
 * Devuelve un icono según la extensión.
 *
 * @param {string} extension
 * @returns {string}
 */
function obtenerIconoDocumento(extension) {
  const ext = String(extension || "").toLowerCase();

  const iconos = {
    pdf: "ri-file-pdf-2-line text-danger",
    jpg: "ri-image-2-line text-primary",
    jpeg: "ri-image-2-line text-primary",
    png: "ri-image-2-line text-primary",
    doc: "ri-file-word-2-line text-primary",
    docx: "ri-file-word-2-line text-primary",
    xls: "ri-file-excel-2-line text-success",
    xlsx: "ri-file-excel-2-line text-success",
  };

  return iconos[ext] || "ri-file-3-line text-muted";
}

/**
 * Formatea el tamaño de un archivo.
 *
 * @param {number} bytes
 * @returns {string}
 */
function formatearTamanoArchivo(bytes) {
  const tamano = Number(bytes || 0);

  if (!Number.isFinite(tamano) || tamano <= 0) {
    return "0 B";
  }

  const unidades = ["B", "KB", "MB", "GB"];

  const indice = Math.min(
    Math.floor(Math.log(tamano) / Math.log(1024)),
    unidades.length - 1,
  );

  const valor = tamano / Math.pow(1024, indice);

  return `${valor.toFixed(indice === 0 ? 0 : 2)} ${unidades[indice]}`;
}

/**
 * Actualiza el contador de documentos.
 */
function actualizarContadorDocumentos() {
  const botonTab = obtenerBotonTab("#tab-documentos");

  if (!botonTab) {
    return;
  }

  const total = estadoDocumentosCliente.registros.length;

  let contador = botonTab.querySelector(".tab-counter-documentos");

  if (!contador) {
    contador = document.createElement("span");

    contador.className =
      "badge bg-primary-subtle text-primary tab-counter tab-counter-documentos";

    botonTab.appendChild(contador);
  }

  contador.textContent = String(total);

  contador.style.display = total > 0 ? "" : "none";
}

/* ============================================================
 * 20. FUNCIONES AUXILIARES DE LA PARTE 4
 * ============================================================ */

/**
 * Limpia un valor decimal.
 *
 * @param {string} valor
 * @param {number} decimales
 * @returns {string}
 */
function limpiarNumeroDecimal(valor, decimales = 2) {
  let texto = String(valor || "").replace(/[^\d.]/g, "");

  /*
   * Conserva únicamente el primer punto decimal.
   */
  const partes = texto.split(".");

  if (partes.length > 1) {
    texto = partes.shift() + "." + partes.join("");
  }

  const partesFinales = texto.split(".");

  if (partesFinales.length === 2) {
    partesFinales[1] = partesFinales[1].slice(0, decimales);

    texto = partesFinales.join(".");
  }

  return texto;
}

/**
 * Obtiene el valor booleano de checkbox, select o input.
 *
 * @param {HTMLElement} campo
 * @returns {boolean}
 */
function obtenerValorBooleanoCampo(campo) {
  if (!campo) {
    return false;
  }

  if (campo.type === "checkbox") {
    return campo.checked;
  }

  const valor = String(campo.value || "").toLowerCase();

  return ["1", "true", "si", "sí", "activo"].includes(valor);
}
