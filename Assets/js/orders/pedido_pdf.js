async function imprimirPedidoPdf(
    clave
) {

    clave =String(clave || "").trim();
    if (!clave) {
        throw new Error(
            "No fue posible identificar el pedido."
        );
    }

    /*
     * ==========================================================
     * VALIDAR PDFMAKE
     * ==========================================================
     */

    if (typeof pdfMake=== "undefined") {
        throw new Error(
            "La librería pdfMake no se encuentra disponible."
        );

    }

    /*
     * ==========================================================
     * CONSULTAR PEDIDO
     * ==========================================================
     */
    const response =
        await fetch(
            `${base_url}/orders/getPedidoPdf/${encodeURIComponent(
                clave
            )}`,
            {
                method: "GET",
                headers: {
                    "Accept": "application/json"
                }
            }
        );

    /*
     * ==========================================================
     * VALIDAR TIPO DE RESPUESTA
     * ==========================================================
     */

    const contentType =response.headers.get("content-type") || "";


    if (!contentType.includes("application/json")
    ) {

        const texto =await response.text();
        console.error(
            "Respuesta no JSON:",
            texto
        );

        throw new Error(
            "El servidor devolvió una respuesta no válida."
        );

    }

    const result = await response.json();

    /*
     * ==========================================================
     * VALIDAR RESULTADO
     * ==========================================================
     */

    if (!response.ok || !result.status) {

        throw new Error(
            result.message
            || "No fue posible obtener la información del pedido."
        );

    }


    if (
        !result.data
        || !result.data.pedido
    ) {

        throw new Error(
            "No se recibió la información necesaria para generar el PDF."
        );

    }


    /*
     * ==========================================================
     * GENERAR
     * ==========================================================
     */

    await generarPdfPedido(
        result.data.pedido,
        result.data.detalles || []
    );


    return true;

}



async function generarPdfPedido(pedido,detalles) {
    /*
     * ==========================================================
     * LOGO
     * ==========================================================
     */

    const logoBase64 =await obtenerLogoPedidoBase64();

    /*
     * ==========================================================
     * VALIDAR DETALLES
     * ==========================================================
     */

    detalles =Array.isArray(detalles) ? detalles : [];

    /*
     * ==========================================================
     * DISTRIBUIDOR
     * ==========================================================
     */

    const distribuidor =
        pedido.nombre_comercial
        || pedido.razon_social
        || "Distribuidor";

    /*
     * ==========================================================
     * SOLICITANTE
     * ==========================================================
     */
    const solicitante =`${pedido.nombre_usuario || ""} ${pedido.apellido_usuario || ""}`.trim()|| "No especificado";

    /*
     * ==========================================================
     * TOTAL UNIDADES
     * ==========================================================
     */
    const totalUnidades =detalles.reduce(
            function (
                acumulado,
                detalle
            ) {

                return acumulado
                    + Number(
                        detalle.cantidad_solicitada
                        || 0
                    );

            },
            0
        );

    /*
     * ==========================================================
     * TABLA DE UNIDADES
     * ==========================================================
     */

    const cuerpoDetalle = [

        [
            {
                text: "MODELO",
                style: "tableHeader"
            },

            {
                text: "CANT.",
                style: "tableHeader",
                alignment: "center"
            },

            {
                text: "ENTREGA / DESTINO",
                style: "tableHeader"
            },

            {
                text: "PRECIO UNIT.",
                style: "tableHeader",
                alignment: "right"
            },

            {
                text: "IMPORTE",
                style: "tableHeader",
                alignment: "right"
            }
        ]

    ];

    /*
     * ==========================================================
     * AGREGAR UNIDADES
     * ==========================================================
     */
    detalles.forEach(
        function (detalle) {

            const cantidad =Number(detalle.cantidad_solicitada || 0);
            const precio =Number(detalle.precio_unitario || 0);
            const importe =Number(detalle.subtotal ?? (precio * cantidad));

            /*
             * ==================================================
             * DESTINO
             * ==================================================
             */
            let destino ="No especificado";
            const tipoEntrega =String(detalle.tipo_entrega || "").trim().toUpperCase();

            if (tipoEntrega=== "SUCURSAL") {
                destino =detalle.nombre_sucursal|| (
                        detalle.idsucursal_entrega
                            ? `Sucursal ${detalle.idsucursal_entrega}`
                            : "Sucursal"
                    );

            } else if (
                tipoEntrega=== "OTRA_DIRECCION"
            ) {
                destino =
                    detalle.direccion_entrega
                    || "Dirección no especificada";

            }

            /*
             * ==================================================
             * NOMBRE DEL MODELO
             * ==================================================
             */

            const nombreModelo =detalle.nombre || detalle.modelo || "Unidad";


            const descripcionModelo =
                [
                    detalle.marca,
                    detalle.version,
                    detalle.anio
                ]
                    .filter(Boolean)
                    .join(" · ");


            /*
             * ==================================================
             * FILA
             * ==================================================
             */

            cuerpoDetalle.push([

                /*
                 * MODELO
                 */

                {
                    stack: [

                        {
                            text:nombreModelo,
                            bold:true,
                            fontSize:9.5,
                            color:"#101828"
                        },

                        descripcionModelo
                            ? {
                                text:descripcionModelo,
                                fontSize:8,
                                color:"#344054",
                                margin:[0, 3, 0, 0]
                            }
                            : {},

                        detalle.clave_modelo
                            ? {
                                text:`Clave: ${detalle.clave_modelo}`,
                                fontSize:7.5,
                                color:"#475467",
                                margin:[0, 3, 0, 0]
                            }
                            : {}

                    ]
                },

                /*
                 * CANTIDAD
                 */
                {
                    text:cantidad.toString(),
                    alignment:"center",
                    bold:true,
                    fontSize:9,
                    color:"#101828"
                },

                /*
                 * ENTREGA
                 */

                {
                    stack: [

                        {
                            text:formatearTipoEntregaPdf(tipoEntrega),
                            bold:true,
                            fontSize:8.5,
                            color:"#101828"
                        },

                        {
                            text:destino,
                            fontSize:8,
                            color:"#344054",
                            margin:[0, 3, 0, 0]
                        }

                    ]
                },

                /*
                 * PRECIO UNITARIO
                 */

                {
                    text:formatMoneyPdfPedido(precio),
                    alignment:"right",
                    fontSize:8.5,
                    color:"#101828"
                },


                /*
                 * IMPORTE
                 */

                {
                    text:formatMoneyPdfPedido(importe),
                    alignment:"right",
                    bold:true,
                    fontSize:8.5,
                    color:"#101828"
                }

            ]);
        }
    );


    /*
     * ==========================================================
     * DOCUMENTO
     * ==========================================================
     */

    const docDefinition = {
        pageSize:"A4",
        pageOrientation:"portrait",
        pageMargins: [
            35,
            40,
            35,
            48
        ],

        /*
         * ======================================================
         * FOOTER
         * ======================================================
         */

        footer:
            function (
                currentPage,
                pageCount
            ) {

                return {

                    columns: [

                        {
                            text:"Portal de Pedidos · LDR Solutions",
                            alignment:"left",
                            fontSize:7.5,
                            bold:true,
                            color:"#475467",
                            margin:[35, 12, 0, 0]
                        },

                        {
                            text:`Página ${currentPage} de ${pageCount}`,
                            alignment:"right",
                            fontSize:7.5,
                            bold:true,
                            color:"#475467",
                            margin:[0, 12, 35, 0]
                        }

                    ]
                };

            },


        content: [

            /*
             * ==================================================
             * ENCABEZADO
             * ==================================================
             */

            {

                columns: [

                    /*
                     * ==========================================
                     * LOGO
                     * ==========================================
                     */

                    {
                        width:"*",
                        stack: [
                            logoBase64
                                ? {
    image: logoBase64,
    width: 70,
    margin: [0, 0, 0, 6]
}
                                : {
                                    text:"LDR SOLUTIONS",
                                    fontSize:13,
                                    bold:true,
                                    color:"#101828"
                                },

                            {
                                text:"Portal de Pedidos",
                                fontSize:8.5,
                                bold:true,
                                color:"#344054",
                                margin:[0, 2, 0, 0]
                            }

                        ]
                    },


                    /*
                     * ==========================================
                     * HOJA DE PEDIDO
                     * ==========================================
                     */

                    {
                        width:240,
                        alignment:"right",
                        stack: [

                            {
                                text:"HOJA DE PEDIDO",
                                fontSize:19,
                                bold:true,
                                color:"#101828"
                            },

                            {
                                text:pedido.folio_pedido || "",
                                fontSize:11,
                                bold:true,
                                color:"#EA580C",
                                margin:[0, 5, 0, 0]
                            },

                            {
                                text:`Generado: ${formatFechaHoraActualPdf()}`,
                                fontSize:7.5,
                                color:"#344054",
                                margin:[0, 5, 0, 0]
                            }

                        ]
                    }

                ]

            },

            /*
             * ==================================================
             * LÍNEA
             * ==================================================
             */

            {
                canvas: [

                    {
                        type:"line",
                        x1:0,
                        y1:0,
                        x2:525,
                        y2:0,
                        lineWidth:1.2,
                        lineColor:"#D0D5DD"
                    }
                ],
                margin:[0, 15, 0, 15]
            },


            /*
             * ==================================================
             * RESUMEN
             * ==================================================
             */
            {

                table: {
                    widths: [
                        "*",
                        "*",
                        "*",
                        "*"
                    ],
                    body: [

                        [
                            crearDatoResumenPdf("ESTATUS",pedido.estatus || "PENDIENTE"),
                            crearDatoResumenPdf("PRIORIDAD",pedido.prioridad || "NORMAL"),
                            crearDatoResumenPdf("MODELOS",detalles.length),
                            crearDatoResumenPdf("UNIDADES",totalUnidades)

                        ]
                    ]

                },

                layout: {

                    fillColor:
                        function () {
                            return "#F9FAFB";
                        },
                    hLineColor:
                        function () {
                            return "#D0D5DD";
                        },
                    vLineColor:
                        function () {
                            return "#D0D5DD";
                        },

                    paddingLeft:
                        function () {
                            return 8;
                        },

                    paddingRight:
                        function () {
                            return 8;
                        },

                    paddingTop:
                        function () {
                            return 8;
                        },

                    paddingBottom:
                        function () {
                            return 8;
                        }
                },

                margin:[0, 0, 0, 20]
            },


            /*
             * ==================================================
             * INFORMACIÓN PEDIDO / DISTRIBUIDOR
             * ==================================================
             */

            {

                columns: [

                    /*
                     * PEDIDO
                     */

                    {

                        width:"50%",
                        stack: [
                            {
                                text:"INFORMACIÓN DEL PEDIDO",
                                style:"sectionTitle"
                            },
                            crearFilaInfoPdf(
                                "Fecha pedido",
                                formatDatePdfPedido(
                                    pedido.fecha_pedido,
                                    true
                                )
                            ),

                            crearFilaInfoPdf("Fecha requerida",formatDatePdfPedido(pedido.fecha_requerida)
                            ),

                            crearFilaInfoPdf("Mes facturación",pedido.mes_facturacion_deseado || "No especificado"),

                            pedido.version
                                ? crearFilaInfoPdf(
                                    "Versión",
                                    pedido.version
                                )
                                : {}

                        ]

                    },

                    /*
                     * DISTRIBUIDOR
                     */

                    {

                        width:"50%",
                        stack: [
                            {
                                text:"DISTRIBUIDOR",
                                style:"sectionTitle"
                            },

                            crearFilaInfoPdf("Nombre",distribuidor),

                            crearFilaInfoPdf(
                                "Clave",
                                pedido.clave_distribuidor
                                || pedido.codigo_cliente
                                || "N/A"
                            ),

                            crearFilaInfoPdf(
                                "Solicitante",
                                solicitante
                            ),

                            pedido.correo_usuario
                                ? crearFilaInfoPdf(
                                    "Correo",
                                    pedido.correo_usuario
                                )
                                : {}

                        ]

                    }

                ],
                columnGap:22,
                margin:[0, 0, 0, 22]

            },

            /*
             * ==================================================
             * TÍTULO UNIDADES
             * ==================================================
             */

            {
                text:"UNIDADES SOLICITADAS",
                style:"sectionTitle",
                margin:[0, 0, 0, 8]
            },


            /*
             * ==================================================
             * TABLA
             * ==================================================
             */

            {

                table: {
                    headerRows:1,
                    dontBreakRows:true,
                    widths: [
                        "*",
                        38,
                        120,
                        76,
                        76
                    ],
                    body:cuerpoDetalle

                },


                layout: {

                    fillColor:
                        function (
                            rowIndex
                        ) {

                            if (rowIndex === 0) {

                                return "#F2F4F7";

                            }
                            return null;

                        },

                    hLineColor:
                        function () {
                            return "#D0D5DD";
                        },


                    vLineColor:
                        function () {

                            return "#D0D5DD";

                        },


                    hLineWidth:
                        function () {
                            return 0.7;
                        },

                    vLineWidth:
                        function () {
                            return 0.7;
                        },

                    paddingLeft:
                        function () {
                            return 6;
                        },

                    paddingRight:
                        function () {
                            return 6;
                        },

                    paddingTop:
                        function () {
                            return 8;
                        },

                    paddingBottom:
                        function () {
                            return 8;
                        }
                },

                margin:[0, 0, 0, 20]

            },

            /*
             * ==================================================
             * OBSERVACIONES + TOTALES
             * ==================================================
             */

            {

                columns: [

                    /*
                     * OBSERVACIONES
                     */

                    {
                        width:"*",
                        stack: [

                            {
                                text:"OBSERVACIONES",
                                style:"sectionTitle"
                            },
                            {
                                text:pedido.observaciones || "Sin observaciones registradas.",
                                fontSize:8.5,
                                color:"#1D2939",
                                lineHeight:1.35,
                                margin:[0, 5, 18, 0]
                            }

                        ]
                    },

                    /*
                     * TOTALES
                     */

                    {

                        width:195,
                        table: {

                            widths: [
                                "*",
                                100
                            ],

                            body: [

                                crearFilaTotalPdf("Subtotal",pedido.subtotal),
                                crearFilaTotalPdf("Descuento",pedido.descuento),
                                crearFilaTotalPdf("IVA",pedido.iva),
                                [

                                    {
                                        text:"TOTAL",
                                        bold:true,
                                        fontSize:10,
                                        fillColor:"#FFF7ED",
                                        color:"#9A3412",
                                        margin:[6, 7, 6, 7]
                                    },

                                    {
                                        text:formatMoneyPdfPedido(pedido.total),
                                        alignment:"right",
                                        bold:true,
                                        fontSize:10,
                                        fillColor:"#FFF7ED",
                                        color:"#C2410C",
                                        margin:[6, 7, 6, 7]
                                    }
                                ]
                            ]

                        },

                        layout:"noBorders"

                    }
                ],
                columnGap:15,
                unbreakable:true
            }

        ],


        /*
         * ======================================================
         * ESTILOS
         * ======================================================
         */

        styles: {

            sectionTitle: {

                fontSize:9,
                bold:true,
                color:"#101828",
                margin:[0, 0, 0, 7]

            },


            tableHeader: {

                bold:true,
                fontSize:8,
                color:"#101828"
            }

        },


        /*
         * ======================================================
         * ESTILO GENERAL
         * ======================================================
         */

        defaultStyle: {

            font:"Roboto",
            fontSize:9,
            color:"#1D2939"

        }

    };


    /*
     * ==========================================================
     * NOMBRE DEL ARCHIVO
     * ==========================================================
     */

    const nombreArchivo =
        `Hoja_Pedido_${
            pedido.folio_pedido
            || "Pedido"
        }.pdf`;


    /*
     * ==========================================================
     * ABRIR PDF
     * ==========================================================
     */

    pdfMake.createPdf(docDefinition).open();

}


async function obtenerLogoPedidoBase64() {

    /*
     * ==========================================================
     * AQUÍ ESTÁ LA RUTA DE TU LOGO
     * ==========================================================
     */
    const url =`${base_url}/Assets/images/ldr_negro.png`;

    try {

        const response =await fetch(url,
                {
                    cache: "force-cache"
                }
            );

        if (!response.ok) {
            throw new Error(
                `No fue posible cargar el logo. HTTP ${response.status}`
            );

        }

        const blob =await response.blob();

        return await new Promise(
            function (
                resolve,
                reject
            ) {
                const reader =new FileReader();

                reader.onloadend =
                    function () {

                        resolve(
                            reader.result
                        );

                    };

                reader.onerror =function () {
                        reject(
                            new Error(
                                "No fue posible convertir el logo."
                            )
                        );
                    };

                reader.readAsDataURL(
                    blob
                );

            }
        );

    } catch (error) {

        console.error(
            "Error cargando logo PDF:",
            error
        );
        return null;

    }
}



function formatearTipoEntregaPdf(
    tipo
) {

    const valor =
        String(
            tipo
            || ""
        )
            .trim()
            .toUpperCase();

    switch (valor) {

        case "SUCURSAL":
        return "Sucursal";
        case "OTRA_DIRECCION":
        return "Otra dirección";
        default:

            return valor
                .replaceAll(
                    "_",
                    " "
                );

    }
}


function formatMoneyPdfPedido(
    value
) {

    const cantidad =Number(value|| 0);
    if (Number.isNaN(cantidad)) {

        return "$0.00 MXN";
    }


    return cantidad.toLocaleString(
        "es-MX",
        {
            style:"currency",
            currency:"MXN",
            minimumFractionDigits:2,
            maximumFractionDigits:2
        }
    );

}   

function formatDatePdfPedido(fecha,incluirHora = false) {

    if (!fecha || fecha === "0000-00-00" || fecha === "0000-00-00 00:00:00") {

        return "No especificada";

    }

    const fechaNormalizada =
        String(fecha)
            .replace(
                " ",
                "T"
            );

    const date =new Date(fechaNormalizada);

    if (Number.isNaN(date.getTime())) {
        return String(
            fecha
        );

    }


    if (incluirHora) {

        return date.toLocaleString(
            "es-MX",
            {
                day:"2-digit",
                month:"2-digit",
                year:"numeric",
                hour:"2-digit",
                minute:"2-digit",
                hour12:false
            }
        );

    }

    return date.toLocaleDateString(
        "es-MX",
        {
            day:"2-digit",
            month:"2-digit",
            year:"numeric"
        }
    );

}

function formatFechaHoraActualPdf() {

    return new Date()
        .toLocaleString(
            "es-MX",
            {
                day:"2-digit",
                month:"2-digit",
                year:"numeric",
                hour:"2-digit",
                minute:"2-digit",
                hour12:false
            }
        );

}

async function obtenerLogoPedidoBase64() {

    /*
     * ==========================================================
     * AQUÍ ESTÁ LA RUTA DE TU LOGO
     * ==========================================================
     */
    const url =`${base_url}/Assets/images/ldr_negro.png`;

    try {

        const response =await fetch(url,
                {
                    cache: "force-cache"
                }
            );

        if (!response.ok) {
            throw new Error(
                `No fue posible cargar el logo. HTTP ${response.status}`
            );

        }

        const blob =await response.blob();

        return await new Promise(
            function (
                resolve,
                reject
            ) {
                const reader =new FileReader();

                reader.onloadend =
                    function () {

                        resolve(
                            reader.result
                        );

                    };

                reader.onerror =function () {
                        reject(
                            new Error(
                                "No fue posible convertir el logo."
                            )
                        );
                    };

                reader.readAsDataURL(
                    blob
                );

            }
        );

    } catch (error) {

        console.error(
            "Error cargando logo PDF:",
            error
        );
        return null;

    }
}


function crearDatoResumenPdf(
    titulo,
    valor
) {

    return {

        stack: [
            {
                text:titulo,
                fontSize:7.5,
                bold:true,
                color:"#475467"
            },

            {
                text:String(valor ?? ""),
                fontSize:10,
                bold:true,
                color:"#101828",
                margin:[0, 4, 0, 0]
            }

        ],
        margin:[3, 2, 3, 2]

    };

}

function crearFilaInfoPdf(
    etiqueta,
    valor
) {

    return {

        columns: [

            {
                width:86,
                text:etiqueta,
                fontSize:8,
                bold:true,
                color:"#475467"
            },

            {
                width:"*",
                text:String(valor?? ""),
                fontSize:8.5,
                bold:true,
                color:"#101828"
            }

        ],

        margin:[0, 4, 0, 4]
    };

}

function crearFilaTotalPdf(
    etiqueta,
    valor
) {

    return [
        {
            text:etiqueta,
            fontSize:8.5,
            bold:true,
            color:"#475467",
            margin:[6, 5, 6, 5]
        },
        {
            text:formatMoneyPdfPedido(valor),
            alignment:"right",
            fontSize:8.5,
            bold:true,
            color:"#101828",
            margin:[6, 5, 6, 5]
        }
    ];

}
