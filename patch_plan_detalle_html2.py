import re

with open('/home/christianguarneros/proyectos/mrp/Views/Lgs_planeaciones/index.php', 'r') as f:
    html_content = f.read()

# We can just look for the end of the table
block_to_find = """                                </tbody>
                            </table>
                        </div>
                    </div>"""

new_block = """                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div id="containerAccionesDetalle" class="d-flex justify-content-end mt-4 mb-3 pe-4">
                    </div>"""

if block_to_find in html_content:
    html_content = html_content.replace(block_to_find, new_block, 1)
    with open('/home/christianguarneros/proyectos/mrp/Views/Lgs_planeaciones/index.php', 'w') as f:
        f.write(html_content)
    print("Added containerAccionesDetalle")
else:
    print("Block not found")

