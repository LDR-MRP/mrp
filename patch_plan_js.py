import re

with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_planeaciones.js', 'r') as f:
    js_content = f.read()

# 1. Add data-distancia in renderTablaEnviosDisp
old_render = """                            <td>
                                <input class="form-check-input chk-envio" type="checkbox" value="${envio.id_envio}" data-costo="${envio.costo_total}" onchange="calcularTotalesPlan();">
                            </td>"""
new_render = """                            <td>
                                <input class="form-check-input chk-envio" type="checkbox" value="${envio.id_envio}" data-costo="${envio.costo_total}" data-distancia="${envio.km_total || 0}" onchange="calcularTotalesPlan();">
                            </td>"""

if old_render in js_content:
    js_content = js_content.replace(old_render, new_render)
    print("Replaced render checkbox.")

# 2. Update calcularTotalesPlan to also sum distances
old_calc = """function calcularTotalesPlan() {
    let checkboxes = document.querySelectorAll('.chk-envio:checked');
    let totalCosto = 0.0;
    
    checkboxes.forEach(chk => {
        totalCosto += parseFloat(chk.getAttribute('data-costo')) || 0;
    });
    
    let lbl = document.getElementById('lbl-monto-plan-display');
    if (lbl) lbl.innerText = '$' + totalCosto.toFixed(2);
}"""

new_calc = """function calcularTotalesPlan() {
    let checkboxes = document.querySelectorAll('.chk-envio:checked');
    let totalCosto = 0.0;
    let totalDistancia = 0.0;
    
    checkboxes.forEach(chk => {
        totalCosto += parseFloat(chk.getAttribute('data-costo')) || 0;
        totalDistancia += parseFloat(chk.getAttribute('data-distancia')) || 0;
    });
    
    let lblMonto = document.getElementById('lbl-monto-plan-display');
    if (lblMonto) lblMonto.innerText = '$' + totalCosto.toFixed(2);

    let lblDistancia = document.getElementById('lbl-distancia-plan-display');
    if (lblDistancia) lblDistancia.innerText = totalDistancia.toFixed(1) + ' km';
}"""

if old_calc in js_content:
    js_content = js_content.replace(old_calc, new_calc)
    print("Replaced calcularTotalesPlan.")

with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_planeaciones.js', 'w') as f:
    f.write(js_content)
