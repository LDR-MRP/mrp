      <div class="app-menu navbar-menu">
            <!-- LOGO -->
            <div class="navbar-brand-box">
                <!-- Dark Logo-->
                <a href="index.html" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="<?= media(); ?>/minimal/images/logo-sm.png" alt="" height="22">
                    </span>
                    <span class="logo-lg">
                        <img src="<?= media(); ?>/minimal/images/logo-dark.png" alt="" height="17">
                    </span>
                </a>
                <!-- Light Logo-->
                <a href="index.html" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="<?= media(); ?>/minimal/images/logo-sm.png" alt="" height="22">
                    </span>
                    <span class="logo-lg">
                        <img src="<?= media(); ?>/minimal/images/logo-light.png" alt="" height="17">
                    </span>
                </a>
                <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
                    <i class="ri-record-circle-line"></i>
                </button>
            </div>

            <div id="scrollbar">
                <div class="container-fluid">

                    <div id="two-column-menu">
                    </div>
                    <ul class="navbar-nav" id="navbar-nav">
                        <li class="menu-title"><span data-key="t-menu">Menu</span></li>
                                <?php if(!empty($_SESSION['permisos'][1]['r'])){ ?>
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="#sidebarDashboards" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarDashboards">
                                <i data-feather="home" class="icon-dual"></i> <span data-key="t-dashboards">Dashboard</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarDashboards">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a href="#" class="nav-link" data-key="t-analytics"> Analytics </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link" data-key="t-crm"> CRM </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link" data-key="t-ecommerce"> Ecommerce </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link" data-key="t-crypto"> Crypto </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link" data-key="t-projects"> Projects </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link" data-key="t-nft"> NFT</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link" data-key="t-job">Job</a>
                                    </li>
                                </ul>
                            </div>
                        </li> <!-- end Dashboard Menu -->
                        <?php } ?>

                           <?php if(!empty($_SESSION['permisos'][2]['r'])){ ?>
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="#sidebarApps" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarApps">
                                <i data-feather="grid" class="icon-dual"></i> <span data-key="t-apps">Agentes</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarApps">
                                <ul class="nav nav-sm flex-column">

                                                                        <li class="nav-item">
                                        <a href="<?= base_url(); ?>/usuarios" class="nav-link" data-key="t-calendar"> Usuarios </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?= base_url(); ?>/roles" class="nav-link" data-key="t-chat"> Roles </a>
                                    </li>


                                    <li class="nav-item">
                                        <a href="#sidebarTickets" class="nav-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarTickets" data-key="t-supprt-tickets"> Tickets
                                        </a>
                                        <div class="collapse menu-dropdown" id="sidebarTickets">
                                            <ul class="nav nav-sm flex-column">
                                                <li class="nav-item">
                                                    <a href="#" class="nav-link" data-key="t-list-view"> Listado</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a href="#" class="nav-link" data-key="t-ticket-details"> Tickets de soporte </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </li>


                                </ul>
                            </div>
                        </li>

                           <?php } ?>
        <?php if(!empty($_SESSION['permisos'][3]['r']) || !empty($_SESSION['permisos'][4]['r']) || !empty($_SESSION['permisos'][5]['r']) || !empty($_SESSION['permisos'][6]['r'])){ ?>
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="#sidebarPlaneacion" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarPlaneacion">
                                <i data-feather="layout" class="icon-dual"></i> <span data-key="t-layouts">Planeación</span> 
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarPlaneacion">
                                <ul class="nav nav-sm flex-column">
                                    <?php if(!empty($_SESSION['permisos'][3]['r'])){ ?>
                                    <li class="nav-item">
                                        <a href="<?= base_url(); ?>/pbom"  class="nav-link" data-key="t-horizontal">BOM</a>
                                    </li>
                                     <?php } ?>
                                      <?php if(!empty($_SESSION['permisos'][4]['r'])){ ?>
                                    <li class="nav-item">
                                        <a href="<?= base_url(); ?>/pla_productos"  class="nav-link" data-key="t-detached">Productos</a>
                                    </li>
                                       <?php } ?>
                                           <?php if(!empty($_SESSION['permisos'][5]['r'])){ ?>
                                    <li class="nav-item">
                                        <a href="<?= base_url(); ?>/pdemanda"  class="nav-link" data-key="t-two-column">Demanda</a>
                                    </li>
                                      <?php } ?>
                                        <?php if(!empty($_SESSION['permisos'][6]['r'])){ ?>
                                    <li class="nav-item">
                                        <a href="<?= base_url(); ?>/pordenes"  class="nav-link" data-key="t-hovered">Ordenes de producción</a>
                                    </li>
                                       <?php } ?>
                                </ul>
                            </div>
                        </li> <!-- end plan maestro Menu -->
                         <?php } ?>

                                <?php if(!empty($_SESSION['permisos'][7]['r']) || !empty($_SESSION['permisos'][8]['r'])){ ?>
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="#sidebarRequerimientos" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarRequerimientos">
                                <i data-feather="layout" class="icon-dual"></i> <span data-key="t-layouts">Inventario</span> 
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarRequerimientos">
                                <ul class="nav nav-sm flex-column">
                                    <?php 
                                    /*
                                    if(!empty($_SESSION['permisos'][7]['r'])){ ?>
                                    <li class="nav-item">
                                        <a href="<?= base_url(); ?>/rforecast"  class="nav-link" data-key="t-horizontal">Forecast</a>
                                    </li>
                                       <?php } 
                                       */
                                       ?>

                                    <?php if (!empty($_SESSION['permisos'][21]['r'])) { ?>
                                        <li class="nav-item">
                                            <a href="<?= base_url(); ?>/inv_lineasdproducto" class="nav-link" data-key="t-detached">Líneas de producto</a>
                                        </li>
                                    <?php } ?>
                                    
                                    <?php if (!empty($_SESSION['permisos'][22]['r'])) { ?>
                                        <li class="nav-item">
                                            <a href="<?= base_url(); ?>/inv_precios" class="nav-link" data-key="t-detached">Precios</a>
                                        </li>
                                    <?php } ?>
                                    
                                    <?php if (!empty($_SESSION['permisos'][23]['r'])) { ?>
                                        <li class="nav-item">
                                            <a href="<?= base_url(); ?>/inv_inventario" class="nav-link" data-key="t-detached">Inventario</a>
                                        </li>
                                    <?php } ?>
                                        
                                        <?php if (!empty($_SESSION['permisos'][24]['r'])) { ?>
                                            <li class="nav-item">
                                                <a href="<?= base_url(); ?>/inv_almacenes" class="nav-link" data-key="t-detached">Almacenes</a>
                                            </li>
                                        <?php } ?>
                                        
                                        <?php if (!empty($_SESSION['permisos'][25]['r'])) { ?>
                                            <li class="nav-item">
                                                <a href="<?= base_url(); ?>/inv_productossustitutos" class="nav-link" data-key="t-detached">Productos sustitutos</a>
                                            </li>
                                        <?php } ?>

                                        <?php if(!empty($_SESSION['permisos'][8]['r'])){ ?>
                                    <li class="nav-item">
                                        <a href="<?= base_url(); ?>/rpsemanal"  class="nav-link" data-key="t-detached">Programación Semanal</a>
                                    </li>
                                       <?php } ?>
                                </ul>
                            </div>
                        </li> <!-- end plan maestro Menu -->
                             <?php } ?>
 <?php if(!empty($_SESSION['permisos'][9]['r']) || !empty($_SESSION['permisos'][10]['r']) || !empty($_SESSION['permisos'][11]['r'])){ ?>
                            <li class="nav-item">
                            <a class="nav-link menu-link" href="#sidebarOrdenes" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarOrdenes">
                                <i data-feather="layout" class="icon-dual"></i> <span data-key="t-layouts">Órdenes</span> 
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarOrdenes">
                                <ul class="nav nav-sm flex-column">
                                     <?php if(!empty($_SESSION['permisos'][9]['r'])){ ?>
                                    <li class="nav-item">
                                        <a href="#"  class="nav-link" data-key="t-horizontal">BOM</a>
                                    </li>
                                     <?php } ?>
                                          <?php if(!empty($_SESSION['permisos'][10]['r'])){ ?>
                                    <li class="nav-item">
                                        <a href="#"  class="nav-link" data-key="t-detached">Lead times</a>
                                    </li>
                                       <?php } ?>
                                       <?php if(!empty($_SESSION['permisos'][11]['r'])){ ?>
                                        <li class="nav-item">
                                        <a href="#"  class="nav-link" data-key="t-detached">MRP run</a>
                                    </li>
                                       <?php } ?>
                                </ul>
                            </div>
                        </li> <!-- end control de ordenes Menu -->
                         <?php } ?>
 <?php if(!empty($_SESSION['permisos'][12]['r']) || !empty($_SESSION['permisos'][13]['r']) || !empty($_SESSION['permisos'][14]['r'])){ ?>
                           <li class="nav-item">
                            <a class="nav-link menu-link" href="#sidebarMateriales" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarMateriales">
                                <i data-feather="layout" class="icon-dual"></i> <span data-key="t-layouts">Materiales</span> 
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarMateriales">
                                <ul class="nav nav-sm flex-column">
                                      <?php if(!empty($_SESSION['permisos'][12]['r'])){ ?>
                                    <li class="nav-item">
                                        <a href="#"  class="nav-link" data-key="t-horizontal">Liberación</a>
                                    </li>
                                      <?php } ?>
                                        <?php if(!empty($_SESSION['permisos'][13]['r'])){ ?>
                                    <li class="nav-item">
                                        <a href="#"  class="nav-link" data-key="t-detached">Seguimiento</a>
                                    </li>
                                        <?php } ?>
                                                <?php if(!empty($_SESSION['permisos'][14]['r'])){ ?>
                                        <li class="nav-item">
                                        <a href="#"  class="nav-link" data-key="t-detached">Cierre</a>
                                    </li>
                                       <?php } ?>
                                </ul>
                            </div>
                        </li> 
      <?php } ?>


                         <?php if(!empty($_SESSION['permisos'][15]['r']) || !empty($_SESSION['permisos'][16]['r']) || !empty($_SESSION['permisos'][17]['r'])){ ?>
                           <li class="nav-item">
                            <a class="nav-link menu-link" href="#sidebarCapacidad" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarCapacidad">
                                <i data-feather="layout" class="icon-dual"></i> <span data-key="t-layouts">Capacidad</span> 
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarCapacidad">
                                <ul class="nav nav-sm flex-column">
                                     <?php if(!empty($_SESSION['permisos'][15]['r'])){ ?>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link" data-key="t-horizontal">Calendario de producción</a>
                                    </li> 
                                     <?php } ?>
                                       <?php if(!empty($_SESSION['permisos'][16]['r'])){ ?>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link" data-key="t-detached">Requerimientos</a>
                                    </li>
                                     <?php } ?>
                                        <?php if(!empty($_SESSION['permisos'][17]['r'])){ ?>
                                        <li class="nav-item">
                                        <a href="#" class="nav-link" data-key="t-detached">Transferencias</a>
                                    </li>
                                     <?php } ?>

                                                                              <?php if(!empty($_SESSION['permisos'][19]['r'])){ ?>
                                        <li class="nav-item">
                                        <a href="<?= base_url(); ?>/cap_estaciones"  class="nav-link" data-key="t-detached">Estaciones</a>
                                    </li>
                                     <?php } ?>



                                        <?php if(!empty($_SESSION['permisos'][18]['r'])){ ?>
                                        <li class="nav-item">
                                        <a href="<?= base_url(); ?>/cap_lineasdtrabajo"  class="nav-link" data-key="t-detached">Líneas de trabajo</a>
                                    </li>
                                     <?php } ?>

                                        <?php if(!empty($_SESSION['permisos'][20]['r'])){ ?>
                                        <li class="nav-item">
                                        <a href="<?= base_url(); ?>/cap_plantas"  class="nav-link" data-key="t-detached">Plantas</a>
                                    </li>
                                     <?php } ?>
                                </ul>
                            </div>
                        </li> 
                        <?php } ?>

             

                  
<!-- 
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="#sidebarConfiguracion" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarConfiguracion">
                                <i data-feather="command" class="icon-dual"></i> <span data-key="t-pages">Configuración</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarConfiguracion">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a href="pages-starter.html" class="nav-link" data-key="t-starter"> MRP </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="pages-team.html" class="nav-link" data-key="t-team"> Accesos </a>
                                    </li>

                                </ul>
                            </div>
                        </li> -->


                        <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-components">Components</span></li>

                  
             

                        <li class="nav-item">
                            <a class="nav-link menu-link" href="<?= base_url(); ?>/usuarios/perfil">
                                <i data-feather="copy" class="icon-dual"></i> <span data-key="t-widgets">Mi perfil</span>
                            </a>
                        </li>




                      

                    </ul>
                </div>
                <!-- Sidebar -->
            </div>

            <div class="sidebar-background"></div>
        </div>