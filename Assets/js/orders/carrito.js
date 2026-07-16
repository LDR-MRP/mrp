function renderCart(){
  const wrap = document.getElementById('cartItems');
  if(!wrap) return;

  const cart = getCart();
  const totalModels = document.getElementById('totalModels');
  const totalUnits = document.getElementById('totalUnits');

  if(totalModels) totalModels.textContent = cart.length;
  if(totalUnits) totalUnits.textContent = cart.reduce((total,item) => total + item.qty, 0);

  if(cart.length === 0){
    wrap.innerHTML = `
      <div class="order-card">
        <h3>Tu carrito está vacío</h3>
        <p>Regresa al catálogo y agrega unidades para generar una solicitud.</p>
        <a href="index.html#catalogo" class="btn btn-primary">Ver catálogo</a>
      </div>
    `;
    return;
  }

  wrap.innerHTML = cart.map(item => `
    <article class="order-card">
      <div class="order-head">
        <div>
          <span>Unidad</span>
          <strong>${item.nombre}</strong>
        </div>
        <span class="status-badge status-process">${item.cat}</span>
      </div>
      <p>${item.desc}</p>
      <div class="order-meta">
        <span>Cantidad: ${item.qty}</span>
        <span>${item.precio}</span>
      </div>
      <button class="btn btn-outline btn-small" onclick="removeCart(${item.id})">Eliminar</button>
    </article>
  `).join('');
}

function removeCart(id){
  const cart = getCart().filter(item => item.id !== Number(id));
  setCart(cart);
  renderCart();
}

function generateRequest(){
  const cart = getCart();

  if(!cart.length){
    alert('Agrega unidades antes de generar la solicitud.');
    return;
  }

  alert('Solicitud generada correctamente. Folio demo: PED-2026-00082');
  localStorage.removeItem('cartAD');
  updateCartCount();
  renderCart();
}

document.addEventListener('DOMContentLoaded', renderCart);
