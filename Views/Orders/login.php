  <?php 
	headerOrders($data);
 ?>
  <main class="auth-main">
    <section class="auth-wrapper container">
      <div class="auth-info-card">
        <span class="tag">Portal para distribuidores</span>
        <h1>Accede a tu cuenta comercial</h1>
        <p>Consulta tu catálogo, genera solicitudes de pedido y da seguimiento a la trazabilidad de tus unidades.</p>

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
        <div class="auth-tabs">
          <button class="auth-tab active" data-auth-tab="login">Iniciar sesión</button>
          <button class="auth-tab" data-auth-tab="recover">Recuperar contraseña</button>
        </div>

        <form class="auth-form active" id="loginForm">
          <div class="form-header">
            <h2>Bienvenido</h2>
            <p>Ingresa tus credenciales para continuar.</p>
          </div>

          <label for="usuario">Usuario o correo electrónico</label>
          <input type="text" id="usuario" placeholder="Ejemplo: distribuidor@empresa.com" required />

          <label for="password">Contraseña</label>
          <div class="password-field">
            <input type="password" id="password" placeholder="Ingresa tu contraseña" required />
            <button type="button" id="togglePassword">Ver</button>
          </div>

          <div class="form-options">
            <label class="check-option">
              <input type="checkbox" />
              <span>Recordar sesión</span>
            </label>
            <button type="button" class="link-button" data-show-recover>¿Olvidaste tu contraseña?</button>
          </div>

          <button type="submit" class="btn btn-primary btn-full">Entrar al portal</button>
          <p class="form-note">Acceso exclusivo para distribuidores autorizados.</p>
        </form>

        <form class="auth-form" id="recoverForm">
          <div class="form-header">
            <h2>Recuperar contraseña</h2>
            <p>Coloca el correo registrado en tu cuenta y te enviaremos instrucciones para restablecer tu acceso.</p>
          </div>

          <label for="recoverEmail">Correo electrónico de la cuenta</label>
          <input type="email" id="recoverEmail" placeholder="correo@empresa.com" required />

          <button type="submit" class="btn btn-primary btn-full">Solicitar recuperación</button>
          <button type="button" class="btn btn-outline btn-full" data-show-login>Volver a iniciar sesión</button>

          <div class="recover-message" id="recoverMessage">
            Solicitud registrada. Revisa tu correo para continuar con el proceso.
          </div>
        </form>
      </div>
    </section>
  </main>

  
  <?php 
	footerOrders($data);
 ?>