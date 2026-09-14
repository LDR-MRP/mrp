"use strict";

document.addEventListener(
    "DOMContentLoaded",
    function () {
        inicializarDetallePedido();

    }
);
function inicializarDetallePedido() {   

configurarBotonImprimirPedido();

configurarErroresImagenesPedido();

}

function configurarErroresImagenesPedido() {

    const imagenes =document.querySelectorAll(".order-detail-product-image img");

    imagenes.forEach(
        function (imagen) {
            imagen.addEventListener(
                "error",
                function () {

                    if (this.dataset.errorHandled=== "1") {

                        return;
                    }
                    this.dataset.errorHandled ="1";
                    this.src =`${base_url}/Assets/images/no-image.png`;

                }
            );

        }
    );
}
/*
 * ============================================================
 * ALERTAS
 * ============================================================
 */

function mostrarAlertaDetallePedido(
    mensaje,
    tipo = "info"
) {

    if (typeof Swal!== "undefined") {

        Swal.fire({
            icon:tipo,
            title:tipo === "error"? "No fue posible continuar": "Información",
            text:mensaje,
            confirmButtonText:"Aceptar"

        });

        return;
    }
    alert(mensaje);

}

function configurarBotonImprimirPedido() {

    const btn =document.getElementById("btnPrintOrderDetail");
    if (!btn) {
        return;
    }
    btn.addEventListener(
        "click",
        async function () {
            const clave =this.dataset.clave || window.ORDER_DETAIL?.clave || "";
            const htmlOriginal =btn.innerHTML;
            btn.disabled =true;
            btn.innerHTML = `<i class="ri-loader-4-line ri-spin"></i>Generando...`;

            try {
                await imprimirPedidoPdf(clave);

            } catch (error) {
                console.error("Error al generar PDF:",error
                );
                mostrarAlertaDetallePedido(error.message || "No fue posible generar el PDF.", "error");

            } finally {
                btn.disabled =false;
                btn.innerHTML =Original;

            }

        }
    );

}














































































