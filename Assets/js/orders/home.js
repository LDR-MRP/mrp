const products = [
  {id:1, name:'Sedán Aurora LX 2026', category:'sedan', model:'Aurora LX', img:'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=900&q=80', detail:'Sedán ejecutivo con alto rendimiento y gran equipamiento.', motor:'1.6L Turbo', transmision:'Automática CVT', disponibilidad:'Alta', combustible:'Gasolina'},
  {id:2, name:'SUV Terra X 2026', category:'suv', model:'Terra X', img:'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=900&q=80', detail:'SUV familiar ideal para ciudad y carretera.', motor:'2.0L', transmision:'Automática 8 vel.', disponibilidad:'Media', combustible:'Gasolina'},
  {id:3, name:'Pickup Brava Pro 2026', category:'pickup', model:'Brava Pro', img:'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=900&q=80', detail:'Pickup de trabajo con gran capacidad de carga.', motor:'2.4L Diesel', transmision:'Manual 6 vel.', disponibilidad:'Alta', combustible:'Diesel'},
  {id:4, name:'Van Cargo Max 2026', category:'van', model:'Cargo Max', img:'https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?auto=format&fit=crop&w=900&q=80', detail:'Van para reparto, flotillas y transporte comercial.', motor:'2.2L', transmision:'Manual 6 vel.', disponibilidad:'Media', combustible:'Diesel'},
  {id:5, name:'Eléctrico Volt E1 2026', category:'electrico', model:'Volt E1', img:'https://images.unsplash.com/photo-1593941707882-a5bba14938c7?auto=format&fit=crop&w=900&q=80', detail:'Unidad eléctrica urbana de bajo costo operativo.', motor:'Motor eléctrico', transmision:'Automática', disponibilidad:'Baja', combustible:'Eléctrico'},
  {id:6, name:'Sedán Nova Comfort', category:'sedan', model:'Nova Comfort', img:'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=900&q=80', detail:'Sedán cómodo para flotillas corporativas.', motor:'1.5L', transmision:'Manual 6 vel.', disponibilidad:'Alta', combustible:'Gasolina'},
  {id:7, name:'SUV Atlas Premium', category:'suv', model:'Atlas Premium', img:'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?auto=format&fit=crop&w=900&q=80', detail:'SUV premium con tecnología de asistencia.', motor:'2.5L', transmision:'Automática', disponibilidad:'Media', combustible:'Gasolina'},
  {id:8, name:'Pickup Titan Work', category:'pickup', model:'Titan Work', img:'https://images.unsplash.com/photo-1563720223185-11003d516935?auto=format&fit=crop&w=900&q=80', detail:'Pickup robusta para carga y operaciones pesadas.', motor:'3.0L Diesel', transmision:'Automática', disponibilidad:'Alta', combustible:'Diesel'},
  {id:9, name:'Van Passenger 15', category:'van', model:'Passenger 15', img:'https://images.unsplash.com/photo-1605893477799-b99e3b8b93fe?auto=format&fit=crop&w=900&q=80', detail:'Van para traslado de personal y rutas empresariales.', motor:'2.8L', transmision:'Manual', disponibilidad:'Media', combustible:'Diesel'},
  {id:10, name:'SUV Compact City', category:'suv', model:'Compact City', img:'https://images.unsplash.com/photo-1542362567-b07e54358753?auto=format&fit=crop&w=900&q=80', detail:'SUV compacta ideal para distribuidores urbanos.', motor:'1.4L Turbo', transmision:'CVT', disponibilidad:'Alta', combustible:'Gasolina'},
  {id:11, name:'Sedán Fleet Basic', category:'sedan', model:'Fleet Basic', img:'https://images.unsplash.com/photo-1553440569-bcc63803a83d?auto=format&fit=crop&w=900&q=80', detail:'Versión económica para volumen de flotillas.', motor:'1.6L', transmision:'Manual', disponibilidad:'Alta', combustible:'Gasolina'},
  {id:12, name:'Eléctrico Urban E2', category:'electrico', model:'Urban E2', img:'https://images.unsplash.com/photo-1619767886558-efdc259cde1a?auto=format&fit=crop&w=900&q=80', detail:'Compacto eléctrico para operación urbana.', motor:'Eléctrico 120 kW', transmision:'Automática', disponibilidad:'Media', combustible:'Eléctrico'},
  {id:13, name:'Pickup Ranch 4x4', category:'pickup', model:'Ranch 4x4', img:'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=900&q=80', detail:'Pickup 4x4 preparada para terrenos complicados.', motor:'2.8L Diesel', transmision:'Automática', disponibilidad:'Baja', combustible:'Diesel'},
  {id:14, name:'SUV Family Plus', category:'suv', model:'Family Plus', img:'https://images.unsplash.com/photo-1606220838315-056192d5e927?auto=format&fit=crop&w=900&q=80', detail:'SUV de 7 pasajeros con gran espacio interior.', motor:'2.0L Turbo', transmision:'Automática', disponibilidad:'Alta', combustible:'Gasolina'},
  {id:15, name:'Sedán Sport GT', category:'sedan', model:'Sport GT', img:'https://images.unsplash.com/photo-1549924231-f129b911e442?auto=format&fit=crop&w=900&q=80', detail:'Sedán deportivo con diseño atractivo.', motor:'2.0L Turbo', transmision:'Automática', disponibilidad:'Media', combustible:'Gasolina'},
  {id:16, name:'Van Refrigerada Pro', category:'van', model:'Refrigerada Pro', img:'https://images.unsplash.com/photo-1517524008697-84bbe3c3fd98?auto=format&fit=crop&w=900&q=80', detail:'Van para logística especializada y cadena fría.', motor:'2.5L Diesel', transmision:'Manual', disponibilidad:'Baja', combustible:'Diesel'},
  {id:17, name:'Eléctrico SUV E-Motion', category:'electrico', model:'E-Motion', img:'https://images.unsplash.com/photo-1617788138017-80ad40651399?auto=format&fit=crop&w=900&q=80', detail:'SUV eléctrica de alto equipamiento.', motor:'Dual motor', transmision:'Automática', disponibilidad:'Media', combustible:'Eléctrico'},
  {id:18, name:'Pickup Chasis Cabina', category:'pickup', model:'Chasis Cabina', img:'https://images.unsplash.com/photo-1597007066704-67bf2068d5b2?auto=format&fit=crop&w=900&q=80', detail:'Unidad adaptable para cajas, redilas o plataforma.', motor:'2.5L Diesel', transmision:'Manual', disponibilidad:'Alta', combustible:'Diesel'},
  {id:19, name:'SUV Executive Black', category:'suv', model:'Executive Black', img:'https://images.unsplash.com/photo-1555215695-3004980ad54e?auto=format&fit=crop&w=900&q=80', detail:'SUV ejecutiva para clientes premium.', motor:'3.0L', transmision:'Automática', disponibilidad:'Baja', combustible:'Gasolina'},
  {id:20, name:'Sedán Hybrid Eco', category:'sedan', model:'Hybrid Eco', img:'https://images.unsplash.com/photo-1580273916550-e323be2ae537?auto=format&fit=crop&w=900&q=80', detail:'Sedán híbrido de bajo consumo.', motor:'1.8L Híbrido', transmision:'CVT', disponibilidad:'Media', combustible:'Híbrido'}
];

const getCart = () => JSON.parse(localStorage.getItem('cartUnits')) || [];
const saveCart = cart => localStorage.setItem('cartUnits', JSON.stringify(cart));

function updateCartCount() {
  const count = getCart().reduce((sum, item) => sum + item.qty, 0);
  document.querySelectorAll('#cartCount').forEach(el => el.textContent = count);
}

function renderProducts(list = products) {
  const grid = document.getElementById('productsGrid');
  if (!grid) return;
  grid.innerHTML = list.map(p => `
    <article class="product-card">
      <div class="product-img"><img src="${p.img}" alt="${p.name}"></div>
      <div class="product-body">
        <h3>${p.name}</h3>
        <p>${p.detail}</p>
        <div class="product-meta"><span>${p.motor}</span><span>${p.disponibilidad}</span></div>
        <div class="product-actions">
          <a class="btn btn-outline" href="detalle.html?id=${p.id}">Ver detalle</a>
          <button class="btn btn-primary" onclick="addToCart(${p.id})">Agregar</button>
        </div>
      </div>
    </article>
  `).join('');
}

function addToCart(id, qty = 1) {
  const product = products.find(p => p.id === id);
  const cart = getCart();
  const found = cart.find(item => item.id === id);
  if (found) found.qty += Number(qty);
  else cart.push({ id: product.id, qty: Number(qty) });
  saveCart(cart);
  updateCartCount();
  alert('Unidad agregada al carrito de pedido');
}

function filterProducts() {
  const search = document.getElementById('searchInput')?.value.toLowerCase() || '';
  const category = document.getElementById('categoryFilter')?.value || 'todos';
  const filtered = products.filter(p => {
    const matchesText = `${p.name} ${p.model} ${p.detail} ${p.category}`.toLowerCase().includes(search);
    const matchesCategory = category === 'todos' || p.category === category;
    return matchesText && matchesCategory;
  });
  renderProducts(filtered);
}

function renderDetail() {
  const container = document.getElementById('detailContainer');
  if (!container) return;
  const id = Number(new URLSearchParams(location.search).get('id')) || 1;
  const p = products.find(item => item.id === id) || products[0];
  container.innerHTML = `
    <div class="zoom-box">
      <div class="zoom-image-wrap" id="zoomWrap">
        <img id="zoomImage" src="${p.img}" alt="${p.name}">
      </div>
    </div>
    <div class="detail-info">
      <span class="tag">${p.category.toUpperCase()}</span>
      <h1>${p.name}</h1>
      <p>${p.detail} Esta vista permite al distribuidor revisar la información comercial y técnica antes de agregar la unidad a su solicitud de pedido.</p>
      <div class="specs">
        <div class="spec"><span>Modelo</span><strong>${p.model}</strong></div>
        <div class="spec"><span>Motor</span><strong>${p.motor}</strong></div>
        <div class="spec"><span>Transmisión</span><strong>${p.transmision}</strong></div>
        <div class="spec"><span>Combustible</span><strong>${p.combustible}</strong></div>
        <div class="spec"><span>Disponibilidad</span><strong>${p.disponibilidad}</strong></div>
        <div class="spec"><span>Tipo</span><strong>${p.category}</strong></div>
      </div>
      <div class="quantity-box">
        <label for="qty"><strong>Cantidad:</strong></label>
        <input id="qty" type="number" min="1" value="1">
      </div>
      <button class="btn btn-primary btn-large" onclick="addToCart(${p.id}, document.getElementById('qty').value)">Agregar al carrito</button>
    </div>
  `;
  activateZoom();
}

function activateZoom() {
  const wrap = document.getElementById('zoomWrap');
  const img = document.getElementById('zoomImage');
  if (!wrap || !img) return;
  wrap.addEventListener('mousemove', e => {
    const rect = wrap.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / rect.width) * 100;
    const y = ((e.clientY - rect.top) / rect.height) * 100;
    img.style.transformOrigin = `${x}% ${y}%`;
    img.style.transform = 'scale(2.1)';
  });
  wrap.addEventListener('mouseleave', () => {
    img.style.transformOrigin = 'center center';
    img.style.transform = 'scale(1)';
  });
}

function renderCart() {
  const container = document.getElementById('cartItems');
  if (!container) return;
  const cart = getCart();
  if (!cart.length) {
    container.innerHTML = `<div class="empty-cart"><h3>Tu carrito está vacío</h3><p>Agrega unidades desde el catálogo para generar una solicitud de pedido.</p></div>`;
  } else {
    container.innerHTML = cart.map(item => {
      const p = products.find(prod => prod.id === item.id);
      return `
        <article class="cart-item">
          <img src="${p.img}" alt="${p.name}">
          <div>
            <h3>${p.name}</h3>
            <p>${p.detail}</p>
            <p><strong>Modelo:</strong> ${p.model} · <strong>Disponibilidad:</strong> ${p.disponibilidad}</p>
          </div>
          <div class="cart-actions">
            <button onclick="changeQty(${p.id}, -1)">−</button>
            <strong>${item.qty}</strong>
            <button onclick="changeQty(${p.id}, 1)">+</button>
            <button class="remove-btn" onclick="removeFromCart(${p.id})">Eliminar</button>
          </div>
        </article>
      `;
    }).join('');
  }
  document.getElementById('totalModels').textContent = cart.length;
  document.getElementById('totalUnits').textContent = cart.reduce((sum, item) => sum + item.qty, 0);
}

function changeQty(id, delta) {
  let cart = getCart().map(item => item.id === id ? {...item, qty: item.qty + delta} : item).filter(item => item.qty > 0);
  saveCart(cart);
  updateCartCount();
  renderCart();
}

function removeFromCart(id) {
  saveCart(getCart().filter(item => item.id !== id));
  updateCartCount();
  renderCart();
}

function generateRequest() {
  const cart = getCart();
  if (!cart.length) return alert('Agrega al menos una unidad para generar la solicitud.');
  alert('Solicitud generada correctamente. Aquí después puedes conectarlo con tu backend para guardar el pedido.');
}

document.getElementById('menuToggle')?.addEventListener('click', () => document.getElementById('navMenu').classList.toggle('active'));
document.getElementById('searchInput')?.addEventListener('input', filterProducts);
document.getElementById('categoryFilter')?.addEventListener('change', filterProducts);

renderProducts();
renderDetail();
renderCart();
updateCartCount();

// Filtro rápido desde las tarjetas de modelos
function activateModelLinks() {
  document.querySelectorAll('.modelo-link').forEach(link => {
    link.addEventListener('click', () => {
      const filter = link.dataset.filter;
      const category = document.getElementById('categoryFilter');
      if (category && filter) {
        category.value = filter;
        filterProducts();
      }
    });
  });
}
activateModelLinks();
