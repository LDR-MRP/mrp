 <?php 
	headerOrders($data);
 ?>
 <main class="account-main">
    <section class="account-hero">
      <div class="container account-hero-grid">
        <div>
          <span class="tag">Mi cuenta</span>
          <h1>Seguimiento de solicitudes de pedido</h1>
          <p>Consulta pedidos en proceso, finalizados y revisa la trazabilidad completa de cada solicitud.</p>
        </div>
        <div class="account-summary-card">
          <span>Distribuidor</span>
          <strong>Distribuidora Demo Norte</strong>
          <p>Clave: DIST-00045</p>
        </div>
      </div>
    </section>

    <section class="container account-layout">
      <aside class="account-sidebar">
        <div class="profile-card">
          <div class="profile-avatar">DN</div>
          <h3>Distribuidora Demo Norte</h3>
          <p>compras@distribuidora.com</p>
        </div>
        <div class="account-stats">
          <div><strong>12</strong><span>Pedidos</span></div>
          <div><strong>5</strong><span>En proceso</span></div>
          <div><strong>7</strong><span>Finalizados</span></div>
        </div>
      </aside>

      <section class="account-content">
        <div class="tabs-header">
          <button class="tab-button active" data-tab="todos">Mis pedidos</button>
          <button class="tab-button" data-tab="proceso">En proceso</button>
          <button class="tab-button" data-tab="finalizados">Finalizados</button>
          <button class="tab-button" data-tab="trazabilidad">Trazabilidad</button>
        </div>

        <div class="tab-panel active" id="todos">
          <div class="panel-title-row">
            <div>
              <h2>Mis pedidos</h2>
              <p>Resumen general de solicitudes realizadas.</p>
            </div>
            <a href="index.html#catalogo" class="btn btn-primary">Nueva solicitud</a>
          </div>

          <div class="orders-grid">
            <article class="order-card">
              <div class="order-head">
                <div><span>Folio</span><strong>PED-2026-00081</strong></div>
                <span class="status-badge status-process">En revisión</span>
              </div>
              <p>Solicitud de 8 unidades SUV y 3 Sedán para entrega programada.</p>
              <div class="order-meta">
                <span>11 unidades</span><span>05 Jul 2026</span><span>$6,420,000 estimado</span>
              </div>
              <button class="btn btn-outline btn-small" data-open-trace="PED-2026-00081">Ver trazabilidad</button>
            </article>

            <article class="order-card">
              <div class="order-head">
                <div><span>Folio</span><strong>PED-2026-00079</strong></div>
                <span class="status-badge status-process">Inventario asignado</span>
              </div>
              <p>Pedido de flotilla con unidades tipo Pickup para operación comercial.</p>
              <div class="order-meta">
                <span>6 unidades</span><span>30 Jun 2026</span><span>$4,190,000 estimado</span>
              </div>
              <button class="btn btn-outline btn-small" data-open-trace="PED-2026-00079">Ver trazabilidad</button>
            </article>

            <article class="order-card">
              <div class="order-head">
                <div><span>Folio</span><strong>PED-2026-00072</strong></div>
                <span class="status-badge status-finished">Finalizado</span>
              </div>
              <p>Solicitud entregada con unidades Sedán y Van para distribuidor regional.</p>
              <div class="order-meta">
                <span>10 unidades</span><span>18 Jun 2026</span><span>$5,780,000 estimado</span>
              </div>
              <button class="btn btn-outline btn-small" data-open-trace="PED-2026-00072">Ver trazabilidad</button>
            </article>
          </div>
        </div>

        <div class="tab-panel" id="proceso">
          <h2>Pedidos en proceso</h2>
          <div class="table-card">
            <table class="orders-table">
              <thead>
                <tr><th>Folio</th><th>Fecha</th><th>Unidades</th><th>Estatus</th><th>Avance</th><th>Acción</th></tr>
              </thead>
              <tbody>
                <tr><td>PED-2026-00081</td><td>05 Jul 2026</td><td>11</td><td><span class="status-badge status-process">En revisión</span></td><td><div class="progress"><span style="width:35%"></span></div></td><td><button class="link-button" data-open-trace="PED-2026-00081">Ver</button></td></tr>
                <tr><td>PED-2026-00079</td><td>30 Jun 2026</td><td>6</td><td><span class="status-badge status-process">Inventario asignado</span></td><td><div class="progress"><span style="width:65%"></span></div></td><td><button class="link-button" data-open-trace="PED-2026-00079">Ver</button></td></tr>
                <tr><td>PED-2026-00075</td><td>22 Jun 2026</td><td>4</td><td><span class="status-badge status-process">Preparación</span></td><td><div class="progress"><span style="width:80%"></span></div></td><td><button class="link-button" data-open-trace="PED-2026-00075">Ver</button></td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="tab-panel" id="finalizados">
          <h2>Pedidos finalizados</h2>
          <div class="table-card">
            <table class="orders-table">
              <thead>
                <tr><th>Folio</th><th>Fecha cierre</th><th>Unidades</th><th>Estatus</th><th>Documento</th></tr>
              </thead>
              <tbody>
                <tr><td>PED-2026-00072</td><td>18 Jun 2026</td><td>10</td><td><span class="status-badge status-finished">Finalizado</span></td><td><button class="btn btn-outline btn-small">Descargar resumen</button></td></tr>
                <tr><td>PED-2026-00068</td><td>09 Jun 2026</td><td>7</td><td><span class="status-badge status-finished">Finalizado</span></td><td><button class="btn btn-outline btn-small">Descargar resumen</button></td></tr>
                <tr><td>PED-2026-00061</td><td>28 May 2026</td><td>14</td><td><span class="status-badge status-finished">Finalizado</span></td><td><button class="btn btn-outline btn-small">Descargar resumen</button></td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="tab-panel" id="trazabilidad">
          <div class="panel-title-row">
            <div>
              <h2>Trazabilidad de solicitud</h2>
              <p id="traceFolioText">Folio seleccionado: PED-2026-00081</p>
            </div>
            <span class="status-badge status-process">En proceso</span>
          </div>

          <div class="trace-detail-grid">
            <div class="trace-box"><span>Solicitante</span><strong>Distribuidora Demo Norte</strong></div>
            <div class="trace-box"><span>Unidades solicitadas</span><strong>11 unidades</strong></div>
            <div class="trace-box"><span>Prioridad</span><strong>Alta</strong></div>
            <div class="trace-box"><span>Entrega estimada</span><strong>15 Jul 2026</strong></div>
          </div>

          <div class="timeline">
            <div class="timeline-item completed">
              <div class="timeline-dot"></div>
              <div>
                <h3>Solicitud generada</h3>
                <p>El distribuidor generó la solicitud desde el carrito.</p>
                <span>05 Jul 2026 · 09:20 AM</span>
              </div>
            </div>
            <div class="timeline-item completed">
              <div class="timeline-dot"></div>
              <div>
                <h3>Revisión comercial</h3>
                <p>Administración revisó cantidades, modelos y observaciones.</p>
                <span>05 Jul 2026 · 11:45 AM</span>
              </div>
            </div>
            <div class="timeline-item active">
              <div class="timeline-dot"></div>
              <div>
                <h3>Validación de inventario</h3>
                <p>Se está confirmando disponibilidad por modelo y versión.</p>
                <span>En proceso</span>
              </div>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot"></div>
              <div>
                <h3>Asignación de unidades</h3>
                <p>Se asignarán unidades disponibles o backorder si aplica.</p>
                <span>Pendiente</span>
              </div>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot"></div>
              <div>
                <h3>Pedido finalizado</h3>
                <p>La solicitud será cerrada al concluir el proceso.</p>
                <span>Pendiente</span>
              </div>
            </div>
          </div>
        </div>
      </section>
    </section>
  </main>

    <?php 
	footerOrders($data);
 ?>