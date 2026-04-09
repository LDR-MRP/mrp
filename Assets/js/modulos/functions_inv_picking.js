let currentPicking = null;

document.addEventListener("DOMContentLoaded", function () {
    cargarPickings();
});

// 🔹 Cargar lista
function cargarPickings(){
    fetch(base_url + '/Inv_picking/getPickings')
    .then(res => res.json())
    .then(data => {

        let html = "";

        data.forEach(p => {
            html += `
            <li class="list-group-item" onclick="cargarDetalle(${p.idpicking})">
                ${p.folio}
            </li>`;
        });

        document.getElementById("listaPicking").innerHTML = html;
    });
}

// 🔹 Detalle
function cargarDetalle(id){
    currentPicking = id;

    fetch(base_url + '/Inv_picking/getDetalle/' + id)
    .then(res => res.json())
    .then(data => {

        let html = "";

        data.forEach((item, i) => {

            html += `
            <tr>
                <td>${i+1}</td>
                <td>${item.descripcion}</td>
                <td>${item.codigo_ubicacion}</td>
                <td>${item.lote}</td>
                <td>${item.cantidad_solicitada}</td>
                <td>${item.cantidad_existente}</td>
                <td>
                    <input type="number"
                        class="form-control pick"
                        data-id="${item.iddetalle}"
                        data-inv="${item.inventarioid}"
                        data-ubi="${item.ubicacionid}"
                        max="${item.cantidad_solicitada}"
                        value="${item.cantidad_pickeada}">
                </td>
            </tr>`;
        });

        document.getElementById("detallePicking").innerHTML = html;
    });
}

// 🔹 Guardar
function guardarPicking(){

    let inputs = document.querySelectorAll(".pick");
    let data = [];

    inputs.forEach(input => {

        let cantidad = parseFloat(input.value) || 0;
        let max = parseFloat(input.max);

        if(cantidad > max){
            alert("No puedes pickear más de lo solicitado");
            return;
        }

        data.push({
            iddetalle: input.dataset.id,
            inventarioid: input.dataset.inv,
            ubicacionid: input.dataset.ubi,
            cantidad: cantidad
        });
    });

    fetch(base_url + '/Inv_picking/setPicking', {
        method: "POST",
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        alert(res.msg);
        cargarDetalle(currentPicking);
    });
}

function crearPicking(){

    let data = {
        folio: document.getElementById("folio").value,
        pedido: document.getElementById("pedido").value,
        prioridad: document.getElementById("prioridad").value
    };

    fetch(base_url + '/Inv_picking/setPickingHeader', {
        method: "POST",
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        alert("Picking creado");
        cargarPickings();
    });
}

function agregarProducto(idPicking){

    let inventarioid = prompt("ID del producto:");
    let ubicacionid = prompt("ID ubicación:");
    let cantidad = prompt("Cantidad:");

    fetch(base_url + '/Inv_picking/addDetalle', {
        method: "POST",
        body: JSON.stringify({
            pickingid: idPicking,
            inventarioid: inventarioid,
            ubicacionid: ubicacionid,
            cantidad: cantidad
        })
    })
    .then(res => res.json())
    .then(res => {
        alert("Producto agregado");
        cargarDetalle(idPicking);
    });
}