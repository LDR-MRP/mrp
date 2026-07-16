const products = [
  {id:1,nombre:'SUV Alpha X',cat:'suv',precio:'$685,000',img:'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?auto=format&fit=crop&w=900&q=80',desc:'SUV automática, gasolina, ideal para familias y ejecutivos.'},
  {id:2,nombre:'Sedán Nova 1.6',cat:'sedan',precio:'$389,000',img:'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=900&q=80',desc:'Sedán de alto desplazamiento comercial para flotillas.'},
  {id:3,nombre:'Pickup Titan Pro',cat:'pickup',precio:'$742,000',img:'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=900&q=80',desc:'Pickup para trabajo, carga y operación diaria.'},
  {id:4,nombre:'Van Cargo Max',cat:'van',precio:'$620,000',img:'https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?auto=format&fit=crop&w=900&q=80',desc:'Van comercial para reparto y transporte de personal.'},
  {id:5,nombre:'E-Drive City',cat:'electrico',precio:'$810,000',img:'https://images.unsplash.com/photo-1593941707882-a5bba14938c7?auto=format&fit=crop&w=900&q=80',desc:'Unidad eléctrica para movilidad urbana eficiente.'},
  {id:6,nombre:'SUV Terra Plus',cat:'suv',precio:'$715,000',img:'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=900&q=80',desc:'SUV amplia con equipamiento premium.'},
  {id:7,nombre:'Sedán Executive',cat:'sedan',precio:'$445,000',img:'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=900&q=80',desc:'Sedán ejecutivo para clientes corporativos.'},
  {id:8,nombre:'Pickup Work 4x2',cat:'pickup',precio:'$689,000',img:'https://images.unsplash.com/photo-1563720223185-11003d516935?auto=format&fit=crop&w=900&q=80',desc:'Pickup práctica para trabajo pesado.'}
];

function getCart(){
  return JSON.parse(localStorage.getItem('cartAD') || '[]');
}

function setCart(cart){
  localStorage.setItem('cartAD', JSON.stringify(cart));
  updateCartCount();
}

function updateCartCount(){
  const el = document.getElementById('cartCount');
  if(el){
    el.textContent = getCart().reduce((total,item) => total + item.qty, 0);
  }
}

function addToCart(id){
  const item = products.find(p => p.id === Number(id));
  if(!item) return;

  const cart = getCart();
  const found = cart.find(p => p.id === item.id);

  if(found){
    found.qty++;
  }else{
    cart.push({...item, qty: 1});
  }

  setCart(cart);
  alert('Unidad agregada al carrito');
}

function setupMenu(){
  document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('navMenu')?.classList.toggle('open');
  });
}

function renderProducts(list = products){
  const grid = document.getElementById('productsGrid');
  if(!grid) return;

  grid.innerHTML = list.map(p => `
    <article class="product-card">
      <img src="${p.img}" alt="${p.nombre}">
      <div class="product-body">
        <h3>${p.nombre}</h3>
        <p>${p.desc}</p>
        <div class="product-meta">
          <span>${p.cat.toUpperCase()}</span>
          <span>${p.precio}</span>
        </div>
        <div class="product-actions">
          <a class="btn btn-outline btn-small" href="detalle.html?id=${p.id}">Ver detalle</a>
          <button class="btn btn-primary btn-small" onclick="addToCart(${p.id})">Agregar</button>
        </div>
      </div>
    </article>
  `).join('');
}

function filterProducts(){
  const q = (document.getElementById('searchInput')?.value || '').toLowerCase();
  const c = document.getElementById('categoryFilter')?.value || 'todos';

  const list = products.filter(p =>
    (c === 'todos' || p.cat === c) &&
    (p.nombre.toLowerCase().includes(q) || p.desc.toLowerCase().includes(q) || p.cat.includes(q))
  );

  renderProducts(list);
}

function setupModelLinks(){
  document.querySelectorAll('.modelo-link').forEach(a => {
    a.addEventListener('click', () => {
      const filter = a.dataset.filter;
      const select = document.getElementById('categoryFilter');
      if(select){
        select.value = filter;
        setTimeout(filterProducts, 50);
      }
    });
  });
}

function setupDetail(){
  const detail = document.getElementById('detailProduct');
  if(!detail) return;

  const id = new URLSearchParams(location.search).get('id') || 1;
  const p = products.find(x => x.id === Number(id)) || products[0];

  detail.innerHTML = `
    <div class="hero-card">
      <img id="zoomImg" src="${p.img}" alt="${p.nombre}">
    </div>
    <div>
      <span class="tag">${p.cat.toUpperCase()}</span>
      <h1>${p.nombre}</h1>
      <p>${p.desc}</p>
      <h2>${p.precio}</h2>
      <button class="btn btn-primary" onclick="addToCart(${p.id})">Agregar al carrito</button>
    </div>
  `;

  const img = document.getElementById('zoomImg');
  img?.addEventListener('mousemove', e => {
    const r = img.getBoundingClientRect();
    img.style.transformOrigin = `${((e.clientX - r.left) / r.width) * 100}% ${((e.clientY - r.top) / r.height) * 100}%`;
    img.style.transform = 'scale(1.8)';
  });
  img?.addEventListener('mouseleave', () => img.style.transform = 'scale(1)');
}

document.addEventListener('DOMContentLoaded', () => {
  updateCartCount();
  setupMenu();
  renderProducts();
  setupModelLinks();
  setupDetail();

  document.getElementById('searchInput')?.addEventListener('input', filterProducts);
  document.getElementById('categoryFilter')?.addEventListener('change', filterProducts);
});
