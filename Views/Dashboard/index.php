<?php headerAdmin($data); ?>

<style>
    .ldr-hero {
        position: relative;
        border: 0;
        border-radius: 10px;
        overflow: hidden;
        color: #fff;
        background: linear-gradient(120deg, #0b2545 0%, #13407a 60%, #1c5aa3 100%);
        box-shadow: 0 2px 12px rgba(11, 37, 69, .18);
    }

    .ldr-hero::after {
        content: "";
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 92% 8%, rgba(255, 255, 255, .10) 0, transparent 38%),
            radial-gradient(circle at 70% 120%, rgba(255, 255, 255, .06) 0, transparent 45%);
        pointer-events: none;
    }

    .ldr-hero .card-body {
        position: relative;
        z-index: 1;
        padding: 1.75rem 2rem;
    }

    .ldr-eyebrow {
        font-size: .72rem;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, .65);
        font-weight: 600;
    }

    .ldr-title {
        font-size: 1.65rem;
        font-weight: 600;
        margin: .35rem 0 .4rem;
        color: #fff;
    }

    .ldr-title .ldr-name {
        font-weight: 700;
    }

    .ldr-sub {
        color: rgba(255, 255, 255, .78);
        margin: 0;
        font-size: .95rem;
    }

    .ldr-clock {
        text-align: right;
        padding-left: 1.75rem;
        border-left: 1px solid rgba(255, 255, 255, .22);
    }

    .ldr-clock-time {
        font-size: 2.1rem;
        font-weight: 600;
        line-height: 1.1;
        font-variant-numeric: tabular-nums;
        color: #fff;
    }

    .ldr-clock-date {
        margin-top: .35rem;
        font-size: .9rem;
        color: rgba(255, 255, 255, .78);
    }

    .ldr-clock-tz {
        margin-top: .2rem;
        font-size: .72rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, .5);
    }

    @media (max-width: 767.98px) {
        .ldr-hero .card-body {
            padding: 1.25rem;
        }

        .ldr-title {
            font-size: 1.35rem;
        }

        .ldr-clock {
            text-align: left;
            border-left: 0;
            padding-left: 0;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, .22);
        }

        .ldr-clock-time {
            font-size: 1.6rem;
        }
    }
</style>

<div class="main-content">

    <div class="page-content">
        <div class="container-fluid">

            <!-- BANNER DE BIENVENIDA -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card ldr-hero">
                        <div class="card-body">
                            <div class="row align-items-center">

                                <div class="col-md-8">
                                    <div class="ldr-eyebrow">LDR Solutions · MRP</div>
                                    <h3 class="ldr-title">
                                        <span id="lbl-greeting">Hola</span>,
                                        <span class="ldr-name" id="lbl-user-name">...</span>
                                    </h3>
                                    <p class="ldr-sub">
                                        Panel de control y monitoreo global de operaciones.
                                    </p>
                                </div>

                                <div class="col-md-4">
                                    <div class="ldr-clock">
                                        <div class="ldr-clock-time" id="lbl-time">--:--:--</div>
                                        <div class="ldr-clock-date" id="lbl-date">--</div>
                                        <div class="ldr-clock-tz">Hora de Ciudad de México</div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- container-fluid -->
    </div>
    <!-- End Page-content -->

    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <script>
                        document.write(new Date().getFullYear())
                    </script> © LDR.
                </div>
                <div class="col-sm-6">
                    <div class="text-sm-end d-none d-sm-block">
                        LDR Solutions · MRP
                    </div>
                </div>
            </div>
        </div>
    </footer>
</div>
<!-- end main content-->

<script>
    (function() {
        const TZ = 'America/Mexico_City';

        const elDate = document.getElementById('lbl-date');
        const elTime = document.getElementById('lbl-time');
        const elGreeting = document.getElementById('lbl-greeting');

        const capitalize = s => s.charAt(0).toUpperCase() + s.slice(1);

        function getGreeting(hour) {
            if (hour >= 5 && hour < 12) return 'Buenos días';
            if (hour >= 12 && hour < 19) return 'Buenas tardes';
            return 'Buenas noches';
        }

        function tick() {
            const now = new Date();

            // "Viernes, 13 de noviembre de 2026"
            elDate.textContent = capitalize(
                now.toLocaleDateString('es-MX', {
                    timeZone: TZ,
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                })
            );

            // "14:52:07" (formato 24 h, típico de sistemas corporativos)
            elTime.textContent = now.toLocaleTimeString('es-MX', {
                timeZone: TZ,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });

            const hour = parseInt(
                new Intl.DateTimeFormat('es-MX', {
                    timeZone: TZ,
                    hour: 'numeric',
                    hour12: false
                }).format(now),
                10
            ) % 24;

            elGreeting.textContent = getGreeting(hour);
        }

        tick();
        setInterval(tick, 1000);
    })();
</script>

<?php footerAdmin($data); ?>