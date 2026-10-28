<?php headerAdmin($data);
?>
<style>
    /* =====================================================================
       Inventario de unidades PT — estilos del módulo (prefijo iu-)
       Usa solo variables del tema (--vz-*) para funcionar en claro y oscuro.
       ===================================================================== */
    .iu-wrap { --iu-radius: .75rem; --iu-muted: var(--vz-secondary-color, #878a99); }
    .iu-wrap .tnum { font-variant-numeric: tabular-nums; }

    /* ---------- Encabezado ---------- */
    .iu-head { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; justify-content: space-between; margin-bottom: 1.25rem; }
    .iu-crumb { font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; color: var(--iu-muted); margin-bottom: .2rem; }
    .iu-head h4 { font-size: 1.35rem; font-weight: 600; margin: 0; }
    .iu-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem .9rem; margin-top: .4rem; font-size: .8rem; color: var(--iu-muted); }
    .iu-live { display: inline-flex; align-items: center; gap: .4rem; }
    .iu-live::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: rgb(var(--vz-success-rgb)); box-shadow: 0 0 0 3px rgba(var(--vz-success-rgb), .18); }
    .iu-live.err::before { background: rgb(var(--vz-danger-rgb)); box-shadow: 0 0 0 3px rgba(var(--vz-danger-rgb), .18); }
    .iu-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
    .iu-actions .btn { display: inline-flex; align-items: center; gap: .35rem; }
    .iu-spin { animation: iuSpin .8s linear infinite; display: inline-block; }

    /* ---------- KPIs ---------- */
    .iu-kpi { position: relative; height: 100%; padding: 1.1rem 1.2rem; border: 1px solid var(--vz-border-color); border-radius: var(--iu-radius);
              background: var(--vz-card-bg); box-shadow: 0 1px 2px rgba(56, 65, 74, .06); overflow: hidden; transition: box-shadow .2s, transform .2s; }
    .iu-kpi:hover { box-shadow: 0 6px 18px rgba(56, 65, 74, .10); transform: translateY(-1px); }
    .iu-kpi::after { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: rgb(var(--iu-acc)); opacity: .9; }
    .iu-kpi .top { display: flex; justify-content: space-between; align-items: flex-start; gap: .75rem; }
    .iu-kpi .lbl { font-size: .72rem; font-weight: 500; text-transform: uppercase; letter-spacing: .06em; color: var(--iu-muted); }
    .iu-kpi .val { font-size: 1.85rem; font-weight: 600; line-height: 1.1; margin-top: .35rem; font-variant-numeric: tabular-nums; color: var(--vz-heading-color, inherit); }
    .iu-kpi .sub { font-size: .78rem; color: var(--iu-muted); margin-top: .3rem; min-height: 1.1rem; }
    .iu-ico { flex-shrink: 0; width: 42px; height: 42px; border-radius: .6rem; display: grid; place-items: center; font-size: 1.3rem;
              color: rgb(var(--iu-acc)); background: rgba(var(--iu-acc), .12); }
    .iu-acc-primary { --iu-acc: var(--vz-primary-rgb); }
    .iu-acc-success { --iu-acc: var(--vz-success-rgb); }
    .iu-acc-warning { --iu-acc: var(--vz-warning-rgb); }
    .iu-acc-info    { --iu-acc: var(--vz-info-rgb); }
    .iu-chips { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .55rem; }
    .iu-chip { display: inline-flex; gap: .3rem; align-items: center; padding: .12rem .5rem; border-radius: 1rem;
               background: var(--vz-light); font-size: .74rem; white-space: nowrap; }
    .iu-chip b { font-weight: 600; font-variant-numeric: tabular-nums; }
    .iu-stack { display: flex; height: 10px; border-radius: 5px; overflow: hidden; background: var(--vz-light); margin: .8rem 0 .6rem; gap: 2px; }
    .iu-stack span { display: block; height: 100%; transition: width .4s ease; }
    .iu-leg { display: flex; flex-wrap: wrap; gap: .2rem .35rem; }
    .iu-leg button { display: inline-flex; align-items: center; gap: .3rem; border: 0; background: transparent; padding: .1rem .35rem; border-radius: .35rem;
                     font-size: .72rem; color: inherit; cursor: pointer; }
    .iu-leg button:hover { background: var(--vz-light); }
    .iu-leg button.on { background: rgba(var(--vz-primary-rgb), .12); color: rgb(var(--vz-primary-rgb)); }
    .iu-leg i { display: inline-block; width: 8px; height: 8px; border-radius: 2px; }
    .iu-leg b { font-weight: 600; font-variant-numeric: tabular-nums; }

    /* ---------- Tarjeta principal y barra de herramientas ---------- */
    .iu-card { border: 1px solid var(--vz-border-color); border-radius: var(--iu-radius); background: var(--vz-card-bg);
               box-shadow: 0 1px 2px rgba(56, 65, 74, .06); margin-bottom: 1.5rem; }
    .iu-bar { display: flex; flex-wrap: wrap; gap: .75rem 1rem; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid var(--vz-border-color); }
    .iu-tabs { display: inline-flex; padding: 3px; gap: 2px; border-radius: .6rem; background: var(--vz-light); }
    .iu-tab { display: inline-flex; align-items: center; gap: .4rem; border: 0; background: transparent; color: var(--iu-muted);
              padding: .4rem .8rem; border-radius: .45rem; font-size: .82rem; font-weight: 500; cursor: pointer; transition: background .15s, color .15s; }
    .iu-tab:hover { color: var(--vz-body-color); }
    .iu-tab.on { background: var(--vz-card-bg); color: rgb(var(--vz-primary-rgb)); box-shadow: 0 1px 3px rgba(0, 0, 0, .12); }
    .iu-tab i { font-size: 1rem; }
    .iu-tab .n { font-size: .68rem; font-weight: 600; padding: 0 .4rem; border-radius: 1rem; background: rgba(var(--vz-primary-rgb), .1);
                 color: rgb(var(--vz-primary-rgb)); font-variant-numeric: tabular-nums; }
    .iu-tab:not(.on) .n { background: var(--vz-border-color); color: var(--iu-muted); }
    .iu-busca { position: relative; flex: 1 1 260px; max-width: 380px; }
    .iu-busca input { padding-left: 2.1rem; padding-right: 2.2rem; border-radius: .5rem; }
    .iu-busca input::-webkit-search-cancel-button { -webkit-appearance: none; display: none; }
    .iu-busca > i { position: absolute; left: .7rem; top: 50%; transform: translateY(-50%); color: var(--iu-muted); pointer-events: none; }
    .iu-busca .kbd { position: absolute; right: .55rem; top: 50%; transform: translateY(-50%); font-size: .68rem; padding: .02rem .35rem;
                     border: 1px solid var(--vz-border-color); border-radius: .3rem; color: var(--iu-muted); pointer-events: none; }
    .iu-busca .clr { position: absolute; right: .35rem; top: 50%; transform: translateY(-50%); border: 0; background: transparent; color: var(--iu-muted);
                     width: 26px; height: 26px; border-radius: 50%; display: none; }
    .iu-busca .clr:hover { background: var(--vz-light); }
    .iu-busca.lleno .clr { display: grid; place-items: center; }
    .iu-busca.lleno .kbd { display: none; }
    .iu-resumen { margin-left: auto; font-size: .8rem; color: var(--iu-muted); white-space: nowrap; }
    .iu-resumen b { color: var(--vz-body-color); font-variant-numeric: tabular-nums; }

    /* ---------- Filtros ---------- */
    .iu-filtros { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1.2fr) minmax(0, 1.4fr); gap: 1rem 1.5rem; padding: 1rem 1.25rem;
                  border-bottom: 1px solid var(--vz-border-color); background: rgba(var(--vz-primary-rgb), .015); }
    .iu-fgrupo > .lbl { display: block; font-size: .68rem; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; color: var(--iu-muted); margin-bottom: .45rem; }
    .iu-fopts { display: flex; flex-wrap: wrap; gap: .35rem; }
    .iu-selects { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; }
    .iu-selects label { display: block; font-size: .68rem; color: var(--iu-muted); margin-bottom: .2rem; }
    .iu-toggle { display: inline-flex; align-items: center; gap: .35rem; border: 1px solid var(--vz-border-color); background: var(--vz-card-bg);
                 color: var(--vz-body-color); border-radius: 2rem; padding: .22rem .7rem; font-size: .78rem; line-height: 1.45; cursor: pointer;
                 transition: border-color .15s, background .15s, color .15s; }
    .iu-toggle:hover { border-color: rgba(var(--vz-primary-rgb), .6); }
    .iu-toggle.on { background: rgb(var(--vz-primary-rgb)); border-color: rgb(var(--vz-primary-rgb)); color: #fff; }
    .iu-toggle.vacio:not(.on) { opacity: .45; }
    .iu-toggle .n { font-size: .7rem; opacity: .7; font-variant-numeric: tabular-nums; }
    .iu-toggle .sw { width: 8px; height: 8px; border-radius: 50%; }
    .iu-toggle.on .sw { box-shadow: 0 0 0 2px rgba(255, 255, 255, .8); }

    .iu-activos { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; padding: .65rem 1.25rem; border-bottom: 1px solid var(--vz-border-color);
                  font-size: .78rem; }
    .iu-activos .tit { color: var(--iu-muted); margin-right: .25rem; }
    .iu-pill { display: inline-flex; align-items: center; gap: .3rem; padding: .15rem .25rem .15rem .6rem; border-radius: 2rem;
               background: rgba(var(--vz-primary-rgb), .1); color: rgb(var(--vz-primary-rgb)); font-weight: 500; }
    .iu-pill small { opacity: .75; font-weight: 400; }
    .iu-pill button { border: 0; background: transparent; color: inherit; width: 18px; height: 18px; border-radius: 50%; display: grid; place-items: center; padding: 0; }
    .iu-pill button:hover { background: rgba(var(--vz-primary-rgb), .18); }

    .iu-body { padding: 1.25rem; }
    .iu-panel { animation: iuFade .25s ease; }

    /* ---------- Vista: matriz por unidad ---------- */
    .iu-mbar { display: flex; flex-wrap: wrap; gap: .5rem 1rem; align-items: center; justify-content: space-between; margin-bottom: .75rem; }
    .iu-mbar .btn-link { font-size: .78rem; padding: 0; text-decoration: none; }
    .iu-heat { display: inline-flex; align-items: center; gap: .4rem; font-size: .72rem; color: var(--iu-muted); }
    .iu-heat span { display: inline-flex; gap: 2px; }
    .iu-heat span i { width: 16px; height: 10px; border-radius: 2px; display: block; }
    .iu-scroll { overflow: auto; max-height: calc(100vh - 220px); min-height: 280px; border: 1px solid var(--vz-border-color); border-radius: .6rem; }
    .iu-matriz { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .82rem; }
    .iu-matriz th, .iu-matriz td { padding: .5rem .65rem; border-bottom: 1px solid var(--vz-border-color); white-space: nowrap; }
    .iu-matriz thead th { position: sticky; z-index: 3; background: var(--vz-light); font-size: .68rem; font-weight: 600; text-transform: uppercase;
                          letter-spacing: .05em; color: var(--iu-muted); transition: color .12s, background .12s; }
    .iu-matriz thead tr.plantas th { top: 0; height: 32px; text-align: center; color: var(--vz-body-color); border-bottom: 0; }
    .iu-matriz thead tr.cols th { top: 32px; border-bottom: 2px solid var(--vz-border-color); }
    .iu-matriz thead th.pl { border-left: 1px solid var(--vz-border-color); }
    .iu-matriz thead th.hl { color: rgb(var(--vz-primary-rgb)); background: rgba(var(--vz-primary-rgb), .12); }
    .iu-matriz .num { text-align: right; font-variant-numeric: tabular-nums; min-width: 66px; }
    .iu-matriz .ini-pl { border-left: 1px solid var(--vz-border-color); }
    .iu-matriz .col-mod { position: sticky; left: 0; z-index: 2; background: var(--vz-card-bg); min-width: 280px; max-width: 360px; white-space: normal;
                          box-shadow: 1px 0 0 var(--vz-border-color); }
    .iu-matriz thead .col-mod { z-index: 4; background: var(--vz-light); text-align: left; }
    .iu-matriz .col-tot { font-weight: 600; background: rgba(var(--vz-primary-rgb), .06); }
    .iu-matriz tr.sub td { background: var(--vz-light); font-weight: 600; cursor: pointer; user-select: none; }
    .iu-matriz tr.sub td.col-mod { background: var(--vz-light); box-shadow: inset 3px 0 0 var(--iu-sc), 1px 0 0 var(--vz-border-color); }
    .iu-matriz tr.sub .caret { display: inline-grid; place-items: center; width: 18px; height: 18px; margin-right: .3rem; border-radius: .25rem;
                               transition: transform .15s; color: var(--iu-muted); }
    .iu-matriz tr.sub:hover .caret { background: var(--vz-border-color); }
    .iu-matriz tr.sub.cerrado .caret { transform: rotate(-90deg); }
    .iu-matriz tr.sub .cnt { font-weight: 400; font-size: .72rem; color: var(--iu-muted); margin-left: .35rem; }
    .iu-matriz tr.fila td.col-mod { padding-left: 2rem; cursor: pointer; }
    .iu-matriz tr.fila:hover td { background-color: rgba(var(--vz-primary-rgb), .045); }
    .iu-matriz tr.fila:hover td.col-mod { background: var(--vz-card-bg); }
    .iu-matriz tr.fila:hover .iu-mod-nom { color: rgb(var(--vz-primary-rgb)); }
    .iu-matriz td.cel { cursor: pointer; position: relative; }
    .iu-matriz td.cel:hover { box-shadow: inset 0 0 0 2px rgba(var(--vz-primary-rgb), .7); color: rgb(var(--vz-primary-rgb)); font-weight: 600; }
    .iu-matriz .cero { color: var(--iu-muted); opacity: .35; }
    .iu-matriz tfoot td { position: sticky; bottom: 0; z-index: 2; background: var(--vz-light); font-weight: 700; border-top: 2px solid var(--vz-border-color); border-bottom: 0; }
    .iu-matriz tfoot td.col-mod { z-index: 3; background: var(--vz-light); }
    .iu-mod-nom { font-weight: 500; transition: color .12s; }
    .iu-mod-cve { display: block; font-size: .7rem; color: var(--iu-muted); margin-top: .1rem; }
    .iu-anio { display: inline-block; padding: .02rem .4rem; margin-left: .4rem; border-radius: .3rem; font-size: .68rem; font-weight: 600;
               background: rgba(var(--vz-primary-rgb), .1); color: rgb(var(--vz-primary-rgb)); vertical-align: middle; font-variant-numeric: tabular-nums; }
    .iu-color { display: inline-flex; align-items: center; gap: .3rem; margin-left: .4rem; font-size: .72rem; color: var(--iu-muted); }
    .iu-dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; border: 1px solid rgba(0, 0, 0, .25); vertical-align: middle; flex-shrink: 0; }
    .iu-hint { display: flex; flex-wrap: wrap; gap: .35rem 1.25rem; margin-top: .75rem; font-size: .75rem; color: var(--iu-muted); }
    .iu-hint i { color: rgb(var(--vz-primary-rgb)); margin-right: .2rem; }

    /* ---------- Vista: por almacén ---------- */
    .iu-planta-tit { display: flex; align-items: center; gap: .6rem; margin: .25rem 0 .75rem; font-size: .75rem; font-weight: 600;
                     text-transform: uppercase; letter-spacing: .07em; color: var(--iu-muted); }
    .iu-planta-tit::after { content: ""; flex: 1; height: 1px; background: var(--vz-border-color); }
    .iu-planta-tit .n { font-weight: 500; letter-spacing: 0; text-transform: none; }
    .iu-alm { height: 100%; display: flex; flex-direction: column; border: 1px solid var(--vz-border-color); border-radius: .65rem;
              background: var(--vz-card-bg); overflow: hidden; transition: box-shadow .2s, border-color .2s; }
    .iu-alm:hover { box-shadow: 0 6px 18px rgba(56, 65, 74, .10); border-color: rgba(var(--vz-primary-rgb), .35); }
    .iu-alm-head { padding: .95rem 1.05rem .8rem; border-bottom: 1px solid var(--vz-border-color); }
    .iu-alm-head .r1 { display: flex; justify-content: space-between; align-items: flex-start; gap: .75rem; }
    .iu-alm-head .cve { font-weight: 600; font-size: .92rem; }
    .iu-alm-head .desc { font-size: .74rem; color: var(--iu-muted); }
    .iu-alm-head .tot { font-size: 1.6rem; font-weight: 600; line-height: 1; font-variant-numeric: tabular-nums; text-align: right; }
    .iu-alm-head .tot small { display: block; font-size: .66rem; font-weight: 400; color: var(--iu-muted); margin-top: .2rem; }
    .iu-share { display: flex; align-items: center; gap: .5rem; margin-top: .65rem; font-size: .7rem; color: var(--iu-muted); }
    .iu-share .trk { flex: 1; height: 5px; border-radius: 3px; background: var(--vz-light); overflow: hidden; }
    .iu-share .trk span { display: block; height: 100%; background: rgb(var(--vz-primary-rgb)); border-radius: 3px; }
    .iu-alm-list { list-style: none; margin: 0; padding: .35rem 0; max-height: 320px; overflow-y: auto; flex: 1; }
    .iu-alm-list li { display: grid; grid-template-columns: 1fr auto; gap: .2rem .75rem; padding: .45rem 1.05rem; cursor: pointer; font-size: .8rem; }
    .iu-alm-list li:hover { background: rgba(var(--vz-primary-rgb), .05); }
    .iu-alm-list li:hover .m { color: rgb(var(--vz-primary-rgb)); }
    .iu-alm-list .bar { grid-column: 1 / -1; height: 4px; border-radius: 2px; background: var(--vz-light); overflow: hidden; }
    .iu-alm-list .bar span { display: block; height: 100%; border-radius: 2px; }
    .iu-alm-list .n { font-weight: 600; font-variant-numeric: tabular-nums; }
    .iu-alm-list .subl { font-size: .66rem; color: var(--iu-muted); margin-left: .35rem; text-transform: uppercase; letter-spacing: .03em; }

    /* ---------- Vista: listado ---------- */
    #tListado thead th { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--iu-muted); background: var(--vz-light); white-space: nowrap; }
    #tListado td { font-size: .8rem; white-space: nowrap; }
    .iu-vin { font-family: var(--vz-font-monospace, SFMono-Regular, Menlo, Consolas, monospace); font-weight: 600; letter-spacing: .02em; }
    .iu-copy { border: 0; background: transparent; color: var(--iu-muted); padding: 0 .25rem; margin-left: .2rem; border-radius: .25rem; opacity: 0; transition: opacity .12s; }
    tr:hover .iu-copy { opacity: 1; }
    .iu-copy:hover { color: rgb(var(--vz-primary-rgb)); background: rgba(var(--vz-primary-rgb), .1); }
    .iu-sub-tag { display: inline-flex; align-items: center; gap: .35rem; font-size: .72rem; font-weight: 600; }

    /* ---------- Panel de detalle ---------- */
    #iuDetalle { width: min(880px, 100vw); }
    #iuDetalle .offcanvas-header { padding: 1.1rem 1.35rem; }
    #iuDetalle .eyebrow { font-size: .68rem; text-transform: uppercase; letter-spacing: .08em; color: var(--iu-muted); }
    .iu-dstats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .65rem; margin-bottom: 1rem; }
    .iu-dstat { padding: .7rem .85rem; border: 1px solid var(--vz-border-color); border-radius: .55rem; }
    .iu-dstat .l { font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: var(--iu-muted); }
    .iu-dstat .v { font-size: 1.35rem; font-weight: 600; font-variant-numeric: tabular-nums; line-height: 1.2; }
    .iu-dgrupo { margin-bottom: .75rem; }
    .iu-dgrupo .l { font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: var(--iu-muted); margin-bottom: .3rem; }
    .iu-dtools { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; margin: 1rem 0 .6rem; }
    .iu-dtools .iu-busca { max-width: none; }
    .iu-dtabla { border: 1px solid var(--vz-border-color); border-radius: .55rem; overflow: auto; max-height: calc(100vh - 380px); }
    .iu-dtabla table { font-size: .78rem; margin: 0; }
    .iu-dtabla thead th { position: sticky; top: 0; z-index: 1; background: var(--vz-light); font-size: .68rem; text-transform: uppercase; letter-spacing: .05em;
                          color: var(--iu-muted); font-weight: 600; white-space: nowrap; }
    .iu-dtabla td { white-space: nowrap; }

    /* ---------- Estados ---------- */
    .iu-vacio { padding: 3.5rem 1rem; text-align: center; color: var(--iu-muted); }
    .iu-vacio .ico { width: 56px; height: 56px; margin: 0 auto .75rem; border-radius: 50%; display: grid; place-items: center; font-size: 1.6rem;
                     background: var(--vz-light); }
    .iu-vacio h6 { color: var(--vz-body-color); margin-bottom: .25rem; }
    .iu-skel { display: block; border-radius: .35rem; background: linear-gradient(90deg, var(--vz-light) 25%, var(--vz-border-color) 37%, var(--vz-light) 63%);
               background-size: 400% 100%; animation: iuShimmer 1.4s ease infinite; }
    .iu-toast { position: fixed; left: 50%; bottom: 1.5rem; z-index: 1090; transform: translate(-50%, 1rem); opacity: 0; pointer-events: none;
                padding: .55rem 1rem; border-radius: .5rem; background: #212529; color: #fff; font-size: .82rem; box-shadow: 0 8px 24px rgba(0, 0, 0, .2);
                transition: opacity .2s, transform .2s; }
    .iu-toast.on { opacity: 1; transform: translate(-50%, 0); }
    .iu-oculto { position: absolute; left: -99999px; top: 0; }
    .iu-nota { display: flex; gap: .5rem; align-items: flex-start; margin-top: 1.25rem; padding-top: .9rem; border-top: 1px dashed var(--vz-border-color);
               font-size: .75rem; color: var(--iu-muted); }

    @keyframes iuFade { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }
    @keyframes iuShimmer { 0% { background-position: 100% 50%; } 100% { background-position: 0 50%; } }
    @keyframes iuSpin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) {
        .iu-wrap *, .iu-toast { animation: none !important; transition: none !important; }
    }
    @media (max-width: 1199.98px) {
        .iu-filtros { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
        .iu-filtros .iu-fgrupo.sel { grid-column: 1 / -1; }
    }
    @media (max-width: 767.98px) {
        .iu-filtros { grid-template-columns: minmax(0, 1fr); }
        .iu-selects { grid-template-columns: minmax(0, 1fr); }
        .iu-tab .txt { display: none; }
        .iu-resumen { margin-left: 0; width: 100%; }
        .iu-busca { max-width: none; }
        .iu-busca .kbd { display: none; }
        .iu-matriz .col-mod { min-width: 190px; }
        .iu-dstats { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .4rem; }
        .iu-bar, .iu-filtros, .iu-activos, .iu-body { padding-left: 1rem; padding-right: 1rem; }
    }
</style>
<div id="contentAjax"></div>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid iu-wrap">

            <!-- Encabezado -->
            <div class="iu-head">
                <div>
                    <div class="iu-crumb">Inventario · Producto terminado</div>
                    <h4><?= $data['page_title'] ?></h4>
                    <div class="iu-meta">
                        <span class="iu-live" id="iuEstado">Cargando existencia…</span>
                        <span id="iuGenerado"></span>
                    </div>
                </div>
                <div class="iu-actions">
                    <button type="button" class="btn btn-sm btn-light" id="btnRefrescar" title="Volver a consultar la existencia">
                        <i class="ri-refresh-line"></i> Actualizar
                    </button>
                    <button type="button" class="btn btn-sm btn-soft-success" id="btnExcel" title="Exporta la vista actual con los filtros aplicados">
                        <i class="ri-file-excel-2-line"></i> Excel
                    </button>
                    <button type="button" class="btn btn-sm btn-soft-danger" id="btnPDF" title="Reporte PDF de la matriz con los filtros aplicados">
                        <i class="ri-file-pdf-2-line"></i> Reporte PDF
                    </button>
                </div>
            </div>

            <!-- KPIs: siguen a los filtros -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="iu-kpi iu-acc-primary">
                        <div class="top">
                            <div>
                                <div class="lbl">Unidades en inventario</div>
                                <div class="val" id="kUnidades"><span class="iu-skel" style="width:90px;height:32px"></span></div>
                            </div>
                            <div class="iu-ico"><i class="ri-truck-line"></i></div>
                        </div>
                        <div class="iu-chips" id="kAnios" title="Unidades por año modelo"></div>
                        <div class="sub" id="kTransito"></div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="iu-kpi iu-acc-success">
                        <div class="top">
                            <div>
                                <div class="lbl">Modelos distintos</div>
                                <div class="val" id="kModelos"><span class="iu-skel" style="width:60px;height:32px"></span></div>
                            </div>
                            <div class="iu-ico"><i class="ri-stack-line"></i></div>
                        </div>
                        <div class="sub" id="kModelosSub"></div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="iu-kpi iu-acc-warning">
                        <div class="top">
                            <div>
                                <div class="lbl">Almacenes con unidades</div>
                                <div class="val" id="kAlmacenes"><span class="iu-skel" style="width:50px;height:32px"></span></div>
                            </div>
                            <div class="iu-ico"><i class="ri-building-2-line"></i></div>
                        </div>
                        <div class="sub" id="kAlmacenesSub"></div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="iu-kpi iu-acc-info">
                        <div class="top">
                            <div class="lbl">Composición por sublínea</div>
                            <div class="iu-ico" style="width:32px;height:32px;font-size:1rem"><i class="ri-pie-chart-2-line"></i></div>
                        </div>
                        <div class="iu-stack" id="kStack"><span class="iu-skel" style="width:100%"></span></div>
                        <div class="iu-leg" id="kLeyenda" title="Clic para filtrar por sublínea"></div>
                    </div>
                </div>
            </div>

            <div class="iu-card">
                <!-- Vista + búsqueda -->
                <div class="iu-bar">
                    <div class="iu-tabs" role="tablist" aria-label="Vista">
                        <button type="button" class="iu-tab" data-vista="unidad" role="tab" title="Matriz modelo × almacén">
                            <i class="ri-table-2"></i><span class="txt">Por unidad</span><span class="n" id="nVUnidad">–</span>
                        </button>
                        <button type="button" class="iu-tab" data-vista="almacen" role="tab" title="Una tarjeta por almacén">
                            <i class="ri-building-2-line"></i><span class="txt">Por almacén</span><span class="n" id="nVAlmacen">–</span>
                        </button>
                        <button type="button" class="iu-tab" data-vista="listado" role="tab" title="Un renglón por VIN">
                            <i class="ri-barcode-line"></i><span class="txt">Listado de VIN</span><span class="n" id="nVListado">–</span>
                        </button>
                    </div>
                    <div class="iu-busca" id="wrapBuscar">
                        <i class="ri-search-line"></i>
                        <input type="search" class="form-control form-control-sm" id="fBuscar" placeholder="Buscar modelo, clave o VIN" autocomplete="off">
                        <span class="kbd">/</span>
                        <button type="button" class="clr" id="btnBuscarClr" aria-label="Borrar búsqueda"><i class="ri-close-line"></i></button>
                    </div>
                    <div class="form-check form-switch mb-0" id="wrapPorColor" title="Un renglón por cada color del modelo">
                        <input class="form-check-input" type="checkbox" id="fPorColor">
                        <label class="form-check-label fs-13" for="fPorColor">Separar por color</label>
                    </div>
                    <div class="iu-resumen" id="iuResumen"></div>
                </div>

                <!-- Filtros -->
                <div class="iu-filtros">
                    <div class="iu-fgrupo">
                        <span class="lbl">Sublínea</span>
                        <div class="iu-fopts" id="fSublineas"><span class="iu-skel" style="width:70%;height:26px"></span></div>
                    </div>
                    <div class="iu-fgrupo">
                        <span class="lbl">Año modelo</span>
                        <div class="iu-fopts" id="fAnios"><span class="iu-skel" style="width:60%;height:26px"></span></div>
                    </div>
                    <div class="iu-fgrupo sel">
                        <span class="lbl">Ubicación y color</span>
                        <div class="iu-selects">
                            <div><label for="fPlanta">Planta</label><select class="form-select form-select-sm" id="fPlanta"><option value="">Todas</option></select></div>
                            <div><label for="fAlmacen">Almacén</label><select class="form-select form-select-sm" id="fAlmacen"><option value="">Todos</option></select></div>
                            <div><label for="fColor">Color</label><select class="form-select form-select-sm" id="fColor"><option value="">Todos</option></select></div>
                        </div>
                    </div>
                </div>

                <!-- Filtros activos -->
                <div class="iu-activos d-none" id="iuActivos"></div>

                <div class="iu-body">
                    <div id="panelUnidad" class="iu-panel">
                        <div class="iu-mbar" id="mBar">
                            <div class="d-flex gap-3">
                                <button type="button" class="btn btn-link" id="btnExpandir"><i class="ri-arrow-down-s-line align-bottom"></i> Expandir todo</button>
                                <button type="button" class="btn btn-link" id="btnContraer"><i class="ri-arrow-right-s-line align-bottom"></i> Contraer todo</button>
                            </div>
                            <div class="iu-heat" title="El fondo de cada celda indica la concentración de unidades">
                                Menos <span id="heatLeg"></span> Más
                            </div>
                        </div>
                        <div class="iu-scroll" id="mScroll">
                            <table class="iu-matriz" id="tMatriz">
                                <tbody>
                                    <tr><td><span class="iu-skel" style="height:14px;width:60%"></span></td></tr>
                                    <tr><td><span class="iu-skel" style="height:14px;width:85%"></span></td></tr>
                                    <tr><td><span class="iu-skel" style="height:14px;width:75%"></span></td></tr>
                                    <tr><td><span class="iu-skel" style="height:14px;width:90%"></span></td></tr>
                                    <tr><td><span class="iu-skel" style="height:14px;width:70%"></span></td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="iu-hint">
                            <span><i class="ri-cursor-line"></i>Clic en un número: VIN de ese modelo en ese almacén</span>
                            <span><i class="ri-car-line"></i>Clic en el modelo: todas sus unidades</span>
                            <span><i class="ri-folder-open-line"></i>Clic en la sublínea: contraer / expandir</span>
                        </div>
                    </div>
                    <div id="panelAlmacen" class="iu-panel d-none"></div>
                    <div id="panelListado" class="iu-panel d-none">
                        <table id="tListado" class="table table-sm table-hover align-middle mb-0" style="width:100%">
                            <thead>
                                <tr>
                                    <th>VIN</th>
                                    <th>Año</th>
                                    <th>Color</th>
                                    <th>Sublínea</th>
                                    <th>Modelo</th>
                                    <th>Clave</th>
                                    <th>Almacén</th>
                                    <th>No. motor</th>
                                    <th>VIN origen</th>
                                    <th>Referencia</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="iu-nota">
                        <i class="ri-information-line fs-14"></i>
                        <span>Existencia = unidades físicas (VIN) de productos activos. El año modelo se toma de la posición 10 del VIN; la sublínea es la asignada en Ingeniería.
                            Los indicadores y exportaciones respetan los filtros aplicados.</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Detalle de VIN (panel lateral) -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="iuDetalle" aria-labelledby="iuDetalleTit">
        <div class="offcanvas-header border-bottom align-items-start">
            <div class="me-3">
                <div class="eyebrow" id="iuDetalleEye">Detalle de unidades</div>
                <h5 class="offcanvas-title mb-1" id="iuDetalleTit">Unidades</h5>
                <div class="fs-12 text-muted" id="iuDetalleSub"></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
        </div>
        <div class="offcanvas-body">
            <div class="iu-dstats">
                <div class="iu-dstat"><div class="l">Unidades</div><div class="v" id="dUnidades">0</div></div>
                <div class="iu-dstat"><div class="l">Almacenes</div><div class="v" id="dAlmacenes">0</div></div>
                <div class="iu-dstat"><div class="l">Colores</div><div class="v" id="dColores">0</div></div>
            </div>
            <div class="iu-dgrupo"><div class="l">Por almacén</div><div class="iu-chips mt-0" id="dPorAlm"></div></div>
            <div class="iu-dgrupo"><div class="l">Por color</div><div class="iu-chips mt-0" id="dPorColor"></div></div>
            <div class="iu-dtools">
                <div class="iu-busca" style="flex:1 1 220px">
                    <i class="ri-search-line"></i>
                    <input type="search" class="form-control form-control-sm" id="dBuscar" placeholder="Filtrar VIN, motor o almacén" autocomplete="off">
                </div>
                <button type="button" class="btn btn-sm btn-light" id="btnDetalleCopiar"><i class="ri-file-copy-line align-bottom"></i> Copiar VINs</button>
                <button type="button" class="btn btn-sm btn-soft-success" id="btnDetalleCSV"><i class="ri-download-2-line align-bottom"></i> CSV</button>
            </div>
            <div class="iu-dtabla">
                <table class="table table-sm table-hover align-middle">
                    <thead>
                        <tr><th>#</th><th>VIN</th><th>Año</th><th>Color</th><th>Almacén</th><th>No. motor</th><th>VIN origen</th></tr>
                    </thead>
                    <tbody id="iuDetalleBody"></tbody>
                </table>
            </div>
            <div class="fs-12 text-muted mt-2" id="dPie"></div>
        </div>
    </div>
    <div class="iu-toast" id="iuToast" role="status" aria-live="polite"></div>
    <div class="iu-oculto" id="iuExport"></div>

    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <script>
                        document.write(new Date().getFullYear())
                    </script> © LDR.
                </div>
                <div class="col-sm-6">
                    <div class="text-sm-end d-none d-sm-block">LDR Solutions · MRP</div>
                </div>
            </div>
        </div>
    </footer>
</div>
<?php footerAdmin($data); ?>
