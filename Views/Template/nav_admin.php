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
                      <?php if (!empty($_SESSION['permisos'][1]['r'])) { ?>
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

                      <?php if (!empty($_SESSION['permisos'][2]['r'])) { ?>
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


                                      <!-- <li class="nav-item">
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
                                    </li> -->


                                  </ul>
                              </div>
                          </li>

                      <?php } ?>
                      <?php if (!empty($_SESSION['permisos'][6]['r']) || !empty($_SESSION['permisos'][7]['r']) || !empty($_SESSION['permisos'][8]['r']) || !empty($_SESSION['permisos'][9]['r']) || !empty($_SESSION['permisos'][10]['r'])) { ?>
                          <li class="nav-item">
                              <a class="nav-link menu-link" href="#sidebarPlaneacion" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarPlaneacion">
                                  <i data-feather="layout" class="icon-dual"></i> <span data-key="t-layouts">Planeación</span>
                              </a>
                              <div class="collapse menu-dropdown" id="sidebarPlaneacion">
                                  <ul class="nav nav-sm flex-column">
                                      <?php if (!empty($_SESSION['permisos'][6]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/plan_confproductos" class="nav-link" data-key="t-horizontal">Configuración de productos</a>
                                          </li>
                                      <?php } ?>
                                      <!-- <?php if (!empty($_SESSION['permisos'][7]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/pbom" class="nav-link" data-key="t-horizontal">BOM - Control</a>
                                          </li>
                                      <?php } ?>
                                      <?php if (!empty($_SESSION['permisos'][8]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/pla_productos" class="nav-link" data-key="t-detached">Productos Terminados - PT</a>
                                          </li>
                                      <?php } ?> -->
                                      <?php if (!empty($_SESSION['permisos'][9]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/plan_planeacion" class="nav-link" data-key="t-two-column">Plan de producción</a>

                                          </li>
                                      <?php } ?>
                                      <!-- <?php if (!empty($_SESSION['permisos'][10]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/pordenes" class="nav-link" data-key="t-hovered">Ordenes</a>
                                          </li>
                                      <?php } ?> -->


                                         <!-- <li class="nav-item">
                                        <a href="#sidebarTickets" class="nav-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarTickets" data-key="t-supprt-tickets"> Reportes
                                        </a>
                                        <div class="collapse menu-dropdown" id="sidebarTickets">
                                            <ul class="nav nav-sm flex-column">
                                                <li class="nav-item">
                                                    <a href="<?= base_url(); ?>/rpt_mrp_planeacion" class="nav-link" data-key="t-list-view"> Planeación </a>
                                                </li>
                                     
                                            </ul>
                                        </div>
                                    </li> -->
                                  </ul>
                              </div>
                          </li> <!-- end plan maestro Menu -->
                      <?php } ?>

                      <?php if (!empty($_SESSION['permisos'][16]['r']) || !empty($_SESSION['permisos'][17]['r']) || !empty($_SESSION['permisos'][18]['r']) || !empty($_SESSION['permisos'][19]['r']) || !empty($_SESSION['permisos'][20]['r']) || !empty($_SESSION['permisos'][21]['r']) || !empty($_SESSION['permisos'][22]['r']) || !empty($_SESSION['permisos'][23]['r']) || !empty($_SESSION['permisos'][24]['r']) || !empty($_SESSION['permisos'][25]['r']) || !empty($_SESSION['permisos'][26]['r']) || !empty($_SESSION['permisos'][70]['r'])) { ?>
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


                                      <?php if (!empty($_SESSION['permisos'][19]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_almacenes" class="nav-link" data-key="t-detached">Almacenes</a>
                                          </li>
                                      <?php } ?>

                                      <?php if (!empty($_SESSION['permisos'][18]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_inventario" class="nav-link" data-key="t-detached">Alta de inventario</a>
                                          </li>
                                      <?php } ?>

                                      <?php /* if (!empty($_SESSION['permisos'][71]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_asignacionesinventario" class="nav-link" data-key="t-detached">Asignaciones de inventario</a>
                                          </li>
                                      <?php } */?>

                                      <?php if (!empty($_SESSION['permisos'][23]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_concepmovinventarios" class="nav-link" data-key="t-detached">Conceptos de movimientos</a>
                                          </li>
                                      <?php } ?>

                                      <?php if (!empty($_SESSION['permisos'][21]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_esquemaimpuestos" class="nav-link" data-key="t-detached">Impuestos</a>
                                          </li>
                                      <?php } ?>

                                      <?php if (!empty($_SESSION['permisos'][24]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_kardex" class="nav-link" data-key="t-detached">Kardex</a>
                                          </li>
                                      <?php } ?>

                                      <?php if (!empty($_SESSION['permisos'][16]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_lineasdproducto" class="nav-link" data-key="t-detached">Líneas de producto</a>
                                          </li>
                                      <?php } ?>

                                      <?php /* if (!empty($_SESSION['permisos'][25]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_lotespedimentos" class="nav-link" data-key="t-detached">Lotes y pedimentos</a>
                                          </li>
                                      <?php } */?>

                                      <?php if (!empty($_SESSION['permisos'][70]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_moneda" class="nav-link" data-key="t-detached">Moneda</a>
                                          </li>
                                      <?php } ?>

                                      <?php if (!empty($_SESSION['permisos'][22]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_movimientosinventario" class="nav-link" data-key="t-detached">Movimientos</a>
                                          </li>
                                      <?php } ?>

                                      <?php if (!empty($_SESSION['permisos'][26]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_multialmacenes" class="nav-link" data-key="t-detached">Multialmacenes</a>
                                          </li>
                                      <?php } ?>

                                      <?php if (!empty($_SESSION['permisos'][17]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_precios" class="nav-link" data-key="t-detached">Precios</a>
                                          </li>
                                      <?php } ?>

                                      <?php if (!empty($_SESSION['permisos'][71]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_series" class="nav-link" data-key="t-detached">Series</a>
                                          </li>
                                      <?php } ?>

                                  </ul>
                              </div>
                          </li> <!-- end plan maestro Menu -->
                      <?php } ?>

                      <?php if (!empty($_SESSION['permisos'][27]['r']) || !empty($_SESSION['permisos'][28]['r']) || !empty($_SESSION['permisos'][29]['r'])) { ?>
                          <li class="nav-item">
                              <a class="nav-link menu-link" href="#sidebarCapacidad" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarCapacidad">
                                  <i data-feather="layout" class="icon-dual"></i> <span data-key="t-layouts">Capacidad</span>
                              </a>
                              <div class="collapse menu-dropdown" id="sidebarCapacidad">
                                  <ul class="nav nav-sm flex-column">


                                      <?php if (!empty($_SESSION['permisos'][27]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/cap_estaciones" class="nav-link" data-key="t-detached">Estaciones</a>
                                          </li>
                                      <?php } ?>



                                      <?php if (!empty($_SESSION['permisos'][28]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/cap_lineasdtrabajo" class="nav-link" data-key="t-detached">Líneas de producción</a>
                                          </li>
                                      <?php } ?>

                                      <?php if (!empty($_SESSION['permisos'][29]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/cap_plantas" class="nav-link" data-key="t-detached">Plantas</a>
                                          </li>
                                      <?php } ?>
                                  </ul>
                              </div>
                          </li>
                      <?php } ?>

                      <?php if (hasPermissions(COM_COMPRAS, 'r') || hasPermissions(COM_REQUISICIONES, 'r') || hasPermissions(PRV_PROVEEDORES, 'r')): ?>
                          <li class="nav-item">
                              <a class="nav-link menu-link" href="#" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarProveedores">
                                  <i class="ri-shopping-cart-2-line icon-dual"></i> <span data-key="t-layouts">Compras</span>
                              </a>
                              <div class="collapse menu-dropdown" id="sidebarProveedores">
                                  <ul class="nav nav-sm flex-column">
                                    <?php if (hasPermissions(COM_REQUISICIONES, 'r')): ?>
                                      <li class="nav-item">
                                          <a href="<?= base_url(); ?>/com_requisicion" class="nav-link" data-key="t-reqs">
                                              <i class="ri-file-list-3-line align-bottom me-1"></i> Requisiciones
                                              <span class="badge badge-pill bg-danger" data-key="t-hot">Hot</span>
                                          </a>
                                      </li>
                                    <?php endif; ?>
                                    <?php if (hasPermissions(COM_COMPRAS, 'r')): ?>
                                      <li class="nav-item">
                                          <a href="<?= base_url(); ?>/com_compra" class="nav-link" data-key="t-ordenes">
                                              <i class="ri-shopping-bag-3-line align-bottom me-1"></i> Órdenes de Compra
                                          </a>
                                      </li>
                                    <?php endif; ?>
                                    <?php if (hasPermissions(PRV_PROVEEDORES, 'r')): ?>
                                      <li class="nav-item my-2">
                                          <hr class="text-muted opacity-25 my-1" style="margin-left: 20px; margin-right: 20px;">
                                          <span class="d-block text-muted fs-10 fw-bold text-uppercase mt-2" style="padding-left: 35px; letter-spacing: 0.8px;">
                                              Catálogos
                                          </span>
                                      </li>
                                      <li class="nav-item">
                                          <a href="<?= base_url(); ?>/prv_proveedor" class="nav-link" data-key="t-proveedores">
                                              <i class="ri-truck-line align-bottom me-1"></i> Proveedores
                                          </a>
                                      </li>
                                    <?php endif; ?>
                                  </ul>
                              </div>
                          </li>
                      <?php endif; ?>

                      <?php if (!empty($_SESSION['permisos'][39]['r']) || !empty($_SESSION['permisos'][40]['r'])) { ?>
                          <li class="nav-item">
                              <a class="nav-link menu-link" href="#sidebarMateriales" data-bs-toggle="collapse"
                                  role="button" aria-expanded="false" aria-controls="sidebarMateriales">
                                  <i data-feather="layout" class="icon-dual"></i>
                                  <span>Clientes</span>
                              </a>
                              <div class="collapse menu-dropdown" id="sidebarMateriales">
                                  <ul class="nav nav-sm flex-column">
                                      <?php if (!empty($_SESSION['permisos'][40]['r'])) { ?>
                                          <li class="nav-itemQ">
                                              <a href="<?= base_url(); ?>/cli_marcas" class="nav-link">Marcas</a>
                                          </li>
                                      <?php } ?>

                                      <?php if (!empty($_SESSION['permisos'][39]['r'])) { ?>
                                          <li class="nav-item">
                                              <div class="d-flex align-items-center justify-content-between">
                                                  <a href="<?= base_url(); ?>/cli_clientes"
                                                      class="nav-link flex-grow-1">
                                                      Clientes
                                                  </a>
                                                  <a href="javascript:void(0)"
                                                      class="nav-link px-2"
                                                      data-bs-toggle="collapse"
                                                      data-bs-target="#subClientes"
                                                      aria-expanded="false"
                                                      aria-controls="subClientes">
                                                  </a>
                                              </div>
                                              <div class="collapse ms-3" id="subClientes">
                                                  <ul class="nav nav-sm flex-column">
                                                      <?php if (!empty($_SESSION['permisos'][46]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/cli_tipos_clientes" class="nav-link">Tipo de cliente</a>
                                                          </li>
                                                      <?php } ?>
                                                      <?php if (!empty($_SESSION['permisos'][42]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/cli_grupos" class="nav-link">Grupos</a>
                                                          </li>
                                                      <?php } ?>
                                                      <?php if (!empty($_SESSION['permisos'][45]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/cli_regionales" class="nav-link">Regionales</a>
                                                          </li>
                                                      <?php } ?>
                                                  </ul>
                                              </div>
                                          </li>
                                      <?php } ?>

                                      <?php if (!empty($_SESSION['permisos'][44]['r'])) { ?>
                                          <li class="nav-item">
                                              <div class="d-flex align-items-center justify-content-between">
                                                  <a href="<?= base_url(); ?>/cli_contactos"
                                                      class="nav-link flex-grow-1">
                                                      Contactos
                                                  </a>
                                                  <a href="javascript:void(0)"
                                                      class="nav-link px-2"
                                                      data-bs-toggle="collapse"
                                                      data-bs-target="#subContactos"
                                                      aria-expanded="false"
                                                      aria-controls="subContactos">
                                                  </a>
                                              </div>
                                              <div class="collapse ms-3" id="subContactos">
                                                  <ul class="nav nav-sm flex-column">
                                                      <?php if (!empty($_SESSION['permisos'][43]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/cli_puestos" class="nav-link">
                                                                  Puestos
                                                              </a>
                                                          </li>
                                                      <?php } ?>
                                                      <?php if (!empty($_SESSION['permisos'][41]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/cli_departamentos" class="nav-link">
                                                                  Departamentos
                                                              </a>
                                                          </li>
                                                      <?php } ?>
                                                  </ul>
                                              </div>
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
                            </a>http://localhost/mrp/dashboard
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