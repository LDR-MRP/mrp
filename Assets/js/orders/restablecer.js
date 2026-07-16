'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById(
        'resetPasswordForm'
    );

    if (!form) {
        return;
    }

    const inputToken =
        document.getElementById('token');

    const inputPassword =
        document.getElementById('passwordNueva');

    const inputConfirmacion =
        document.getElementById(
            'passwordConfirmacion'
        );

    const btnRestablecer =
        document.getElementById(
            'btnRestablecerPassword'
        );

    const strengthBar =
        document.getElementById(
            'passwordStrengthBar'
        );

    const strengthText =
        document.getElementById(
            'passwordStrengthText'
        );

    const reglasElementos = {
        longitud: document.getElementById(
            'ruleLength'
        ),

        mayuscula: document.getElementById(
            'ruleUpper'
        ),

        minuscula: document.getElementById(
            'ruleLower'
        ),

        numero: document.getElementById(
            'ruleNumber'
        ),

        simbolo: document.getElementById(
            'ruleSymbol'
        )
    };

    function evaluarPassword(password) {
        return {
            longitud: password.length >= 10,
            mayuscula: /[A-Z]/.test(password),
            minuscula: /[a-z]/.test(password),
            numero: /\d/.test(password),
            simbolo: /[^A-Za-z0-9]/.test(
                password
            )
        };
    }

    function passwordValida(password) {
        const reglas = evaluarPassword(
            password
        );

        return Object.values(reglas)
            .every(Boolean);
    }

    function actualizarRegla(
        elemento,
        cumple,
        texto
    ) {
        if (!elemento) {
            return;
        }

        elemento.classList.toggle(
            'password-rule-valid',
            cumple
        );

        elemento.classList.toggle(
            'password-rule-invalid',
            !cumple
        );

        elemento.textContent =
            `${cumple ? '✓' : '○'} ${texto}`;
    }

    function actualizarFortaleza() {
        const password = inputPassword.value;

        const reglas =
            evaluarPassword(password);

        const puntuacion =
            Object.values(reglas)
                .filter(Boolean)
                .length;

        actualizarRegla(
            reglasElementos.longitud,
            reglas.longitud,
            'Al menos 10 caracteres'
        );

        actualizarRegla(
            reglasElementos.mayuscula,
            reglas.mayuscula,
            'Una letra mayúscula'
        );

        actualizarRegla(
            reglasElementos.minuscula,
            reglas.minuscula,
            'Una letra minúscula'
        );

        actualizarRegla(
            reglasElementos.numero,
            reglas.numero,
            'Un número'
        );

        actualizarRegla(
            reglasElementos.simbolo,
            reglas.simbolo,
            'Un símbolo especial'
        );

        let porcentaje = 0;
        let texto = 'Ingresa una contraseña.';
        let color = '#ef4444';

        if (password.length > 0) {
            porcentaje = puntuacion * 20;

            if (puntuacion <= 2) {
                texto = 'Contraseña débil.';
                color = '#ef4444';
            } else if (puntuacion <= 4) {
                texto = 'Contraseña media.';
                color = '#f59e0b';
            } else {
                texto = 'Contraseña segura.';
                color = '#16a34a';
            }
        }

        strengthBar.style.width =
            `${porcentaje}%`;

        strengthBar.style.backgroundColor =
            color;

        strengthText.textContent = texto;
        strengthText.style.color = color;
    }

    function bloquearBoton(
        boton,
        texto
    ) {
        boton.disabled = true;

        boton.dataset.originalText =
            boton.innerHTML;

        boton.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2"></span>
            ${texto}
        `;
    }

    function desbloquearBoton(boton) {
        boton.disabled = false;

        if (boton.dataset.originalText) {
            boton.innerHTML =
                boton.dataset.originalText;
        }
    }

    async function leerJson(response) {
        const texto =
            await response.text();

        try {
            return JSON.parse(texto);

        } catch (error) {
            console.error(
                'Respuesta real del servidor:',
                texto
            );

            const resumen = texto
                .replace(/<[^>]*>/g, ' ')
                .replace(/\s+/g, ' ')
                .trim()
                .substring(0, 300);

            throw new Error(
                resumen
                    ? `Respuesta inválida del servidor: ${resumen}`
                    : 'El servidor devolvió una respuesta vacía.'
            );
        }
    }

    document
        .querySelectorAll(
            '[data-toggle-password]'
        )
        .forEach(button => {
            button.addEventListener(
                'click',
                event => {
                    const selector =
                        event.currentTarget
                            .dataset
                            .togglePassword;

                    const input =
                        document.querySelector(
                            selector
                        );

                    if (!input) {
                        return;
                    }

                    const mostrar =
                        input.type === 'password';

                    input.type =
                        mostrar
                            ? 'text'
                            : 'password';

                    event.currentTarget
                        .textContent =
                            mostrar
                                ? 'Ocultar'
                                : 'Ver';
                }
            );
        });

    inputPassword.addEventListener(
        'input',
        actualizarFortaleza
    );

    inputConfirmacion.addEventListener(
        'input',
        () => {
            inputConfirmacion
                .classList
                .remove(
                    'is-valid',
                    'is-invalid'
                );

            if (!inputConfirmacion.value) {
                return;
            }

            if (
                inputConfirmacion.value
                === inputPassword.value
            ) {
                inputConfirmacion
                    .classList
                    .add('is-valid');
            } else {
                inputConfirmacion
                    .classList
                    .add('is-invalid');
            }
        }
    );

    form.addEventListener(
        'submit',
        async event => {
            event.preventDefault();

            const token =
                inputToken.value.trim();

            const passwordNueva =
                inputPassword.value;

            const confirmacion =
                inputConfirmacion.value;

            if (!token) {
                Swal.fire({
                    icon: 'error',
                    title: 'Liga inválida',
                    text:
                        'No se encontró el token de recuperación.'
                });

                return;
            }

            if (
                !passwordValida(
                    passwordNueva
                )
            ) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Contraseña no segura',
                    text:
                        'La contraseña no cumple con todos los requisitos de seguridad.'
                });

                inputPassword.focus();

                return;
            }

            if (
                passwordNueva
                !== confirmacion
            ) {
                Swal.fire({
                    icon: 'warning',
                    title:
                        'Las contraseñas no coinciden',
                    text:
                        'Confirma nuevamente la contraseña.'
                });

                inputConfirmacion.focus();

                return;
            }

            const confirmacionEnvio =
                await Swal.fire({
                    icon: 'question',
                    title:
                        '¿Restablecer contraseña?',
                    text:
                        'La contraseña anterior dejará de funcionar.',
                    showCancelButton: true,
                    confirmButtonText:
                        'Sí, restablecer',
                    cancelButtonText:
                        'Cancelar'
                });

            if (
                !confirmacionEnvio.isConfirmed
            ) {
                return;
            }

            const formData =
                new FormData();

            formData.append(
                'token',
                token
            );

            formData.append(
                'password_nueva',
                passwordNueva
            );

            formData.append(
                'password_confirmacion',
                confirmacion
            );

            try {
                bloquearBoton(
                    btnRestablecer,
                    'Guardando contraseña'
                );

                const response =
                    await fetch(
                        `${base_url}/orders/guardarPasswordRecuperacion`,
                        {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With':
                                    'XMLHttpRequest'
                            }
                        }
                    );

                const result =
                    await leerJson(response);

                if (
                    !response.ok
                    || !result.status
                ) {
                    throw new Error(
                        result.message
                        || 'No fue posible restablecer la contraseña.'
                    );
                }

                await Swal.fire({
                    icon: 'success',
                    title:
                        'Contraseña restablecida',
                    text: result.message,
                    confirmButtonText:
                        'Iniciar sesión'
                });

                window.location.href =
                    result.data?.redirect
                    || `${base_url}/orders/login`;

            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title:
                        'No fue posible restablecer',
                    text: error.message
                });

            } finally {
                desbloquearBoton(
                    btnRestablecer
                );
            }
        }
    );

    actualizarFortaleza();
});