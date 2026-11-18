'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const idclientePagina = document.getElementById('idclientePagina').value;

    const form = document.getElementById('formAccesoCliente');
    const loader = document.getElementById('loaderCliente');

    const inputIdCliente = document.getElementById('idcliente');
    const inputIdUsuario = document.getElementById('idusuario_acceso');
    const inputUsuario = document.getElementById('usuario_acceso');
    const inputCorreo = document.getElementById('correo_acceso');
    const inputPassword = document.getElementById('password_temporal');
    const inputLiga = document.getElementById('liga_acceso');
    const inputDobleAuth = document.getElementById('doble_autenticacion');

    const tbodyLogs = document.getElementById('tbodyLogs');

    let logs = [];
    let logsCargados = false;
    let enviosCredenciales = [];
    let consultaHistorico = null;
    let bibliotecaExcel = null;
    const tbodyEnvios = document.getElementById('tbodyEnviosCredenciales');
    const botonExportar = document.getElementById('btnExportarAccesos');
    const columnasEnvios = [
        ['Fecha y hora', 'fecha_envio', 22], ['Contacto', 'nombre_contacto', 32],
        ['Usuario', 'nombre_usuario', 32], ['Correo destino', 'correo_destino', 37],
        ['Tipo de envío', 'tipo_envio', 26], ['Resultado', 'resultado', 17],
        ['Asunto', 'asunto', 37], ['Detalle', 'detalle', 65],
        ['ID de quien envió', 'enviado_por', 20], ['ID del envío', 'idenvio', 18],
        ['ID de la cuenta', 'idusuario_acceso', 18]
    ];
    const columnasActividad = [
        ['Fecha', 'fecha', 16], ['Hora', 'hora', 14], ['Evento', 'tipo_evento', 29],
        ['Resultado', 'resultado', 17], ['Correo del intento', 'correo_intento', 37],
        ['Dispositivo', 'dispositivo', 25], ['Tipo de dispositivo', 'tipo_dispositivo', 22],
        ['Navegador', 'navegador', 25], ['Versión', 'version_navegador', 15],
        ['Sistema operativo', 'sistema_operativo', 24], ['IP', 'ip', 21],
        ['Ubicación', 'ubicacion', 30], ['Detalle', 'detalle', 65],
        ['ID del evento', 'idlog', 18]
    ];

    function escaparHTML(valor) {
        return String(valor ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    async function leerJson(response) {
        const text = await response.text();

        try {
            return JSON.parse(text);
        } catch (error) {
            throw new Error(
                'La respuesta del servidor no tiene formato JSON.'
            );
        }
    }

    async function cargarAccesoCliente() {
        try {
            const response = await fetch(
                `${base_url}/cli_clientes/getAccesoCliente/${idclientePagina}`,
                {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            const result = await leerJson(response);

            if (!response.ok || !result.status) {
                throw new Error(result.message);
            }

            const data = result.data;

            inputIdCliente.value = data.idcliente;
            inputIdUsuario.value = data.idusuario_acceso;
            inputUsuario.value = data.nombre_cliente;
            inputCorreo.value = data.correo_acceso;
            inputLiga.value = data.liga_acceso;

            inputDobleAuth.checked =
                Number(data.doble_autenticacion) === 1;

            document.getElementById('lblCliente').textContent =
                data.nombre_cliente || 'Sin información';

            document.getElementById('lblEstadoAcceso').textContent =
                Number(data.estado_acceso) === 1
                    ? 'Activo'
                    : 'Sin configurar';

            document.getElementById('lblEstadoPassword').textContent =
                Number(data.idusuario_acceso) === 0
                    ? 'Sin credenciales'
                    : Number(data.requiere_cambio_password) === 1
                        ? 'Pendiente de cambio'
                        : `Cambiada: ${data.fecha_cambio_password || 'Sí'}`;

            document.getElementById('lblUltimoAcceso').textContent =
                data.ultimo_login || 'Sin accesos';

            loader.classList.add('d-none');
            form.classList.remove('d-none');

        } catch (error) {
            loader.className = 'alert alert-danger';

            loader.innerHTML = `
                <i class="ri-error-warning-line me-1"></i>
                ${escaparHTML(error.message)}
            `;
        }
    }

    function obtenerCaracterSeguro(caracteres) {
        const valores = new Uint32Array(1);
        window.crypto.getRandomValues(valores);

        return caracteres[
            valores[0] % caracteres.length
        ];
    }

    function mezclar(texto) {
        const arreglo = texto.split('');

        for (let i = arreglo.length - 1; i > 0; i--) {
            const valores = new Uint32Array(1);
            window.crypto.getRandomValues(valores);

            const j = valores[0] % (i + 1);

            [arreglo[i], arreglo[j]] = [
                arreglo[j],
                arreglo[i]
            ];
        }

        return arreglo.join('');
    }

    function generarPassword() {
        const mayusculas = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        const minusculas = 'abcdefghijkmnopqrstuvwxyz';
        const numeros = '23456789';
        const simbolos = '!@#$%&*+-_?';

        const todos =
            mayusculas
            + minusculas
            + numeros
            + simbolos;

        let password =
            obtenerCaracterSeguro(mayusculas)
            + obtenerCaracterSeguro(minusculas)
            + obtenerCaracterSeguro(numeros)
            + obtenerCaracterSeguro(simbolos);

        while (password.length < 15) {
            password += obtenerCaracterSeguro(todos);
        }

        return mezclar(password).substring(0, 15);
    }

    function validarPassword(password) {
        return password.length === 15
            && /[A-Z]/.test(password)
            && /[a-z]/.test(password)
            && /\d/.test(password)
            && /[!@#$%&*+\-_?]/.test(password);
    }

    document
        .getElementById('btnGenerarPassword')
        .addEventListener('click', () => {
            inputPassword.value = generarPassword();
            inputPassword.type = 'text';

            inputPassword.classList.remove('is-invalid');
            inputPassword.classList.add('is-valid');
        });

    document
        .getElementById('btnMostrarPassword')
        .addEventListener('click', event => {
            const mostrar =
                inputPassword.type === 'password';

            inputPassword.type =
                mostrar ? 'text' : 'password';

            event.currentTarget.innerHTML =
                mostrar
                    ? '<i class="ri-eye-off-line"></i>'
                    : '<i class="ri-eye-line"></i>';
        });

    document
        .getElementById('btnCopiarPassword')
        .addEventListener('click', async () => {
            if (!inputPassword.value) {
                Swal.fire({
                    icon: 'info',
                    title: 'Sin contraseña',
                    text: 'Genera primero una contraseña.'
                });

                return;
            }

            await navigator.clipboard.writeText(
                inputPassword.value
            );

            Swal.fire({
                icon: 'success',
                title: 'Contraseña copiada',
                timer: 1300,
                showConfirmButton: false
            });
        });

    document
        .getElementById('btnAbrirPortal')
        .addEventListener('click', () => {
            if (inputLiga.value) {
                window.open(
                    inputLiga.value,
                    '_blank',
                    'noopener,noreferrer'
                );
            }
        });

    form.addEventListener('submit', async event => {
        event.preventDefault();

        if (!validarPassword(inputPassword.value)) {
            inputPassword.classList.add('is-invalid');

            Swal.fire({
                icon: 'warning',
                title: 'Contraseña inválida',
                text: 'Debe contener exactamente 15 caracteres, mayúscula, minúscula, número y símbolo.'
            });

            return;
        }

        const confirmacion = await Swal.fire({
            icon: 'question',
            title: '¿Guardar y enviar accesos?',
            html: `
                Se enviarán las credenciales a:<br>
                <strong>${escaparHTML(inputCorreo.value)}</strong>
            `,
            showCancelButton: true,
            confirmButtonText: 'Sí, guardar y enviar',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmacion.isConfirmed) {
            return;
        }

        const formData = new FormData(form);

        formData.set(
            'doble_autenticacion',
            inputDobleAuth.checked ? '1' : '0'
        );

        try {
            Swal.fire({
                title: 'Enviando accesos',
                text: 'Guardando credenciales y enviando correo...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const response = await fetch(
                `${base_url}/cli_clientes/setAccesoCliente`,
                {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            const result = await leerJson(response);

            if (!response.ok || !result.status) {
                throw new Error(result.message);
            }

            inputIdUsuario.value =
                result.data.idusuario_acceso;

            inputPassword.value = '';
            inputPassword.type = 'password';
            inputPassword.classList.remove('is-valid');

            await Swal.fire({
                icon: 'success',
                title: 'Accesos enviados',
                text: result.message
            });

            cargarAccesoCliente();
            logsCargados = false;
            if (document.getElementById('tabHistorico').classList.contains('active')) {
                cargarHistorico();
            }

        } catch (error) {
            logsCargados = false;
            Swal.fire({
                icon: 'error',
                title: 'No fue posible completar la operación',
                text: error.message
            });
        }
    });

    function badgeResultado(resultado) {
        const tipos = {
            EXITOSO: 'success',
            ENVIADO: 'success',
            FALLIDO: 'danger',
            BLOQUEADO: 'warning',
            INFORMATIVO: 'info'
        };

        const clase = tipos[resultado] || 'secondary';

        return `
            <span class="badge bg-${clase}-subtle text-${clase}">
                ${escaparHTML(resultado)}
            </span>
        `;
    }

    function renderLogs(data) {
        if (!data.length) {
            tbodyLogs.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        No existen registros de acceso.
                    </td>
                </tr>
            `;

            return;
        }

        tbodyLogs.innerHTML = data.map(log => `
            <tr>
                <td>
                    <strong>${escaparHTML(log.fecha)}</strong>
                    <div class="log-detail">${escaparHTML(log.hora)}</div>
                </td>

                <td>${escaparHTML(log.tipo_evento)}</td>

                <td>${badgeResultado(log.resultado)}</td>

                <td>
                    <div class="d-flex align-items-center gap-2">
                        <span class="device-icon">
                            <i class="ri-computer-line"></i>
                        </span>

                        <div>
                            ${escaparHTML(log.dispositivo || 'No identificado')}
                            <div class="log-detail">
                                ${escaparHTML(log.tipo_dispositivo || '')}
                            </div>
                        </div>
                    </div>
                </td>

                <td>
                    ${escaparHTML(log.navegador || 'No identificado')}
                    <div class="log-detail">
                        ${escaparHTML(log.version_navegador || '')}
                    </div>
                </td>

                <td>
                    ${escaparHTML(log.sistema_operativo || 'No identificado')}
                </td>

                <td>
                    <code>${escaparHTML(log.ip || 'Sin IP')}</code>
                </td>

                <td>
                    ${escaparHTML(log.ubicacion || 'No disponible')}
                </td>

                <td>
                    <button
                        type="button"
                        class="btn btn-sm btn-soft-info btnDetalleLog"
                        data-log="${encodeURIComponent(JSON.stringify(log))}">

                        <i class="ri-eye-line"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    async function cargarLogs() {
        tbodyLogs.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-5">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Consultando histórico...
                </td>
            </tr>
        `;

        try {
            const response = await fetch(
                `${base_url}/cli_clientes/getLogsAcceso/${idclientePagina}`
            );

            const result = await leerJson(response);

            if (!response.ok || !result.status) {
                throw new Error(result.message);
            }

            logs = Array.isArray(result.data)
                ? result.data
                : [];

            filtrarLogs();
            return true;

        } catch (error) {
            logs = [];
            tbodyLogs.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-5 text-danger">
                        ${escaparHTML(error.message)}
                    </td>
                </tr>
            `;
            return false;
        }
    }

    async function cargarEnviosCredenciales() {
        tbodyEnvios.innerHTML = '<tr><td colspan="9" class="text-center py-3">Consultando envíos…</td></tr>';

        try {
            const response = await fetch(
                `${base_url}/cli_clientes/getEnviosCredenciales/${encodeURIComponent(idclientePagina)}`
            );
            const result = await leerJson(response);
            if (!response.ok || !result.status) {
                throw new Error(result.message || 'No fue posible consultar los envíos.');
            }

            enviosCredenciales = Array.isArray(result.data) ? result.data : [];
            tbodyEnvios.innerHTML = enviosCredenciales.length
                ? enviosCredenciales.map(envio => `<tr>${columnasEnvios.slice(0, 9).map(([, campo]) =>
                    `<td>${campo === 'resultado' ? badgeResultado(envio[campo]) : escaparHTML(envio[campo] || '—')}</td>`
                ).join('')}</tr>`).join('')
                : '<tr><td colspan="9" class="text-center py-3 text-muted">No hay envíos registrados.</td></tr>';
            return true;
        } catch (error) {
            enviosCredenciales = [];
            tbodyEnvios.innerHTML = `<tr><td colspan="9" class="text-center py-3 text-danger">${escaparHTML(error.message)}</td></tr>`;
            return false;
        }
    }

    function cargarHistorico() {
        if (!consultaHistorico) {
            logsCargados = false;
            consultaHistorico = Promise.all([cargarLogs(), cargarEnviosCredenciales()])
                .then(resultados => {
                    logsCargados = resultados.every(Boolean);
                    return logsCargados;
                })
                .finally(() => { consultaHistorico = null; });
        }
        return consultaHistorico;
    }

    function cargarBibliotecaExcel() {
        if (window.ExcelJS) {
            return Promise.resolve();
        }
        if (!bibliotecaExcel) {
            bibliotecaExcel = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/exceljs@4.4.0/dist/exceljs.min.js';
                script.onload = resolve;
                script.onerror = () => {
                    script.remove();
                    bibliotecaExcel = null;
                    reject(new Error('No se pudo cargar la biblioteca de Excel.'));
                };
                document.head.appendChild(script);
            });
        }
        return bibliotecaExcel;
    }

    function agregarHojaHistorico(libro, nombre, columnas, registros) {
        const hoja = libro.addWorksheet(nombre, { views: [{ state: 'frozen', ySplit: 3 }] });
        hoja.getCell('A1').value = document.getElementById('lblCliente').textContent.trim();
        hoja.getCell('A1').font = { bold: true, size: 14 };
        hoja.columns = columnas.map(([, key, width]) => ({ key, width }));
        hoja.getRow(3).values = columnas.map(([titulo]) => titulo);
        hoja.getRow(3).eachCell(celda => {
            celda.font = { bold: true, color: { argb: 'FFFFFFFF' } };
            celda.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF25374B' } };
        });
        hoja.autoFilter = { from: { row: 3, column: 1 }, to: { row: 3, column: columnas.length } };
        hoja.addRows(registros.map(registro => columnas.map(([, campo]) => String(registro[campo] ?? ''))));
    }

    async function exportarHistorico() {
        botonExportar.disabled = true;
        try {
            if (!await cargarHistorico()) {
                throw new Error('No se pudo consultar el historial completo del distribuidor.');
            }

            await cargarBibliotecaExcel();
            const libro = new window.ExcelJS.Workbook();
            agregarHojaHistorico(libro, 'Envíos de credenciales', columnasEnvios, enviosCredenciales);
            agregarHojaHistorico(libro, 'Actividad del portal', columnasActividad, logs);
            const contenido = await libro.xlsx.writeBuffer();
            const archivo = new Blob([contenido], {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            });
            const url = URL.createObjectURL(archivo);
            const enlace = document.createElement('a');
            enlace.href = url;
            enlace.download = `Accesos-distribuidor-${idclientePagina}.xlsx`;
            enlace.click();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'No fue posible exportar', text: error.message });
        } finally {
            botonExportar.disabled = false;
        }
    }

    botonExportar.addEventListener('click', exportarHistorico);

    function filtrarLogs() {
        const inicio =
            document.getElementById('filtroFechaInicio').value;

        const fin =
            document.getElementById('filtroFechaFin').value;

        const resultado =
            document.getElementById('filtroResultado').value;

        const busqueda =
            document.getElementById('filtroBusqueda')
                .value
                .toLowerCase()
                .trim();

        const filtrados = logs.filter(log => {
            const texto = [
                log.tipo_evento,
                log.dispositivo,
                log.navegador,
                log.sistema_operativo,
                log.ip,
                log.ubicacion,
                log.detalle
            ].join(' ').toLowerCase();

            return (!inicio || log.fecha_iso >= inicio)
                && (!fin || log.fecha_iso <= fin)
                && (!resultado || log.resultado === resultado)
                && (!busqueda || texto.includes(busqueda));
        });

        renderLogs(filtrados);
    }

    document
        .getElementById('btnTabHistorico')
        .addEventListener('shown.bs.tab', () => {
            if (!logsCargados) {
                cargarHistorico();
            }
        });

    document
        .getElementById('btnActualizarLogs')
        .addEventListener('click', cargarHistorico);

    [
        'filtroFechaInicio',
        'filtroFechaFin',
        'filtroResultado'
    ].forEach(id => {
        document
            .getElementById(id)
            .addEventListener('change', filtrarLogs);
    });

    document
        .getElementById('filtroBusqueda')
        .addEventListener('input', filtrarLogs);

    document.addEventListener('click', event => {
        const boton = event.target.closest('.btnDetalleLog');

        if (!boton) {
            return;
        }

        const log = JSON.parse(
            decodeURIComponent(boton.dataset.log)
        );

        Swal.fire({
            title: 'Detalle del evento',
            width: 700,
            html: `
                <div class="text-start">
                    <p><strong>Evento:</strong> ${escaparHTML(log.tipo_evento)}</p>
                    <p><strong>Resultado:</strong> ${escaparHTML(log.resultado)}</p>
                    <p><strong>Fecha:</strong> ${escaparHTML(log.fecha)} ${escaparHTML(log.hora)}</p>
                    <p><strong>IP:</strong> ${escaparHTML(log.ip)}</p>
                    <p><strong>Sesión:</strong> ${escaparHTML(log.id_sesion)}</p>
                    <p><strong>User Agent:</strong> ${escaparHTML(log.user_agent)}</p>
                    <p><strong>Detalle:</strong> ${escaparHTML(log.detalle)}</p>
                </div>
            `
        });
    });

    cargarAccesoCliente();
});