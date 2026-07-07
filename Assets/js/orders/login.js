function showAuth(tab){
  document.querySelectorAll('.auth-tab').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.authTab === tab);
  });

  document.getElementById('loginForm')?.classList.toggle('active', tab === 'login');
  document.getElementById('recoverForm')?.classList.toggle('active', tab === 'recover');
}

function setupAuth(){
  document.querySelectorAll('[data-auth-tab]').forEach(btn => {
    btn.addEventListener('click', () => showAuth(btn.dataset.authTab));
  });

  document.querySelector('[data-show-recover]')?.addEventListener('click', () => showAuth('recover'));
  document.querySelector('[data-show-login]')?.addEventListener('click', () => showAuth('login'));

  document.getElementById('togglePassword')?.addEventListener('click', e => {
    const input = document.getElementById('password');
    if(!input) return;

    input.type = input.type === 'password' ? 'text' : 'password';
    e.target.textContent = input.type === 'password' ? 'Ver' : 'Ocultar';
  });

  document.getElementById('recoverForm')?.addEventListener('submit', e => {
    e.preventDefault();
    document.getElementById('recoverMessage')?.classList.add('show');
  });

  document.getElementById('loginForm')?.addEventListener('submit', e => {
    e.preventDefault();
    window.location.href = 'mi-cuenta.html';
  });
}

document.addEventListener('DOMContentLoaded', setupAuth);
