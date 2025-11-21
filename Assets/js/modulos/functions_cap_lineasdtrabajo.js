let tableCategorias;
let rowTable = "";
let divLoading = document.querySelector("#divLoading");
document.addEventListener('DOMContentLoaded', function(){

    // tableCategorias = $('#tableCategorias').dataTable( {
    //     "aProcessing":true,
    //     "aServerSide":true,
    //     "language": {
    //         "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
    //     },
    //     "ajax":{
    //         "url": " "+base_url+"/Categorias/getCategorias",
    //         "dataSrc":""
    //     },
    //     "columns":[
    //         {"data":"idcategoria"},
    //         {"data":"nombre"},
    //         {"data":"descripcion"},
    //         {"data":"status"},
    //         {"data":"options"}
    //     ],
    //     'dom': 'lBfrtip',
    //     'buttons': [
    //         {
    //             "extend": "copyHtml5",
    //             "text": "<i class='far fa-copy'></i> Copiar",
    //             "titleAttr":"Copiar",
    //             "className": "btn btn-secondary"
    //         },{
    //             "extend": "excelHtml5",
    //             "text": "<i class='fas fa-file-excel'></i> Excel",
    //             "titleAttr":"Esportar a Excel",
    //             "className": "btn btn-success"
    //         },{
    //             "extend": "pdfHtml5",
    //             "text": "<i class='fas fa-file-pdf'></i> PDF",
    //             "titleAttr":"Esportar a PDF",
    //             "className": "btn btn-danger"
    //         },{
    //             "extend": "csvHtml5",
    //             "text": "<i class='fas fa-file-csv'></i> CSV",
    //             "titleAttr":"Esportar a CSV",
    //             "className": "btn btn-info"
    //         }
    //     ],
    //     "resonsieve":"true",
    //     "bDestroy": true,
    //     "iDisplayLength": 10,
    //     "order":[[0,"desc"]]  
    // });






	//NUEVA CATEGORIA
    let formLineas = document.querySelector("#formLineas");
    formLineas.onsubmit = function(e) {
        e.preventDefault();
        // let strNombre = document.querySelector('#txtNombre').value;
        // let strDescripcion = document.querySelector('#txtDescripcion').value;
        // let intStatus = document.querySelector('#listStatus').value;        
        // if(strNombre == '' || strDescripcion == '' || intStatus == '')
        // {
        //     swal("Atención", "Todos los campos son obligatorios." , "error");
        //     return false;
        // }
        divLoading.style.display = "flex";
        let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
        let ajaxUrl = base_url+'/cap_lineasdtrabajo/setCategoria'; 
        let formData = new FormData(formLineas);
        request.open("POST",ajaxUrl,true);
        request.send(formData);
        request.onreadystatechange = function(){
           if(request.readyState == 4 && request.status == 200){
                
                // let objData = JSON.parse(request.responseText);
                // if(objData.status)
                // {
                //     // if(rowTable == ""){
                //     //     tableCategorias.api().ajax.reload();
                //     // }else{
                //     //     htmlStatus = intStatus == 1 ? 
                //     //         '<span class="badge badge-success">Activo</span>' : 
                //     //         '<span class="badge badge-danger">Inactivo</span>';
                //     //     rowTable.cells[1].textContent = strNombre;
                //     //     rowTable.cells[2].textContent = strDescripcion;
                //     //     rowTable.cells[3].innerHTML = htmlStatus;
                //     //     rowTable = "";
                //     // }

                //     // $('#modalFormLineass').modal("hide");
                //     formLineas.reset();
                //     swal("Categoria", objData.msg ,"success");
                   
                // }else{
                //     swal("Error", objData.msg , "error");
                // }              
            } 
            divLoading.style.display = "none";
            return false;
        }
    }

}, false);






function openModal()
{
    rowTable = "";
    document.querySelector('#idCategoria').value ="";
    document.querySelector('.modal-header').classList.replace("headerUpdate", "headerRegister");
    document.querySelector('#btnActionForm').classList.replace("btn-info", "btn-primary");
    document.querySelector('#btnText').innerHTML ="Guardar";
    document.querySelector('#titleModal').innerHTML = "Nueva Categoría";
    document.querySelector("#formLineas").reset();
    $('#modalFormLineass').modal('show');
    removePhoto();
}