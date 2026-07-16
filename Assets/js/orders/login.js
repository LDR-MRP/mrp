'use strict';

let pinInterval = null;
let pinSecondsRemaining = 0;
let pinChallengeActual = null;

function escaparHTML(valor) {
    return String(valor ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

async function leerJson(response) {
    const texto = await response.text();

    try {
        return JSON.parse(texto);
    } catch (error) {
        console.error('Respuesta real del servidor:', texto);

        throw new Error(
            'La respuesta del servidor no tiene formato JSON.'
        );
    }
}

function mostrarFormulario(idFormulario) {
    document.querySelectorAll('.auth-form').forEach(form => {
        form.classList.remove('active');
    });

    document
        .getElementById(idFormulario)
        ?.classList.add('active');

    const mostrarTabs = ['loginForm', 'recoverForm']
        .includes(idFormulario);

    document
        .getElementById('authTabs')
        ?.classList.toggle('d-none', !mostrarTabs);
}

function showAuth(tab) {
    document.querySelectorAll('.auth-tab').forEach(button => {
        button.classList.toggle(
            'active',
            button.dataset.authTab === tab
        );
    });

    mostrarFormulario(
        tab === 'recover'
            ? 'recoverForm'
            : 'loginForm'
    );
}

function bloquearBoton(boton, texto) {
    if (!boton) {
        return;
    }

    boton.dataset.originalText = boton.innerHTML;
    boton.disabled = true;

    boton.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2"></span>
        ${texto}
    `;
}

function desbloquearBoton(boton) {
    if (!boton) {
        return;
    }

    boton.disabled = false;

    if (boton.dataset.originalText) {
        boton.innerHTML = boton.dataset.originalText;
    }
}

function iniciarTemporizadorPin(segundos) {
    clearInterval(pinInterval);

    pinSecondsRemaining = segundos;

    const timer = document.getElementById('pinTimer');
    const btnValidar = document.getElementById('btnValidarPin');

    function actualizar() {
        const minutos = Math.floor(
            pinSecondsRemaining / 60
        );

        const segundosRestantes =
            pinSecondsRemaining % 60;

        timer.textContent =
            `${String(minutos).padStart(2, '0')}:`
            + `${String(segundosRestantes).padStart(2, '0')}`;

        if (pinSecondsRemaining <= 0) {
            clearInterval(pinInterval);

            timer.textContent = 'Caducado';

            btnValidar.disabled = true;

            Swal.fire({
                icon: 'warning',
                title: 'PIN caducado',
                text: 'El PIN superó los tres minutos de vigencia. Solicita uno nuevo.'
            });

            return;
        }

        pinSecondsRemaining--;
    }

    btnValidar.disabled = false;

    actualizar();

    pinInterval = setInterval(
        actualizar,
        1000
    );
}

function validarPasswordSegura(password) {
    return password.length >= 10
        && /[A-Z]/.test(password)
        && /[a-z]/.test(password)
        && /\d/.test(password)
        && /[^A-Za-z0-9]/.test(password);
}

async function procesarResultadoAutenticacion(data) {
    if (data.requiere_pin) {
        pinChallengeActual = data.challenge;

        document.getElementById('pinChallenge').value =
            data.challenge;

        document.getElementById('codigoPin').value = '';

        mostrarFormulario('pinForm');

        iniciarTemporizadorPin(
            Number(data.expira_en_segundos) || 180
        );

        document.getElementById('codigoPin').focus();

        return;
    }

    if (data.requiere_cambio_password) {
        mostrarFormulario('changePasswordForm');

        document.getElementById('passwordNueva').focus();

        return;
    }

    window.location.href =
        data.redirect
        || `${base_url}/orders/micuenta`;
}

async function enviarLogin(event) {
    event.preventDefault();

    const form = event.currentTarget;
    const button = document.getElementById('btnLogin');

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData();

    formData.append(
        'correo',
        document.getElementById('usuario').value.trim()
    );

    formData.append(
        'password',
        document.getElementById('password').value
    );

    formData.append(
        'recordar_sesion',
        document.getElementById('recordarSesion').checked
            ? '1'
            : '0'
    );

    try {
        bloquearBoton(button, 'Validando');

        const response = await fetch(
            `${base_url}/orders/autenticar`,
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
            throw new Error(
                result.message
                || 'No fue posible iniciar sesión.'
            );
        }

        await procesarResultadoAutenticacion(
            result.data || {}
        );

    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'No fue posible ingresar',
            text: error.message
        });

    } finally {
        desbloquearBoton(button);
    }
}

async function validarPin(event) {
    event.preventDefault();

    const pin = document
        .getElementById('codigoPin')
        .value
        .trim();

    if (!/^\d{6}$/.test(pin)) {
        Swal.fire({
            icon: 'warning',
            title: 'PIN inválido',
            text: 'El PIN debe contener exactamente seis dígitos.'
        });

        return;
    }

    if (pinSecondsRemaining <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'PIN caducado',
            text: 'Solicita un nuevo PIN para continuar.'
        });

        return;
    }

    const button = document.getElementById('btnValidarPin');

    const formData = new FormData();

    formData.append('pin', pin);
    formData.append(
        'challenge',
        pinChallengeActual || ''
    );

    try {
        bloquearBoton(button, 'Validando PIN');

        const response = await fetch(
            `${base_url}/orders/validarPin`,
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
            throw new Error(
                result.message
                || 'El PIN no es correcto.'
            );
        }

        clearInterval(pinInterval);

        await procesarResultadoAutenticacion(
            result.data || {}
        );

    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'No fue posible validar el PIN',
            text: error.message
        });

    } finally {
        desbloquearBoton(button);
    }
}

async function reenviarPin() {
    if (!pinChallengeActual) {
        Swal.fire({
            icon: 'error',
            title: 'Sesión de validación inválida',
            text: 'Vuelve a iniciar sesión.'
        });

        mostrarFormulario('loginForm');
        return;
    }

    const button = document.getElementById('btnReenviarPin');
    const formData = new FormData();

    formData.append(
        'challenge',
        pinChallengeActual
    );

    try {
        bloquearBoton(button, 'Enviando PIN');

        const response = await fetch(
            `${base_url}/orders/reenviarPin`,
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
            throw new Error(
                result.message
                || 'No fue posible reenviar el PIN.'
            );
        }

        pinChallengeActual =
            result.data.challenge
            || pinChallengeActual;

        document.getElementById('pinChallenge').value =
            pinChallengeActual;

        document.getElementById('codigoPin').value = '';

        iniciarTemporizadorPin(
            Number(result.data.expira_en_segundos)
            || 180
        );

        Swal.fire({
            icon: 'success',
            title: 'PIN enviado',
            text: result.message,
            timer: 1800,
            showConfirmButton: false
        });

    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'No fue posible reenviar',
            text: error.message
        });

    } finally {
        desbloquearBoton(button);
    }
}

async function cambiarPassword(event) {
    event.preventDefault();

    const nueva = document
        .getElementById('passwordNueva')
        .value;

    const confirmacion = document
        .getElementById('passwordConfirmacion')
        .value;

    if (!validarPasswordSegura(nueva)) {
        Swal.fire({
            icon: 'warning',
            title: 'Contraseña no segura',
            text: 'Debe contener al menos 10 caracteres, una mayúscula, una minúscula, un número y un símbolo.'
        });

        return;
    }

    if (nueva !== confirmacion) {
        Swal.fire({
            icon: 'warning',
            title: 'Las contraseñas no coinciden',
            text: 'Confirma nuevamente la contraseña.'
        });

        return;
    }

    const button = event.currentTarget.querySelector(
        'button[type="submit"]'
    );

    const formData = new FormData();

    formData.append(
        'password_nueva',
        nueva
    );

    formData.append(
        'password_confirmacion',
        confirmacion
    );

    try {
        bloquearBoton(button, 'Guardando contraseña');

        const response = await fetch(
            `${base_url}/orders/cambiarPasswordInicial`,
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
            throw new Error(
                result.message
                || 'No fue posible cambiar la contraseña.'
            );
        }

        await Swal.fire({
            icon: 'success',
            title: 'Contraseña actualizada',
            text: result.message
        });

        window.location.href =
            result.data?.redirect
            || `${base_url}/orders/micuenta`;

    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'No fue posible guardar',
            text: error.message
        });

    } finally {
        desbloquearBoton(button);
    }
}

async function solicitarRecuperacion(event) {
    event.preventDefault();

    const form = event.currentTarget;

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const button = form.querySelector(
        'button[type="submit"]'
    );

    const formData = new FormData();

    formData.append(
        'correo',
        document.getElementById('recoverEmail')
            .value
            .trim()
    );

    try {
        bloquearBoton(button, 'Procesando');

        const response = await fetch(
            `${base_url}/orders/solicitarRecuperacion`,
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
            throw new Error(
                result.message
                || 'No fue posible procesar la solicitud.'
            );
        }

        document
            .getElementById('recoverMessage')
            .classList.add('show');

        Swal.fire({
            icon: 'success',
            title: 'Solicitud registrada',
            text: result.message
        });

    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'No fue posible procesar',
            text: error.message
        });

    } finally {
        desbloquearBoton(button);
    }
}

function setupAuth() {
    document
        .querySelectorAll('[data-auth-tab]')
        .forEach(button => {
            button.addEventListener('click', () => {
                showAuth(button.dataset.authTab);
            });
        });

    document
        .querySelector('[data-show-recover]')
        ?.addEventListener('click', () => {
            showAuth('recover');
        });

    document
        .querySelector('[data-show-login]')
        ?.addEventListener('click', () => {
            showAuth('login');
        });

    document
        .getElementById('togglePassword')
        ?.addEventListener('click', event => {
            const input = document.getElementById('password');

            input.type =
                input.type === 'password'
                    ? 'text'
                    : 'password';

            event.currentTarget.textContent =
                input.type === 'password'
                    ? 'Ver'
                    : 'Ocultar';
        });

    document
        .querySelectorAll('[data-toggle-password]')
        .forEach(button => {
            button.addEventListener('click', event => {
                const selector =
                    event.currentTarget.dataset.togglePassword;

                const input =
                    document.querySelector(selector);

                if (!input) {
                    return;
                }

                input.type =
                    input.type === 'password'
                        ? 'text'
                        : 'password';

                event.currentTarget.textContent =
                    input.type === 'password'
                        ? 'Ver'
                        : 'Ocultar';
            });
        });

    document
        .getElementById('codigoPin')
        ?.addEventListener('input', event => {
            event.target.value =
                event.target.value
                    .replace(/\D/g, '')
                    .substring(0, 6);
        });

    document
        .getElementById('loginForm')
        ?.addEventListener(
            'submit',
            enviarLogin
        );

    document
        .getElementById('pinForm')
        ?.addEventListener(
            'submit',
            validarPin
        );

    document
        .getElementById('btnReenviarPin')
        ?.addEventListener(
            'click',
            reenviarPin
        );

    document
        .getElementById('changePasswordForm')
        ?.addEventListener(
            'submit',
            cambiarPassword
        );

    document
        .getElementById('recoverForm')
        ?.addEventListener(
            'submit',
            solicitarRecuperacion
        );

    document
        .getElementById('btnVolverLogin')
        ?.addEventListener('click', () => {
            clearInterval(pinInterval);

            pinChallengeActual = null;

            mostrarFormulario('loginForm');
        });
}

document.addEventListener(
    'DOMContentLoaded',
    setupAuth
);