function setupTabs(){
  document.querySelectorAll('.tab-button').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));

      btn.classList.add('active');
      document.getElementById(btn.dataset.tab)?.classList.add('active');
    });
  });

  document.querySelectorAll('[data-open-trace]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelector('[data-tab="trazabilidad"]')?.click();
      const text = document.getElementById('traceFolioText');
      if(text) text.textContent = 'Folio seleccionado: ' + btn.dataset.openTrace;
    });
  });
}

document.addEventListener('DOMContentLoaded', setupTabs);
