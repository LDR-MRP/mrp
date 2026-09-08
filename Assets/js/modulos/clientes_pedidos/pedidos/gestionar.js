document.addEventListener(
    "DOMContentLoaded",
    function () {
        inicializarGestionPedido();
    }
);


function inicializarGestionPedidoold()
{
    const pedido =window.GESTION_PEDIDO || {};
    const estatus =String(pedido.estatus || "").trim().toUpperCase();

    if (estatus === "PENDIENTE") {
        preguntarInicioGestionPedido();
    }
}

function inicializarGestionPedido()
{
    const pedido =window.GESTION_PEDIDO || {};
    const estatus =String(pedido.estatus || "").trim().toUpperCase();

    /*
     * ========================================================
     * APLICAR REGLAS DE INTERFAZ
     * ========================================================
     */

    actualizarEstadoInterfazGestion(estatus);

    /*
     * ========================================================
     * PEDIDO PENDIENTE
     * ========================================================
     */

    if (estatus === "PENDIENTE") {
        preguntarInicioGestionPedido();
    }
}


async function preguntarInicioGestionPedido()
{
    const result = await Swal.fire({

        title: "¿Deseas iniciar la gestión del pedido?",

        html: `
            <div class="text-center">

                <p class="mb-3">
                    Al confirmar esta acción, el pedido cambiará al estatus
                    <strong>EN REVISIÓN</strong> y comenzará formalmente
                    su proceso de gestión administrativa.
                </p>

                <p class="mb-3">
                    A partir de este momento, el distribuidor ya no podrá
                    realizar modificaciones ni cancelar el pedido mientras
                    se encuentre en proceso de revisión.
                </p>

                <p class="mb-0 text-muted">
                    Además, se enviará una notificación por correo electrónico
                    al distribuidor informándole que su pedido ha comenzado
                    a ser revisado por el equipo responsable.
                </p>

            </div>
        `,

        icon: "question",

        showCancelButton: true,

        confirmButtonText: `
            <i class="ri-play-circle-line me-1"></i>
            Sí, iniciar gestión
        `,

        cancelButtonText: "Cancelar",

        reverseButtons: true,

        focusConfirm: false,

        allowOutsideClick: false,

        customClass: {
            popup: "swal-pedido-gestion",
            title: "text-center",
            htmlContainer: "text-center"
        }

    });


    if (!result.isConfirmed) {
        return;
    }


    await iniciarGestionPedido();
}


async function iniciarGestionPedido()
{
    const pedido =window.GESTION_PEDIDO || {};

    try {

        Swal.fire({

            title:"Iniciando gestión...",
            allowOutsideClick:false,
            allowEscapeKey:false,
            didOpen: () => {Swal.showLoading();}
        });

        const response =
            await fetch(

                `${base_url}/ped_pedidos/iniciarGestion`,

                {
                    method:"POST",
                    headers: {
                        "Content-Type":"application/json"
                    },

                    body:
                        JSON.stringify({
                            clave:pedido.clave
                        })
                }

            );

        const result = await response.json();

        if (!response.ok || !result.status) {
            throw new Error(
                result.message
                || "No fue posible iniciar la gestión."
            );
        }

        await Swal.fire({
            title:"Gestión iniciada",
            text:"El pedido ahora se encuentra en revisión.",
            icon:"success",
            confirmButtonText:"Continuar"
        });
        window.location.reload();

    } catch (error) {
        console.error("Error iniciarGestionPedido:",error);
        Swal.fire({
            title:"No fue posible iniciar",
            text:error.message || "Ocurrió un error al iniciar la gestión.",
            icon:"error"
        });

    }
}



function actualizarEstadoInterfazGestion(estatus)
{
    const estado =String(estatus || "").trim().toUpperCase();

    /*
     * ========================================================
     * DETERMINAR SI LA GESTIÓN ESTÁ INICIADA
     * ========================================================
     */

    const gestionIniciada =estado !== "PENDIENTE";

    /*
     * ========================================================
     * BOTONES DE GESTIÓN
     * ========================================================
     */

    document
        .querySelectorAll(
            ".accion-gestion-pedido"
        )
        .forEach(
            function (elemento) {
                if (gestionIniciada) {
                    elemento.classList.remove("d-none");
                } else {
                    elemento.classList.add("d-none");
                }

            }
        );

    /*
     * ========================================================
     * CAMPOS EDITABLES
     * ========================================================
     */

    document.querySelectorAll(".campo-gestion-pedido")
        .forEach(
            function (elemento) {
                elemento.disabled =!gestionIniciada;
            }
        );

    /*
     * ========================================================
     * ALERTA DE GESTIÓN PENDIENTE
     * ========================================================
     */
    const alerta =document.getElementById("alertaGestionPendiente");

    if (alerta) {
        alerta.classList.toggle("d-none",gestionIniciada);
    }
}