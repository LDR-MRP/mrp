import re

with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_envios.js', 'r') as f:
    content = f.read()

# Add handleTipoTrasladoEnvio
new_func = """
function handleTipoTrasladoEnvio() {
    const idTipoTraslado = document.getElementById('id_tipo_traslado') ? document.getElementById('id_tipo_traslado').value : '';
    const btnAddCarga = document.querySelector('button[onclick="agregarNodoRuta(null, true)"]');
    const btnAddDescarga = document.querySelector('button[onclick="agregarNodoRuta(null, false)"]');
    
    if (idTipoTraslado == '2') {
        if (btnAddCarga) btnAddCarga.style.display = 'none';
        if (btnAddDescarga) btnAddDescarga.style.display = 'none';
        
        // Remove extra nodes if there are more than 2
        const cont = document.getElementById('contenedor-nodos-ruta');
        if (cont) {
            const items = cont.querySelectorAll('.nodo-item');
            if (items.length > 2) {
                for (let i = 2; i < items.length; i++) {
                    items[i].remove();
                }
                actualizarSecuenciaNodos();
            }
        }
    } else {
        if (btnAddCarga) btnAddCarga.style.display = '';
        if (btnAddDescarga) btnAddDescarga.style.display = '';
    }
}
"""

if "function handleTipoTrasladoEnvio" not in content:
    content += "\n" + new_func

# Patch agregarNodoRuta
# Find: function agregarNodoRuta(data, isCarga = false) {
old_str = "function agregarNodoRuta(data, isCarga = false) {"
new_str = old_str + """
    const idTipoTraslado = document.getElementById('id_tipo_traslado') ? document.getElementById('id_tipo_traslado').value : '';
    if (idTipoTraslado == '2' && !data) {
        const contCheck = document.getElementById('contenedor-nodos-ruta');
        if (contCheck && contCheck.querySelectorAll('.nodo-item').length >= 2) {
            Swal.fire("Atención", "El tipo de traslado 'Chofer (Rodando)' solo permite un Origen y un Destino.", "warning");
            return;
        }
    }
"""
content = content.replace(old_str, new_str)

with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_envios.js', 'w') as f:
    f.write(content)

print("Patched JS")
