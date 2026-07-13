'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const formCliente = document.getElementById('formCliente');
    const tipoCliente = document.getElementById('idtipo_cliente');
    const tipoPersona = document.getElementById('tipo_persona');
    const inputRFC = document.getElementById('rfc');
    const btnValidarRFC = document.getElementById('btnValidarRFC');
    const rfcStatus = document.getElementById('rfcStatus');
    const inputCURP = document.getElementById('curp');
    const inputCLABE = document.getElementById('clabe');

    const clientSections = {
        DISTRIBUIDOR: document.getElementById('sectionDistribuidor'),
        INTERNO: document.getElementById('sectionInterno'),
        EXTERNO: document.getElementById('sectionExterno'),
        GUBERNAMENTAL: document.getElementById('sectionGubernamental')
    };

    function ocultarSeccionesDinamicas() {
        Object.values(clientSections).forEach(section => {
            section.style.display = 'none';

            section.querySelectorAll('.dynamic-required').forEach(input => {
                input.required = false;
            });
        });
    }

    function mostrarSeccionTipoCliente() {
        ocultarSeccionesDinamicas();

        const tipo = tipoCliente.value;

        if (clientSections[tipo]) {
            clientSections[tipo].style.display = 'block';

            clientSections[tipo]
                .querySelectorAll('.dynamic-required')
                .forEach(input => {
                    input.required = true;
                });
        }
    }

    function actualizarCamposPersona() {
        const personaFisicaFields = document.querySelectorAll('.persona-fisica-field');
        const isFisica = tipoPersona.value === 'FISICA';

        personaFisicaFields.forEach(field => {
            field.style.display = isFisica ? '' : 'none';
        });

        if (!isFisica && inputCURP) {
            inputCURP.value = '';
        }

        inputRFC.maxLength = isFisica ? 13 : 12;
        inputRFC.placeholder = isFisica
            ? 'Ej. CUCX900101AB1'
            : 'Ej. ABC010101AB1';

        limpiarEstadoRFC();
    }

    function limpiarEstadoRFC() {
        inputRFC.classList.remove('is-valid', 'is-invalid');
        rfcStatus.textContent = '';
        rfcStatus.className = 'rfc-status';
    }

    function normalizarRFC(rfc) {
        return rfc
            .trim()
            .toUpperCase()
            .replace(/\s+/g, '');
    }

    function validarRFC(rfc, persona) {
        const rfcNormalizado = normalizarRFC(rfc);

        const regexFisica = /^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/;
        const regexMoral = /^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$/;

        if (persona === 'FISICA') {
            return regexFisica.test(rfcNormalizado);
        }

        if (persona === 'MORAL') {
            return regexMoral.test(rfcNormalizado);
        }

        return false;
    }

    function mostrarResultadoRFC() {
        const persona = tipoPersona.value;
        const rfc = normalizarRFC(inputRFC.value);

        inputRFC.value = rfc;

        if (!persona) {
            inputRFC.classList.remove('is-valid');
            inputRFC.classList.add('is-invalid');

            rfcStatus.textContent = 'Primero selecciona el tipo de persona.';
            rfcStatus.className = 'rfc-status text-danger';
            return false;
        }

        if (!rfc) {
            inputRFC.classList.remove('is-valid');
            inputRFC.classList.add('is-invalid');

            rfcStatus.textContent = 'El RFC es obligatorio.';
            rfcStatus.className = 'rfc-status text-danger';
            return false;
        }

        if (validarRFC(rfc, persona)) {
            inputRFC.classList.remove('is-invalid');
            inputRFC.classList.add('is-valid');

            rfcStatus.textContent = 'El formato del RFC es válido.';
            rfcStatus.className = 'rfc-status text-success';
            return true;
        }

        inputRFC.classList.remove('is-valid');
        inputRFC.classList.add('is-invalid');

        rfcStatus.textContent = persona === 'FISICA'
            ? 'El RFC de persona física debe contener 13 caracteres.'
            : 'El RFC de persona moral debe contener 12 caracteres.';

        rfcStatus.className = 'rfc-status text-danger';
        return false;
    }

    function validarCURP(curp) {
        const regexCURP = /^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/;
        return regexCURP.test(curp.toUpperCase());
    }

    function validarCLABE(clabe) {
        return /^\d{18}$/.test(clabe);
    }

    function mostrarPrimerTabConError() {
        const invalidInput = formCliente.querySelector(':invalid');

        if (!invalidInput) {
            return;
        }

        const tabPane = invalidInput.closest('.tab-pane');

        if (tabPane) {
            const tabButton = document.querySelector(
                `[data-bs-target="#${tabPane.id}"]`
            );

            if (tabButton) {
                bootstrap.Tab.getOrCreateInstance(tabButton).show();
            }
        }

        setTimeout(() => {
            invalidInput.focus();
        }, 250);
    }

    tipoCliente.addEventListener('change', mostrarSeccionTipoCliente);
    tipoPersona.addEventListener('change', actualizarCamposPersona);

    inputRFC.addEventListener('input', event => {
        event.target.value = event.target.value
            .toUpperCase()
            .replace(/[^A-ZÑ&0-9]/g, '');

        limpiarEstadoRFC();
    });

    inputRFC.addEventListener('blur', () => {
        if (inputRFC.value.trim() !== '') {
            mostrarResultadoRFC();
        }
    });

    btnValidarRFC.addEventListener('click', mostrarResultadoRFC);

    if (inputCURP) {
        inputCURP.addEventListener('input', event => {
            event.target.value = event.target.value
                .toUpperCase()
                .replace(/[^A-Z0-9]/g, '');
        });

        inputCURP.addEventListener('blur', () => {
            const curp = inputCURP.value.trim();

            inputCURP.classList.remove('is-valid', 'is-invalid');

            if (!curp) {
                return;
            }

            if (validarCURP(curp)) {
                inputCURP.classList.add('is-valid');
            } else {
                inputCURP.classList.add('is-invalid');
            }
        });
    }

    if (inputCLABE) {
        inputCLABE.addEventListener('input', event => {
            event.target.value = event.target.value.replace(/\D/g, '');
        });

        inputCLABE.addEventListener('blur', () => {
            inputCLABE.classList.remove('is-valid', 'is-invalid');

            if (!inputCLABE.value) {
                return;
            }

            if (validarCLABE(inputCLABE.value)) {
                inputCLABE.classList.add('is-valid');
            } else {
                inputCLABE.classList.add('is-invalid');
            }
        });
    }

    document.querySelectorAll(
        'input[name="telefono"], input[name="celular"], input[name="codigo_postal"], #codigo_postal_fiscal'
    ).forEach(input => {
        input.addEventListener('input', event => {
            event.target.value = event.target.value.replace(/\D/g, '');
        });
    });

    formCliente.addEventListener('submit', async event => {
        event.preventDefault();

        formCliente.classList.add('was-validated');

        if (!formCliente.checkValidity()) {
            mostrarPrimerTabConError();

            Swal.fire({
                icon: 'warning',
                title: 'Información incompleta',
                text: 'Revisa los campos obligatorios antes de guardar.'
            });

            return;
        }

        if (!mostrarResultadoRFC()) {
            const fiscalTab = document.querySelector(
                '[data-bs-target="#tab-fiscal"]'
            );

            bootstrap.Tab.getOrCreateInstance(fiscalTab).show();
            inputRFC.focus();
            return;
        }

        if (
            tipoPersona.value === 'FISICA' &&
            inputCURP.value &&
            !validarCURP(inputCURP.value)
        ) {
            const fiscalTab = document.querySelector(
                '[data-bs-target="#tab-fiscal"]'
            );

            bootstrap.Tab.getOrCreateInstance(fiscalTab).show();

            Swal.fire({
                icon: 'warning',
                title: 'CURP inválida',
                text: 'Revisa el formato de la CURP.'
            });

            inputCURP.focus();
            return;
        }

        if (inputCLABE.value && !validarCLABE(inputCLABE.value)) {
            const bancoTab = document.querySelector(
                '[data-bs-target="#tab-bancos"]'
            );

            bootstrap.Tab.getOrCreateInstance(bancoTab).show();

            Swal.fire({
                icon: 'warning',
                title: 'CLABE inválida',
                text: 'La CLABE interbancaria debe contener exactamente 18 dígitos.'
            });

            inputCLABE.focus();
            return;
        }

        const formData = new FormData(formCliente);

        try {
            Swal.fire({
                title: 'Guardando cliente',
                text: 'Procesando información...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const response = await fetch(
                `${base_url}/cli_clientes/setCliente`,
                {
                    method: 'POST',
                    body: formData
                }
            );

            const textResponse = await response.text();

            let result;

            try {
                result = JSON.parse(textResponse);
            } catch (error) {
                throw new Error(
                    'La respuesta del servidor no tiene formato JSON.'
                );
            }

            if (!response.ok || !result.status) {
                throw new Error(
                    result.message || 'No fue posible guardar el cliente.'
                );
            }

            await Swal.fire({
                icon: 'success',
                title: 'Cliente registrado',
                text: result.message || 'La información se guardó correctamente.',
                confirmButtonText: 'Aceptar'
            });

            window.location.href = `${base_url}/cli_clientes`;

        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'No fue posible guardar',
                text: error.message
            });
        }
    });

    document.getElementById('btnLimpiar').addEventListener('click', () => {
        setTimeout(() => {
            ocultarSeccionesDinamicas();
            actualizarCamposPersona();
            limpiarEstadoRFC();
            formCliente.classList.remove('was-validated');
        }, 0);
    });

    ocultarSeccionesDinamicas();
    actualizarCamposPersona();
});







let contactoIndex = 0;
let sucursalIndex = 0;

document.getElementById('btnAgregarContacto').addEventListener('click', () => {
    const tbody = document.getElementById('tbodyContactos');

    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <input
                type="text"
                class="form-control"
                name="contactos[${contactoIndex}][nombre]"
                placeholder="Nombre completo"
                required>
        </td>
        <td>
            <input
                type="text"
                class="form-control"
                name="contactos[${contactoIndex}][puesto]"
                placeholder="Puesto">
        </td>
        <td>
            <input
                type="email"
                class="form-control"
                name="contactos[${contactoIndex}][correo]"
                placeholder="correo@empresa.com">
        </td>
        <td>
            <input
                type="tel"
                class="form-control"
                name="contactos[${contactoIndex}][telefono]"
                placeholder="7221234567">
        </td>
        <td>
            <select
                class="form-select"
                name="contactos[${contactoIndex}][tipo]">
                <option value="GENERAL">General</option>
                <option value="COMERCIAL">Comercial</option>
                <option value="FACTURACION">Facturación</option>
                <option value="COBRANZA">Cobranza</option>
                <option value="LOGISTICA">Logística</option>
            </select>
        </td>
        <td class="text-center">
            <input
                class="form-check-input"
                type="checkbox"
                name="contactos[${contactoIndex}][notificar]"
                value="1">
        </td>
        <td>
            <button
                type="button"
                class="btn btn-sm btn-soft-danger btnEliminarFila"
                title="Eliminar contacto">
                <i class="ri-delete-bin-line"></i>
            </button>
        </td>
    `;

    tbody.appendChild(row);
    contactoIndex++;
});

document.getElementById('btnAgregarSucursal').addEventListener('click', () => {
    const contenedor = document.getElementById('contenedorSucursales');

    const card = document.createElement('div');
    card.className = 'card border mb-3';

    card.innerHTML = `
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <strong>
                <i class="ri-building-2-line me-1"></i>
                Sucursal ${sucursalIndex + 1}
            </strong>

            <button
                type="button"
                class="btn btn-sm btn-soft-danger btnEliminarSucursal">
                <i class="ri-delete-bin-line"></i>
            </button>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-4 col-md-6">
                    <label class="form-label required">Nombre de sucursal</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="ri-store-line"></i>
                        </span>
                        <input
                            type="text"
                            class="form-control"
                            name="sucursales[${sucursalIndex}][nombre]"
                            placeholder="Ej. Sucursal Toluca"
                            required>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <label class="form-label">Responsable</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="ri-user-star-line"></i>
                        </span>
                        <input
                            type="text"
                            class="form-control"
                            name="sucursales[${sucursalIndex}][responsable]"
                            placeholder="Nombre del responsable">
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <label class="form-label">Teléfono</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="ri-phone-line"></i>
                        </span>
                        <input
                            type="tel"
                            class="form-control"
                            name="sucursales[${sucursalIndex}][telefono]"
                            placeholder="7221234567">
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <label class="form-label">Correo</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="ri-mail-line"></i>
                        </span>
                        <input
                            type="email"
                            class="form-control"
                            name="sucursales[${sucursalIndex}][correo]"
                            placeholder="sucursal@empresa.com">
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <label class="form-label">Código de sucursal</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="ri-barcode-box-line"></i>
                        </span>
                        <input
                            type="text"
                            class="form-control"
                            name="sucursales[${sucursalIndex}][codigo]"
                            placeholder="Ej. SUC-001">
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <label class="form-label">Estado</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="ri-toggle-line"></i>
                        </span>
                        <select
                            class="form-select"
                            name="sucursales[${sucursalIndex}][estado]">
                            <option value="1">Activa</option>
                            <option value="2">Inactiva</option>
                        </select>
                    </div>
                </div>

                <div class="col-lg-6">
                    <label class="form-label">Dirección</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="ri-map-pin-line"></i>
                        </span>
                        <input
                            type="text"
                            class="form-control"
                            name="sucursales[${sucursalIndex}][direccion]"
                            placeholder="Calle, número y colonia">
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Municipio</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="ri-building-line"></i>
                        </span>
                        <input
                            type="text"
                            class="form-control"
                            name="sucursales[${sucursalIndex}][municipio]"
                            placeholder="Municipio">
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Código postal</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="ri-mail-open-line"></i>
                        </span>
                        <input
                            type="text"
                            class="form-control"
                            name="sucursales[${sucursalIndex}][codigo_postal]"
                            placeholder="50000"
                            maxlength="5">
                    </div>
                </div>





                
            </div>
        </div>
    `;

    contenedor.appendChild(card);
    sucursalIndex++;
});

document.addEventListener('click', event => {
    const btnEliminarFila = event.target.closest('.btnEliminarFila');

    if (btnEliminarFila) {
        btnEliminarFila.closest('tr').remove();
    }

    const btnEliminarSucursal = event.target.closest('.btnEliminarSucursal');

    if (btnEliminarSucursal) {
        btnEliminarSucursal.closest('.card').remove();
    }
});
