
(function () {
  const card = document.getElementById("timeTrackerCard");
  if (!card) return;

  const elMain = document.getElementById("ttMain");
  const elSub  = document.getElementById("ttSubtitle");
  const elHint = document.getElementById("ttHint");

  const btnStart = document.getElementById("ttStart");
  const btnStop  = document.getElementById("ttStop");

  const fechaInicioRaw = (card.dataset.fechaInicio || "").trim();
  const fechaReqRaw    = (card.dataset.fechaRequerida || "").trim();

  function parseLocalDateTime(s) {
    if (!s) return null;


    if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
      return new Date(s + "T00:00:00");
    }

    // "YYYY-MM-DD HH:mm:ss" o "YYYY-MM-DD HH:mm"
    if (/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/.test(s)) {
      const iso = s.replace(" ", "T");

      return new Date(iso.length === 16 ? iso + ":00" : iso);
    }

    const d = new Date(s);
    return isNaN(d.getTime()) ? null : d;
  }

  const dtInicio = parseLocalDateTime(fechaInicioRaw);
  const dtReq    = parseLocalDateTime(fechaReqRaw);


  let tick = null;
  let running = true;

  // -------------------------------------------------------
  // Format ms -> "X días Y horas Z min W seg"
  // -------------------------------------------------------
  function formatDelta(ms) {
    const abs = Math.abs(ms);

    const sec = Math.floor(abs / 1000);
    const days = Math.floor(sec / 86400);
    const hours = Math.floor((sec % 86400) / 3600);
    const mins = Math.floor((sec % 3600) / 60);
    const secs = sec % 60;

    const parts = [];
    if (days > 0) parts.push(`${days} día${days === 1 ? "" : "s"}`);
    parts.push(`${hours} hora${hours === 1 ? "" : "s"}`);
    parts.push(`${mins} min`);
    parts.push(`${secs} seg`);

    return parts.join(" ");
  }

  // -------------------------------------------------------
  // Render principal
  // -------------------------------------------------------
  function render() {
    if (!dtInicio || !dtReq) {
      elMain.textContent = "—";
      elSub.textContent = "Fechas no válidas";
      elHint.textContent = "Verifica fecha_inicio y fecha_requerida en tu respuesta.";
      return;
    }

    const now = new Date();

    // 1) Aún no inicia
    if (now < dtInicio) {
      const msToStart = dtInicio - now;
      elMain.textContent = formatDelta(msToStart);
      elSub.textContent = "Inicia en";
      elHint.textContent = "Cuenta regresiva al inicio de producción.";
      return;
    }

    // 2) Ya inició (restante al requerido)
    if (now <= dtReq) {
      const msRemaining = dtReq - now;
      elMain.textContent = formatDelta(msRemaining);
      elSub.textContent = "Tiempo restante (fecha requerida)";
      const elapsed = now - dtInicio;
      elHint.textContent = `Transcurrido desde inicio: ${formatDelta(elapsed)}.`;
      return;
    }

    // 3) Vencido
    const msOver = now - dtReq;
    elMain.textContent = formatDelta(msOver);
    elSub.textContent = "Vencida por";
    elHint.textContent = "Ya pasó la fecha requerida.";
  }

  function start() {
    if (tick) return;
    running = true;
    render();
    tick = setInterval(render, 1000);

    // UI
    btnStart.classList.remove("btn-soft-secondary");
    btnStart.classList.add("btn-soft-success");
    btnStart.innerHTML = '<i class="ri-play-circle-line align-bottom me-1"></i>Producción en curso';

    btnStop.disabled = false;
  }

  function stop() {
    running = false;
    if (tick) clearInterval(tick);
    tick = null;

    // UI
    btnStart.classList.remove("btn-soft-success");
    btnStart.classList.add("btn-soft-secondary");
    btnStart.innerHTML = '<i class="ri-play-circle-line align-bottom me-1"></i>Iniciar producción';

    btnStop.disabled = true;
  }

  // Eventos
  btnStart?.addEventListener("click", () => {
    // Si está corriendo, no hace nada; si está detenido, arranca.
    if (!running) start();
  });

  btnStop?.addEventListener("click", () => stop());

  // Auto start
  start();
})();
