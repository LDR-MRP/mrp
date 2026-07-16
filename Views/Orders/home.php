

<?php 
	headerOrders($data);
 ?>
  <main>
    <section class="hero">
      <div class="container hero-grid">
        <div class="hero-text">
          <span class="tag">Portal B2B automotriz</span>
          <h1>Solicita unidades para tu distribuidora de forma rápida y profesional.</h1>
          <p>Busca por modelo, revisa detalles técnicos, agrega unidades al carrito y genera una solicitud de pedido con trazabilidad.</p>
          <div class="hero-actions">
            <a href="#catalogo" class="btn btn-primary btn-large">Ver catálogo</a>
            <a href="carrito.html" class="btn btn-outline btn-large">Ver carrito</a>
          </div>
        </div>
        <div class="hero-card">
          <img src="<?= media(); ?>/images/order_01.jpg" alt="Unidad automotriz" />
          <div class="hero-card-info">
            <strong>Pedidos centralizados</strong>
            <span>Control de modelos, cantidades y solicitudes.</span>
          </div>
        </div>
      </div>
    </section>

    <section class="search-section" id="modelos">
      <div class="container search-panel">
        <div>
          <h2>Buscar unidades por modelo</h2>
          <p>Filtra unidades disponibles para agregar a tu solicitud de pedido.</p>
        </div>
        <div class="search-box">
          <input type="text" id="searchInput" placeholder="Ejemplo: SUV, Pickup, Sedán, Modelo X..." />
          <select id="categoryFilter">
            <option value="todos">Todos</option>
            <option value="sedan">Sedán</option>
            <option value="suv">SUV</option>
            <option value="pickup">Pickup</option>
            <option value="van">Van</option>
            <option value="electrico">Eléctrico</option>
          </select>
        </div>
      </div>
    </section>

    <!-- <section class="modelos-section" id="familias-modelos">
      <div class="container">
        <div class="section-title modelos-title">
          <span>Modelos</span>
          <h2>Explora por tipo de unidad</h2>
          <p class="section-subtitle">Selecciona una categoría y revisa las unidades disponibles para tu pedido.</p>
        </div>

        <div class="modelos-showcase">
          <article class="modelo-visual-card modelo-wide">
            <img src="https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?auto=format&fit=crop&w=900&q=80" alt="SUV">
            <div class="modelo-overlay">
              <span>SUV</span>
              <h3>Unidades familiares y ejecutivas</h3>
              <a href="#catalogo" class="btn btn-light modelo-link" data-filter="suv">Ver SUV</a>
            </div>
          </article>

          <article class="modelo-visual-card">
            <img src="https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=900&q=80" alt="Sedán">
            <div class="modelo-overlay">
              <span>Sedán</span>
              <h3>Flotillas y venta constante</h3>
              <a href="#catalogo" class="btn btn-light modelo-link" data-filter="sedan">Ver sedanes</a>
            </div>
          </article>

          <article class="modelo-visual-card">
            <img src="https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=900&q=80" alt="Pickup">
            <div class="modelo-overlay">
              <span>Pickup</span>
              <h3>Trabajo, carga y operación</h3>
              <a href="#catalogo" class="btn btn-light modelo-link" data-filter="pickup">Ver pickups</a>
            </div>
          </article>

          <article class="modelo-visual-card">
            <img src="https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?auto=format&fit=crop&w=900&q=80" alt="Van">
            <div class="modelo-overlay">
              <span>Van</span>
              <h3>Transporte y reparto</h3>
              <a href="#catalogo" class="btn btn-light modelo-link" data-filter="van">Ver vans</a>
            </div>
          </article>

          <article class="modelo-visual-card">
            <img src="https://images.unsplash.com/photo-1593941707882-a5bba14938c7?auto=format&fit=crop&w=900&q=80" alt="Eléctrico">
            <div class="modelo-overlay">
              <span>Eléctrico</span>
              <h3>Movilidad eficiente</h3>
              <a href="#catalogo" class="btn btn-light modelo-link" data-filter="electrico">Ver eléctricos</a>
            </div>
          </article>
        </div>
      </div>
    </section> -->

    <section class="catalog" id="catalogo">
      <div class="container">
        <div class="section-title">
          <span>Catálogo</span>
          <h2>Unidades disponibles para distribuidores</h2>
        </div>
        <div class="products-grid" id="productsGrid"></div>
      </div>
    </section>

    <section class="benefits" id="beneficios">
      <div class="container">
        <div class="section-title">
          <span>Beneficios</span>
          <h2>Beneficios del portal para distribuidores</h2>
          <p class="section-subtitle">Una experiencia tipo e-commerce, pero enfocada en solicitudes B2B de unidades automotrices.</p>
        </div>

        <div class="benefits-grid benefits-grid-extended">
          <div class="benefit-card">
            <span>01</span>
            <h3>Solicitud ordenada</h3>
            <p>Genera pedidos con modelo, versión, cantidad y notas comerciales para evitar solicitudes incompletas.</p>
          </div>
          <div class="benefit-card">
            <span>02</span>
            <h3>Búsqueda por modelo</h3>
            <p>Encuentra unidades por nombre, categoría, tipo de combustible o segmento comercial.</p>
          </div>
          <div class="benefit-card">
            <span>03</span>
            <h3>Detalle de unidad</h3>
            <p>Consulta imágenes con zoom, especificaciones, disponibilidad y características antes de pedir.</p>
          </div>
          <div class="benefit-card">
            <span>04</span>
            <h3>Carrito B2B</h3>
            <p>Consolida varios modelos en una sola solicitud con cantidades por unidad.</p>
          </div>
          <div class="benefit-card">
            <span>05</span>
            <h3>Control comercial</h3>
            <p>Permite que administración revise la demanda, disponibilidad y prioridad de cada pedido.</p>
          </div>
          <div class="benefit-card">
            <span>06</span>
            <h3>Base para trazabilidad</h3>
            <p>La vista queda lista para conectarse con backend y registrar folios, estatus y bitácora del pedido.</p>
          </div>
        </div>

        <div class="process-panel">
          <div>
            <span class="tag">Flujo sugerido</span>
            <h3>Del catálogo a la solicitud de pedido</h3>
            <p>El distribuidor inicia sesión, consulta modelos, agrega unidades al carrito y envía una solicitud formal para revisión interna.</p>
          </div>
          <div class="process-steps">
            <div><strong>1</strong><span>Buscar modelo</span></div>
            <div><strong>2</strong><span>Ver detalle</span></div>
            <div><strong>3</strong><span>Agregar al carrito</span></div>
            <div><strong>4</strong><span>Generar solicitud</span></div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <?php 
	footerOrders($data);
 ?>

