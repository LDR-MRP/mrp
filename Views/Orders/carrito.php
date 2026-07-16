 <?php 
	headerOrders($data);
 ?>
  <main class="cart-page">
    <div class="container">
      <div class="section-title left">
        <span>Solicitud de pedido</span>
        <h2>Unidades agregadas al carrito</h2>
      </div>
      <div class="cart-layout">
        <section class="cart-items" id="cartItems"></section>
        <aside class="cart-summary">
          <h3>Resumen de solicitud</h3>
          <div class="summary-row"><span>Total modelos</span><strong id="totalModels">0</strong></div>
          <div class="summary-row"><span>Total unidades</span><strong id="totalUnits">0</strong></div>
          <label>Notas de pedido</label>
          <textarea placeholder="Ejemplo: Prioridad alta, entregar en planta Toluca, colores preferentes..."></textarea>
          <button class="btn btn-primary full" onclick="generateRequest()">Generar solicitud de pedido</button>
          <a href="index.html#catalogo" class="btn btn-outline full">Agregar más unidades</a>
        </aside>
      </div>
    </div>
  </main>

      <?php 
	footerOrders($data);
 ?>