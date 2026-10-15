import re

with open('/home/christianguarneros/proyectos/mrp/Views/Template/nav_admin.php', 'r') as f:
    content = f.read()

logistica_start_marker = '<a class="nav-link menu-link" href="#sidebarLogistica" data-bs-toggle="collapse" role="button"'

parent_check = """<?php if (!empty($_SESSION['permisos'][LGS_BANDEJA]['r']) || !empty($_SESSION['permisos'][LGS_COSTOS]['r']) || !empty($_SESSION['permisos'][LGS_MADRINAS]['r']) || !empty($_SESSION['permisos'][LGS_CHOFERES]['r']) || !empty($_SESSION['permisos'][LGS_PLATAFORMAS]['r']) || !empty($_SESSION['permisos'][LGS_ENVIOS]['r']) || !empty($_SESSION['permisos'][LGS_PLANEACIONES]['r']) || !empty($_SESSION['permisos'][LGS_APROBACIONES]['r']) || !empty($_SESSION['permisos'][LGS_EJECUCION]['r']) || !empty($_SESSION['permisos'][LGS_EVIDENCIAS]['r']) || !empty($_SESSION['permisos'][LGS_PANELRUTAS]['r']) || !empty($_SESSION['permisos'][LGS_INCIDENCIAS]['r']) || !empty($_SESSION['permisos'][LGS_GASTOSADICIONALES]['r'])) { ?>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarLogistica" data-bs-toggle="collapse" role="button" """

content = content.replace('<li class="nav-item">\n                    <a class="nav-link menu-link" href="#sidebarLogistica" data-bs-toggle="collapse" role="button"', parent_check)

def wrap_item(comment_text, tag, text, var):
    pattern = rf'({comment_text}\s*<li class="nav-item">.*?</li>)'
    replacement = f"<?php if (!empty($_SESSION['permisos'][{var}]['r'])) {{ ?>\n                            \\1\n                            <?php }} ?>"
    return re.sub(pattern, replacement, text, flags=re.DOTALL)

content = wrap_item(r'<!-- Bandeja de Logística -->', 'Lgs_bandeja', content, 'LGS_BANDEJA')
content = wrap_item(r'<!-- Tarifas y Costos -->', 'Lgs_costos', content, 'LGS_COSTOS')

# Catálogos parent
catalogos_check = """<?php if (!empty($_SESSION['permisos'][LGS_MADRINAS]['r']) || !empty($_SESSION['permisos'][LGS_CHOFERES]['r']) || !empty($_SESSION['permisos'][LGS_PLATAFORMAS]['r'])) { ?>
                            <!-- Catálogos \(Madrinas, Choferes, Plataformas\) -->
                            <li class="nav-item">
                                <a href="#sidebarLogisticaCatalogos" class="nav-link" data-bs-toggle="collapse" role="button" """
content = re.sub(r'<!-- Catálogos \(Madrinas, Choferes, Plataformas\) -->\s*<li class="nav-item">\s*<a href="#sidebarLogisticaCatalogos" class="nav-link" data-bs-toggle="collapse" role="button"', catalogos_check, content, flags=re.DOTALL)

content = wrap_item(r'<!-- Madrinas -->', 'prv_madrinas', content, 'LGS_MADRINAS')
content = wrap_item(r'<!-- Choferes -->', 'prv_choferes', content, 'LGS_CHOFERES')
content = wrap_item(r'<!-- Plataformas -->', 'prv_plataformas', content, 'LGS_PLATAFORMAS')

# Close Catálogos parent (find the </li> after the </ul></div> for catalogos)
# Let's do it using string replacement:
catalogos_end = """                                    </ul>
                                </div>
                            </li>
                            <?php } ?>"""
content = content.replace('                                    </ul>\n                                </div>\n                            </li>', catalogos_end, 1)

content = wrap_item(r'<!-- Mis Envíos -->', 'Lgs_envios', content, 'LGS_ENVIOS')
content = wrap_item(r'<!-- Mis Planeaciones -->', 'Lgs_planeaciones', content, 'LGS_PLANEACIONES')
content = wrap_item(r'<!-- Aprobaciones -->', 'Lgs_aprobaciones', content, 'LGS_APROBACIONES')
content = wrap_item(r'<!-- Mesa de Despacho -->', 'Lgs_ejecucion', content, 'LGS_EJECUCION')
content = wrap_item(r'<!-- Evidencias y Cierre -->', 'Lgs_evidencias', content, 'LGS_EVIDENCIAS')
content = wrap_item(r'<!-- Monitoreo GPS -->', 'Lgs_panelrutas', content, 'LGS_PANELRUTAS')
content = wrap_item(r'<!-- Incidencias Operativas -->', 'Lgs_incidencias', content, 'LGS_INCIDENCIAS')
content = wrap_item(r'<!-- Gastos Adicionales -->', 'Lgs_gastosadicionales', content, 'LGS_GASTOSADICIONALES')

# wrap 'Portal Trasladista (Móvil)' with LGS_EJECUCION
content = wrap_item(r'<!-- Portal Trasladista \(Móvil\) -->', 'chofer_movil', content, 'LGS_EJECUCION')
# wrap 'Entrega en Destino (QR)' with LGS_EJECUCION
content = wrap_item(r'<!-- Entrega en Destino \(QR\) -->', 'entrega_destino', content, 'LGS_EJECUCION')

# Close parent Logistica
logistica_end = """                        </ul>
                    </div>
                </li>
                <?php } ?>"""
content = content.replace('                        </ul>\n                    </div>\n                </li>\n\n                <!-- ==============================================================================                   CATEGORÍA 2', '                        </ul>\n                    </div>\n                </li>\n                <?php } ?>\n\n                <!-- ==============================================================================')
content = re.sub(r'                        </ul>\n                    </div>\n                </li>\n\s*<!-- ==============================================================================\n\s*CATEGORÍA 2', logistica_end + '\n\n                <!-- ==============================================================================\n                        CATEGORÍA 2', content)

with open('/home/christianguarneros/proyectos/mrp/Views/Template/nav_admin.php', 'w') as f:
    f.write(content)

