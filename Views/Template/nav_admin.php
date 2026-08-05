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
                                              <a href="<?= base_url(); ?>/plan_confproductosv1" class="nav-link" data-key="t-horizontal">Configuración de productos</a>
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

                                      <?php if (!empty($_SESSION['permisos'][11]['r'])) { ?>

                                          <li class="nav-item">
                                              <a href="#sidebarTickets" class="nav-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarTickets" data-key="t-supprt-tickets"> Reportes
                                              </a>
                                              <div class="collapse menu-dropdown" id="sidebarTickets">
                                                  <ul class="nav nav-sm flex-column">

                                                      <?php if (!empty($_SESSION['permisos'][11]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/rpt_mrp_planeacion" class="nav-link" data-key="t-list-view"> Indicadores OT </a>
                                                          </li>
                                                      <?php } ?>
                                                  </ul>
                                              </div>
                                          </li>

                                      <?php } ?>
                                  </ul>
                              </div>
                          </li> <!-- end plan maestro Menu -->
                      <?php } ?>

                      <?php if (
                            !empty($_SESSION['permisos'][16]['r']) || !empty($_SESSION['permisos'][17]['r']) || !empty($_SESSION['permisos'][18]['r']) || !empty($_SESSION['permisos'][19]['r']) || !empty($_SESSION['permisos'][20]['r']) || !empty($_SESSION['permisos'][21]['r'])
                            || !empty($_SESSION['permisos'][22]['r']) || !empty($_SESSION['permisos'][23]['r']) || !empty($_SESSION['permisos'][24]['r']) || !empty($_SESSION['permisos'][25]['r']) || !empty($_SESSION['permisos'][26]['r']) || !empty($_SESSION['permisos'][70]['r'])
                            || !empty($_SESSION['permisos'][71]['r']) || !empty($_SESSION['permisos'][72]['r']) || !empty($_SESSION['permisos'][73]['r']) || !empty($_SESSION['permisos'][67]['r']) || !empty($_SESSION['permisos'][68]['r']) || !empty($_SESSION['permisos'][69]['r'])
                        ) { ?>
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

                                      <?php if (!empty($_SESSION['permisos'][18]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_inventario" class="nav-link" data-key="t-detached">Alta de inventario</a>
                                          </li>
                                      <?php } ?>

                                      <?php if (!empty($_SESSION['permisos'][19]['r']) || !empty($_SESSION['permisos'][23]['r']) || !empty($_SESSION['permisos'][21]['r']) || !empty($_SESSION['permisos'][16]['r'])  || !empty($_SESSION['permisos'][70]['r'])  || !empty($_SESSION['permisos'][17]['r']) || !empty($_SESSION['permisos'][40]['r']) || !empty($_SESSION['permisos'][63]['r']) ) { ?>
                                          <li class="nav-item">
                                              <a href="javascript:void(0)" class="nav-link flex-grow-1" data-key="t-detached" data-bs-toggle="collapse" data-bs-target="#subCatalogos" aria-expanded="false" aria-controls="subCatalogos">
                                                  Catálogos
                                              </a>
                                              <div class="collapse ms-3" id="subCatalogos">
                                                  <ul class="nav nav-sm flex-column">
                                                      <?php if (!empty($_SESSION['permisos'][19]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/inv_almacenes" class="nav-link" data-key="t-detached">Almacenes</a>
                                                          </li>
                                                      <?php } ?>

                                                      <?php if (!empty($_SESSION['permisos'][23]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/inv_concepmovinventarios" class="nav-link" data-key="t-detached">Conceptos de movimientos</a>
                                                          </li>
                                                      <?php } ?>

                                                      <?php if (!empty($_SESSION['permisos'][63]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/inv_descuentos" class="nav-link" data-key="t-detached">Descuentos</a>
                                                          </li>
                                                      <?php } ?>

                                                      <?php if (!empty($_SESSION['permisos'][21]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/inv_esquemaimpuestos" class="nav-link" data-key="t-detached">Impuestos</a>
                                                          </li>
                                                      <?php } ?>

                                                      <?php if (!empty($_SESSION['permisos'][16]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/inv_lineasdproducto" class="nav-link" data-key="t-detached">Líneas de producto</a>
                                                          </li>
                                                      <?php } ?>

                                                      <?php if (!empty($_SESSION['permisos'][40]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/cli_marcas" class="nav-link" data-key="t-detached">Marcas</a>
                                                          </li>
                                                      <?php } ?>

                                                      <?php if (!empty($_SESSION['permisos'][70]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/inv_moneda" class="nav-link" data-key="t-detached">Moneda</a>
                                                          </li>
                                                      <?php } ?>

                                                      <?php if (!empty($_SESSION['permisos'][17]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/inv_precios" class="nav-link" data-key="t-detached">Precios</a>
                                                          </li>
                                                      <?php } ?>

                                                  </ul>
                                              </div>
                                          </li>
                                      <?php } ?>

                                      <?php if (!empty($_SESSION['permisos'][67]['r']) || !empty($_SESSION['permisos'][68]['r']) || !empty($_SESSION['permisos'][69]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="javascript:void(0)" class="nav-link flex-grow-1" data-key="t-detached" data-bs-toggle="collapse" data-bs-target="#subControlAlmacen" aria-expanded="false" aria-controls="subControlAlmacen">
                                                  Control almacén
                                              </a>
                                              <div class="collapse ms-3" id="subControlAlmacen">
                                                  <ul class="nav nav-sm flex-column">
                                                      <?php if (!empty($_SESSION['permisos'][67]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/inv_sedes" class="nav-link" data-key="t-detached">Sedes</a>
                                                          </li>
                                                      <?php } ?>

                                                      <?php if (!empty($_SESSION['permisos'][68]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/inv_zonas" class="nav-link" data-key="t-detached">Zonas</a>
                                                          </li>
                                                      <?php } ?>

                                                      <?php if (!empty($_SESSION['permisos'][69]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/inv_ubicaciones" class="nav-link" data-key="t-detached">Ubicaciones</a>
                                                          </li>
                                                      <?php } ?>

                                                  </ul>
                                              </div>
                                          </li>
                                      <?php } ?>

                                      <?php /* if (!empty($_SESSION['permisos'][71]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_asignacionesinventario" class="nav-link" data-key="t-detached">Asignaciones de inventario</a>
                                          </li>
                                      <?php } */ ?>

                                      <?php if (!empty($_SESSION['permisos'][24]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_kardex" class="nav-link" data-key="t-detached">Kardex</a>
                                          </li>
                                      <?php } ?>

                                      <?php /* if (!empty($_SESSION['permisos'][25]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_lotespedimentos" class="nav-link" data-key="t-detached">Lotes y pedimentos</a>
                                          </li>
                                      <?php } */ ?>

                                      <?php if (!empty($_SESSION['permisos'][22]['r']) || !empty($_SESSION['permisos'][73]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="javascript:void(0)" class="nav-link flex-grow-1" data-key="t-detached" data-bs-toggle="collapse" data-bs-target="#subMovimientos" aria-expanded="false" aria-controls="subMovimientos">
                                                  Movimientos
                                              </a>
                                              <div class="collapse ms-3" id="subMovimientos">
                                                  <ul class="nav nav-sm flex-column">
                                                      <?php if (!empty($_SESSION['permisos'][22]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/inv_movimientosinventario" class="nav-link" data-key="t-detached">Movimientos al inventario</a>
                                                          </li>
                                                      <?php } ?>
                                                      <?php if (!empty($_SESSION['permisos'][73]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/inv_movimientosalmacenes" class="nav-link" data-key="t-detached">Traspaso entre almacenes</a>
                                                          </li>
                                                      <?php } ?>
                                                  </ul>
                                              </div>
                                          </li>
                                      <?php } ?>

                                      <?php if (!empty($_SESSION['permisos'][26]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_multialmacenes" class="nav-link" data-key="t-detached">Multialmacenes</a>
                                          </li>
                                      <?php } ?>

                                      <?php if (!empty($_SESSION['permisos'][74]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_picking" class="nav-link" data-key="t-detached">Picking</a>
                                          </li>
                                      <?php } ?>

                                      <?php if (!empty($_SESSION['permisos'][20]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_productossustitutos" class="nav-link" data-key="t-detached">Productos sustitutos</a>
                                          </li>
                                      <?php } ?>

                                      <?php if (!empty($_SESSION['permisos'][64]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_recepcion" class="nav-link" data-key="t-detached">Recepción</a>
                                          </li>
                                      <?php } ?>

                                      <?php /* if (!empty($_SESSION['permisos'][72]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_reportes" class="nav-link" data-key="t-detached">Reportes</a>
                                          </li>
                                      <?php } */?>

                                      <?php if (!empty($_SESSION['permisos'][66]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="<?= base_url(); ?>/inv_tipo_cambio_moneda" class="nav-link" data-key="t-detached">Tipo de cambio</a>
                                          </li>
                                      <?php } ?>

                                      <?php if (!empty($_SESSION['permisos'][71]['r']) || !empty($_SESSION['permisos'][65]['r'])) { ?>
                                          <li class="nav-item">
                                              <a href="javascript:void(0)" class="nav-link flex-grow-1" data-key="t-detached" data-bs-toggle="collapse" data-bs-target="#subVIN" aria-expanded="false" aria-controls="subVIN">
                                                  VIN
                                              </a>
                                              <div class="collapse ms-3" id="subVIN">
                                                  <ul class="nav nav-sm flex-column">

                                                      <?php if (!empty($_SESSION['permisos'][65]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/inv_captura_vin" class="nav-link" data-key="t-detached">Captura glosario VIN</a>
                                                          </li>
                                                      <?php } ?>
                                                      <?php if (!empty($_SESSION['permisos'][71]['r'])) { ?>
                                                          <li class="nav-item">
                                                              <a href="<?= base_url(); ?>/inv_series" class="nav-link" data-key="t-detached">Generar VIN</a>
                                                          </li>
                                                      <?php } ?>
                                                  </ul>
                                              </div>
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

                      <!-- ==============================================================================
                        CATEGORÍA 1: COMPRAS (OPERATIVO)
                        ============================================================================== -->
                      <li class="nav-item" data-permiso="COM_COMPRAS|r">
                          <a class="nav-link menu-link" href="#sidebarCompras" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarCompras">
                              <i class="ri-shopping-cart-2-line icon-dual"></i> <span data-key="t-layouts">Compras</span>
                          </a>
                          <div class="collapse menu-dropdown" id="sidebarCompras">
                              <ul class="nav nav-sm flex-column">

                                  <!-- Requisiciones (Bandeja actual) -->
                                  <li class="nav-item" data-permiso="COM_REQUISICIONES|r">
                                      <a href="<?= base_url(); ?>/com_requisicion" class="nav-link" data-key="t-reqs">
                                          <i class="ri-file-list-3-line align-bottom me-1"></i> Requisiciones
                                          <span class="badge badge-pill bg-danger" data-key="t-hot">Hot</span>
                                      </a>
                                  </li>

                                  <!-- Cotizaciones -->
                                  <li class="nav-item" data-permiso="COM_NEGOCIACIONES|r">
                                      <a href="<?= base_url(); ?>/com_sourcing" class="nav-link" data-key="t-reqs">
                                          <i class="ri-file-list-3-line align-bottom me-1"></i> Negociaciones
                                      </a>
                                  </li>

                                  <!-- Órdenes de Compra -->
                                  <li class="nav-item" data-permiso="COM_ORDENES|r">
                                      <a href="<?= base_url(); ?>/com_orden" class="nav-link" data-key="t-ordenes">
                                          <i class="ri-shopping-bag-3-line align-bottom me-1"></i> Órdenes de Compra
                                      </a>
                                  </li>

                              </ul>
                          </div>
                      </li>

                      <!-- ==============================================================================
                        CATEGORÍA: LOGÍSTICA (TRASLADISTAS, MADRINAS, CHOFERES)
                        ============================================================================== -->
                      <li class="nav-item">
                          <a class="nav-link menu-link" href="#sidebarLogistica" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarLogistica">
                              <i class="ri-truck-line icon-dual"></i> <span data-key="t-logistica">Logística</span>
                          </a>
                          <div class="collapse menu-dropdown" id="sidebarLogistica">
                              <ul class="nav nav-sm flex-column">

                                  <!-- Madrinas -->
                                  <li class="nav-item">
                                      <a href="<?= base_url(); ?>/prv_madrinas" class="nav-link" data-key="t-madrinas">
                                          <i class="ri-truck-fill align-bottom me-1"></i> Madrinas
                                      </a>
                                  </li>

                                  <!-- Choferes -->
                                  <li class="nav-item">
                                      <a href="<?= base_url(); ?>/prv_choferes" class="nav-link" data-key="t-choferes">
                                          <i class="ri-steering-2-line align-bottom me-1"></i> Choferes
                                      </a>
                                  </li>

                                   <!-- Bandeja de Logística (Pool de VINs) -->
                                   <li class="nav-item">
                                       <a href="<?= base_url(); ?>/Lgs_bandeja" class="nav-link" data-key="t-lgs-bandeja">
                                           <i class="ri-inbox-line align-bottom me-1"></i> Pool VINs Liberados
                                       </a>
                                   </li>

                                   <!-- Mis Envíos -->
                                   <li class="nav-item">
                                       <a href="<?= base_url(); ?>/Lgs_envios" class="nav-link" data-key="t-lgs-envios">
                                           <i class="ri-route-line align-bottom me-1"></i> Mis Envíos
                                       </a>
                                   </li>

                                   <!-- Mis Planeaciones -->
                                   <li class="nav-item">
                                       <a href="<?= base_url(); ?>/Lgs_planeaciones" class="nav-link" data-key="t-lgs-planeaciones">
                                           <i class="ri-file-list-3-line align-bottom me-1"></i> Mis Planeaciones
                                       </a>
                                   </li>

                                   <!-- Panel de Aprobaciones -->
                                   <li class="nav-item">
                                       <a href="<?= base_url(); ?>/Lgs_aprobaciones" class="nav-link" data-key="t-lgs-aprobaciones">
                                           <i class="ri-checkbox-circle-line align-bottom me-1"></i> Aprobaciones
                                       </a>
                                   </li>

                                   <!-- Mesa de Despacho -->
                                   <li class="nav-item">
                                       <a href="<?= base_url(); ?>/Lgs_ejecucion" class="nav-link" data-key="t-lgs-ejecucion">
                                           <i class="ri-ship-line align-bottom me-1"></i> Mesa de Despacho
                                       </a>
                                   </li>

                                   <!-- Evidencias y Cierre -->
                                   <li class="nav-item">
                                       <a href="<?= base_url(); ?>/Lgs_evidencias" class="nav-link" data-key="t-lgs-evidencias">
                                           <i class="ri-camera-lens-line align-bottom me-1"></i> Evidencias y Cierre
                                       </a>
                                   </li>

                                   <!-- Monitoreo GPS y Rutas -->
                                   <li class="nav-item">
                                       <a href="<?= base_url(); ?>/Lgs_panelrutas" class="nav-link" data-key="t-lgs-panelrutas">
                                           <i class="ri-map-pin-user-line align-bottom me-1"></i> Monitoreo GPS
                                       </a>
                                   </li>

                              </ul>
                          </div>
                      </li>

                      <!-- ==============================================================================
                        CATEGORÍA 2: PROVEEDORES (CATÁLOGOS / ONBOARDING)
                        ============================================================================== -->
                      <li class="nav-item" data-permiso="PRV_PROVEEDORES|r">
                          <a class="nav-link menu-link" href="#sidebarProveedores" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarProveedores">
                              <i class="ri-building-line icon-dual"></i> <span>Proveedores</span>
                          </a>
                          <div class="collapse menu-dropdown" id="sidebarProveedores">
                              <ul class="nav nav-sm flex-column">

                                  <!-- Directorio de Proveedores (Acceso a la bandeja maestra con sus pestañas) -->
                                  <li class="nav-item" data-permiso="PRV_PROVEEDORES|r">
                                      <a href="<?= base_url(); ?>/prv_proveedor" class="nav-link">
                                          <i class="ri-truck-line align-bottom me-1"></i> Directorio de Proveedores
                                      </a>
                                  </li>

                                  <!-- Reporte Ejecutivo de Proveedores -->
                                  <li class="nav-item" data-permiso="PRV_PROVEEDORES|r">
                                      <a href="<?= base_url(); ?>/prv_proveedor/reporte" class="nav-link">
                                          <i class="ri-shield-check-line align-bottom me-1"></i> Reporte Análitico de Onboarding
                                      </a>
                                  </li>

                              </ul>
                          </div>
                      </li>

                      <!-- ==============================================================================
                        CATEGORÍA 3: CUENTAS POR PAGAR - CXP (FINANZAS)
                        ============================================================================== -->
                      <li class="nav-item" data-permiso="CXP_FACTURAS|r">
                          <a class="nav-link menu-link" href="#sidebarCxP" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarCxP">
                              <i class="ri-bank-card-2-line icon-dual"></i> <span>Cuentas por Pagar</span>
                          </a>
                          <div class="collapse menu-dropdown" id="sidebarCxP">
                              <ul class="nav nav-sm flex-column">

                                  <!-- Bandeja de Facturas (Conciliación) -->
                                  <li class="nav-item" data-permiso="CXP_FACTURAS|r">
                                      <a href="<?= base_url(); ?>/accountspayableinvoice/index" class="nav-link">
                                          <i class="ri-calculator-line align-bottom me-1"></i> Bandeja de Facturas
                                      </a>
                                  </li>

                                  <!-- Programación de Pagos (Dispersión #153) -->
                                  <li class="nav-item" data-permiso="CXP_PAGOS|r">
                                      <a href="<?= base_url(); ?>/accountspayablepayment/index" class="nav-link">
                                          <i class="ri-refund-2-line align-bottom me-1"></i> Programación de Pagos
                                      </a>
                                  </li>

                              </ul>
                          </div>
                      </li>

                      <?php if (!empty($_SESSION['permisos'][39]['r']) || !empty($_SESSION['permisos'][40]['r'])) { ?>
                          <li class="nav-item">
                              <a class="nav-link menu-link" href="#sidebarMateriales" data-bs-toggle="collapse"
                                  role="button" aria-expanded="false" aria-controls="sidebarMateriales">
                                  <i data-feather="layout" class="icon-dual"></i>
                                  <span>Clientes</span>
                              </a>
                              <div class="collapse menu-dropdown" id="sidebarMateriales">
                                  <ul class="nav nav-sm flex-column">

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