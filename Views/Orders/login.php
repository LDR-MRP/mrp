<?php headerOrders($data); ?>

<main class="auth-main">
    <section class="auth-wrapper container">

        <div class="auth-info-card">
            <span class="tag">Portal para distribuidores</span>

            <h1>Accede a tu cuenta comercial</h1>

            <p>
                Consulta tu catálogo, genera solicitudes de pedido y da seguimiento
                a la trazabilidad de tus unidades.
            </p>

            <div class="auth-benefits-list">
                <div>
                    <strong>Pedidos centralizados</strong>
                    <span>Administra todos tus pedidos desde una sola cuenta.</span>
                </div>

                <div>
                    <strong>Trazabilidad completa</strong>
                    <span>Revisa el avance desde solicitud hasta entrega.</span>
                </div>

                <div>
                    <strong>Catálogo actualizado</strong>
                    <span>Busca unidades por modelo, segmento o disponibilidad.</span>
                </div>
            </div>
        </div>

        <div class="auth-card">

            <div class="auth-tabs" id="authTabs">
                <button
                    type="button"
                    class="auth-tab active"
                    data-auth-tab="login">

                    Iniciar sesión
                </button>

                <button
                    type="button"
                    class="auth-tab"
                    data-auth-tab="recover">

                    Recuperar contraseña
                </button>
            </div>

            <!-- LOGIN -->
            <form
                class="auth-form active"
                id="loginForm"
                autocomplete="off"
                novalidate>

                <div class="form-header">
                    <h2>Bienvenido</h2>
                    <p>Ingresa tus credenciales para continuar.</p>
                </div>

                <label for="usuario">
                    Correo electrónico
                </label>

                <input
                    type="email"
                    id="usuario"
                    name="usuario"
                    placeholder="Ejemplo: distribuidor@empresa.com"
                    autocomplete="username"
                    required>

                <label for="password">
                    Contraseña
                </label>

                <div class="password-field">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Ingresa tu contraseña"
                        autocomplete="current-password"
                        required>

                    <button
                        type="button"
                        id="togglePassword">

                        Ver
                    </button>
                </div>

                <div class="form-options">
                    <label class="check-option">
                        <input
                            type="checkbox"
                            id="recordarSesion"
                            name="recordar_sesion"
                            value="1">

                        <span>Recordar sesión</span>
                    </label>

                    <button
                        type="button"
                        class="link-button"
                        data-show-recover>

                        ¿Olvidaste tu contraseña?
                    </button>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary btn-full"
                    id="btnLogin">

                    Entrar al portal
                </button>

                <p class="form-note">
                    Acceso exclusivo para distribuidores autorizados.
                </p>
            </form>

            <!-- PIN -->
            <form
                class="auth-form"
                id="pinForm"
                autocomplete="off"
                novalidate>

                <input
                    type="hidden"
                    id="pinChallenge"
                    name="challenge">

                <div class="form-header">
                    <h2>Verificación de seguridad</h2>

                    <p>
                        Enviamos un PIN de seis dígitos a tu correo. El código tendrá
                        una vigencia de tres minutos.
                    </p>
                </div>

                <label for="codigoPin">
                    PIN de seguridad
                </label>

                <input
                    type="text"
                    id="codigoPin"
                    name="pin"
                    class="pin-input"
                    placeholder="000000"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    required>

                <div class="recover-message show" id="pinTimerContainer">
                    El PIN caduca en
                    <strong id="pinTimer">03:00</strong>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary btn-full"
                    id="btnValidarPin">

                    Validar PIN
                </button>

                <button
                    type="button"
                    class="btn btn-outline btn-full"
                    id="btnReenviarPin">

                    Reenviar PIN
                </button>

                <button
                    type="button"
                    class="link-button"
                    id="btnVolverLogin">

                    Volver al inicio de sesión
                </button>
            </form>

            <!-- CAMBIO OBLIGATORIO -->
            <form
                class="auth-form"
                id="changePasswordForm"
                autocomplete="off"
                novalidate>

                <div class="form-header">
                    <h2>Crea tu nueva contraseña</h2>

                    <p>
                        La contraseña recibida por correo es temporal. Este cambio
                        solamente se solicitará una vez.
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
                        required>

                    <button
                        type="button"
                        data-toggle-password="#passwordNueva">

                        Ver
                    </button>
                </div>

                <label for="passwordConfirmacion">
                    Confirmar nueva contraseña
                </label>

                <div class="password-field">
                    <input
                        type="password"
                        id="passwordConfirmacion"
                        name="password_confirmacion"
                        placeholder="Confirma la contraseña"
                        autocomplete="new-password"
                        required>

                    <button
                        type="button"
                        data-toggle-password="#passwordConfirmacion">

                        Ver
                    </button>
                </div>

                <div class="recover-message show">
                    La contraseña debe contener al menos 10 caracteres, una mayúscula,
                    una minúscula, un número y un símbolo.
                </div>

                <button
                    type="submit"
                    class="btn btn-primary btn-full">

                    Guardar nueva contraseña
                </button>
            </form>

            <!-- RECUPERACIÓN -->
            <form
                class="auth-form"
                id="recoverForm"
                autocomplete="off"
                novalidate>

                <div class="form-header">
                    <h2>Recuperar contraseña</h2>

                    <p>
                        Coloca el correo registrado y te enviaremos instrucciones
                        para restablecer tu acceso.
                    </p>
                </div>

                <label for="recoverEmail">
                    Correo electrónico de la cuenta
                </label>

                <input
                    type="email"
                    id="recoverEmail"
                    name="correo"
                    placeholder="correo@empresa.com"
                    autocomplete="email"
                    required>

                <button
                    type="submit"
                    class="btn btn-primary btn-full">

                    Solicitar recuperación
                </button>

                <button
                    type="button"
                    class="btn btn-outline btn-full"
                    data-show-login>

                    Volver a iniciar sesión
                </button>

                <div
                    class="recover-message"
                    id="recoverMessage">

                    Si el correo está registrado, recibirás las instrucciones
                    para continuar.
                </div>
            </form>

        </div>
    </section>
</main>

<?php footerOrders($data); ?>