<?php headerOrders($data); ?>

<main class="auth-main">
    <section class="auth-wrapper container">

        <div class="auth-info-card">
            <span class="tag">
                Seguridad de la cuenta
            </span>

            <h1>
                Restablece tu acceso
            </h1>

            <p>
                Crea una nueva contraseña para volver a ingresar
                al Portal de Pedidos de Distribuidores.
            </p>

            <div class="auth-benefits-list">
                <div>
                    <strong>Protección de cuenta</strong>

                    <span>
                        La liga de recuperación es personal y tiene
                        una vigencia limitada.
                    </span>
                </div>

                <div>
                    <strong>Contraseña segura</strong>

                    <span>
                        Utiliza una combinación de letras, números
                        y símbolos.
                    </span>
                </div>

                <div>
                    <strong>Acceso inmediato</strong>

                    <span>
                        Una vez actualizada podrás iniciar sesión
                        con tu nueva contraseña.
                    </span>
                </div>
            </div>
        </div>

        <div class="auth-card">

            <?php if (!empty($data['token_valido'])): ?>

                <form
                    class="auth-form active"
                    id="resetPasswordForm"
                    autocomplete="off"
                    novalidate>

                    <input
                        type="hidden"
                        id="token"
                        name="token"
                        value="<?= htmlspecialchars(
                            $data['token'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>">

                    <div class="form-header">
                        <h2>
                            Crea una nueva contraseña
                        </h2>

                        <p>
                            Ingresa y confirma la nueva contraseña
                            para tu cuenta.
                        </p>
                    </div>

                    <label for="passwordNueva">
                        Nueva contraseña
                    </label>

                    <div class="password-field">
                        <input
                            type="password"
                            id="passwordNueva"
                            name="password_nueva"
                            placeholder="Ingresa una contraseña segura"
                            autocomplete="new-password"
                            minlength="10"
                            required>

                        <button
                            type="button"
                            data-toggle-password="#passwordNueva">

                            Ver
                        </button>
                    </div>

                    <div
                        class="password-strength"
                        id="passwordStrength">

                        <div class="password-strength-bar">
                            <span id="passwordStrengthBar"></span>
                        </div>

                        <small id="passwordStrengthText">
                            Ingresa una contraseña.
                        </small>
                    </div>

                    <label for="passwordConfirmacion">
                        Confirmar nueva contraseña
                    </label>

                    <div class="password-field">
                        <input
                            type="password"
                            id="passwordConfirmacion"
                            name="password_confirmacion"
                            placeholder="Confirma tu nueva contraseña"
                            autocomplete="new-password"
                            minlength="10"
                            required>

                        <button
                            type="button"
                            data-toggle-password="#passwordConfirmacion">

                            Ver
                        </button>
                    </div>

                    <div class="recover-message show">
                        <strong>La contraseña debe incluir:</strong>

                        <br><br>

                        <span id="ruleLength">
                            ○ Al menos 10 caracteres
                        </span>

                        <br>

                        <span id="ruleUpper">
                            ○ Una letra mayúscula
                        </span>

                        <br>

                        <span id="ruleLower">
                            ○ Una letra minúscula
                        </span>

                        <br>

                        <span id="ruleNumber">
                            ○ Un número
                        </span>

                        <br>

                        <span id="ruleSymbol">
                            ○ Un símbolo especial
                        </span>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary btn-full"
                        id="btnRestablecerPassword">

                        Guardar nueva contraseña
                    </button>

                    <a
                        href="<?= base_url(); ?>/orders/login"
                        class="btn btn-outline btn-full">

                        Volver a iniciar sesión
                    </a>
                </form>

            <?php else: ?>

                <div class="auth-form active">

                    <div class="form-header">
                        <h2>
                            Liga no disponible
                        </h2>

                        <p>
                            <?= htmlspecialchars(
                                $data['mensaje_token']
                                ?? 'La liga de recuperación no es válida.',
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </p>
                    </div>

                    <div class="recover-message show">
                        La liga pudo haber caducado, ya fue utilizada
                        o no corresponde a una solicitud válida.
                    </div>

                    <a
                        href="<?= base_url(); ?>/orders/login"
                        class="btn btn-primary btn-full">

                        Solicitar una nueva recuperación
                    </a>
                </div>

            <?php endif; ?>

        </div>
    </section>
</main>

<?php footerOrders($data); ?>