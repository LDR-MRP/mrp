<!-- FOOTER BASE -->
        <footer class="footer border-top bg-white">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        <script>document.write(new Date().getFullYear())</script> © LDR Solutions.
                    </div>
                    <div class="col-sm-6">
                        <div class="text-sm-end d-none d-sm-block">
                            Portal de Proveedores (SRM)
                        </div>
                    </div>
                </div>
            </div>
        </footer>

    </div> <!-- FIN de layout-wrapper -->

    <!-- Variables Globales -->
    <script>
        const base_url = "<?= base_url(); ?>";
    </script>

    <!-- Scripts Esenciales -->
    <script src="<?= media(); ?>/js/jquery-3.3.1.min.js"></script>
    <script src="<?= media(); ?>/minimal/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= media(); ?>/minimal/libs/sweetalert2/sweetalert2.min.js"></script>
    
    <!-- Motor de Comunicaciones e Interfaz -->
    <script src="<?= media(); ?>/js/sys_core.js"></script>

    <!-- Inicializador de componentes dinámicos de Velzon (Hamburger, dropdowns, etc) -->
    <script>
        $(document).on('click', '#topnav-hamburger-icon', function() {
            $('html').toggleClass('vertical-sidebar-enable');
            if ($('html').hasClass('vertical-sidebar-enable')) {
                $('.navbar-menu').addClass('show');
            } else {
                $('.navbar-menu').removeClass('show');
            }
        });
        $(document).on('click', '#vertical-overlay', function() {
            $('html').removeClass('vertical-sidebar-enable');
        });
    </script>

    <!-- Inyección dinámica de JS del módulo -->
    <?php if(!empty($data['page_functions_js'])): ?>
        <script src="<?= media(); ?>/js/<?= $data['page_functions_js']; ?>"></script>
    <?php endif; ?>

</body>
</html>