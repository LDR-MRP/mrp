import re

with open('/home/christianguarneros/proyectos/mrp/Views/Lgs_planeaciones/index.php', 'r') as f:
    html_content = f.read()

# Buscamos el final de la tabla de detalles de envios, o el final de view-detalle-planeaciones
# Para estar seguros, agregamos el div antes del cierre del main card body en detalle
block_to_find = """                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>"""

new_block = """                                </tbody>
                            </table>
                        </div>
                        
                        <!-- BOTONES DE ACCIÓN (APROBAR / RECHAZAR / CLONAR) -->
                        <div id="containerAccionesDetalle" class="d-flex justify-content-end mt-4">
                        </div>

                    </div>
                </div>
            </section>"""

if block_to_find in html_content:
    html_content = html_content.replace(block_to_find, new_block)
    with open('/home/christianguarneros/proyectos/mrp/Views/Lgs_planeaciones/index.php', 'w') as f:
        f.write(html_content)
    print("Added containerAccionesDetalle")
else:
    print("Block not found")

