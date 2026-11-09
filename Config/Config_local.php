<?php
const BASE_URL = "http://localhost/mrp";

// Zona horaria
date_default_timezone_set('America/Mexico_City');

// Datos de conexión a Base de Datos Local (Docker)
const DB_HOST = "mrp-db";
const DB_NAME = "db_mrp";
const DB_USER = "mrp_user";
const DB_PASSWORD = "mrp_password";
const DB_CHARSET = "utf8";

// Configuración Email local
const ENVIRONMENT = 0;

// API MRP / ERP / WMS
const API_URL = "/api/v1";
const JWT_SECRET = "kgIO9A1mT1CZU+QTKjLjFXrYE2wuogGEHyqoK0Z/t/8=";
const JWT_SECRET_RRHH = "mi_super_secret_ultra_seguro_123456789";

const SPD = ".";
const SPM = ",";

// Símbolo de moneda
const SMONEY = "$";
const CURRENCY = "USD";

// API PayPal
const URLPAYPAL = "https://api-m.sandbox.paypal.com";
const IDCLIENTE = "";
const SECRET = "";

// WEBMASTERS
const MAIL_WEBMASTER = 'erick.pulido@ldrsolutions.com.mx';

// Configuración del Servidor SMTP Local / Pruebas
const MAIL_HOST = "smtp.gmail.com";
const MAIL_USER = "notificacion@ldrsolutions.com.mx";
const MAIL_PASS = "";
const MAIL_PORT = 465;
const MAIL_FROM_NAME = "Notificaciones LDR (Local)";

// Datos envío de correo
const NOMBRE_REMITENTE = "LDR - SOLUTIONS";
const EMAIL_REMITENTE = "carlosbunti97@gmail.com";
const NOMBRE_EMPESA = "LDR - SOLUTIONS";
const WEB_EMPRESA = "https://www.ldrsolutions.mx/";

const SHAREDHASH = "ldrsolutions";

// Datos Empresa
const DIRECCION = "Prol. P.º de la Reforma 1015-piso 24, Santa Fe, Contadero, Cuajimalpa de Morelos, 05348 Ciudad de México, CDMX";
const WHATSAPP = "+5572227706";

// Datos para Encriptar / Desencriptar
const KEY = 'carloscc';
const METHODENCRIPT = "ACC-128-ECB";

// Módulos
const MDASHBOARD = 1;
const MUSUARIOS = 2;

// Submódulos Planeación
const MPCONFPRODUCTOS = 6;
const MPBOMCONTROL = 7;
const MPPRODUCTOSTERMINADOS = 8;
const MPPLANPRODUCCION = 9;
const MPORDENES = 10;
const MPINDICADORESOT = 11;

// Submódulos inventario
const MILPRODUCTO = 16;
const MIPRECIOS = 17;
const MIINVENTARIO = 18;
const MIALMACENES = 19;
const MIPRODUCTOSSUSTITUTOS = 20;
const MIESQUEMAIMPUESTOS = 21;
const MIMOVIMIENTOS = 22;
const MICONCEPTOSMOVIMIENTOS = 23;
const MIKARDEX = 24;
const MILOTESPEDIMENTOS = 25;
const MIMULTIALMACENES = 26;
const MIUNIDADES = 83; // Inventario > Unidades por año modelo (Inv_unidades)
const MIINVDISTRIBUIDORES = 85; // Inventario > Retail de distribuidores (Inv_invdistribuidores)
const MICOLORESUNIDAD = 86; // Inventario > Catálogos > Colores de unidad (Inv_coloresunidad)
const MICOBRANZAUNIDADES = 87; // Inventario > Cobranza de unidades (Inv_cobranzaunidades)
const MIMONEDAS = 70;
const MIASIGNACIONESINV = 71;
const MIREPORTES = 72;
const MIMOVIMIENTOSALMACEN = 73;
const MISEDES = 67;
const MIZONAS = 68;
const MIUBICACIONES = 69;
const MIPICKING = 74;
const MICAPTURAVIN = 65;
const MITIPOCAMBIOMONEDA = 66;
const MIRECEPCION = 64;
const MIDESCUENTOS = 63;
const MITRASLADOS = 75;
const MIOPTRASLADOS = 76;

// Submódulos Ingeniería (vehículos)
const ING_MODELOS = 77;
const ING_MOTORES = 78;
const ING_TRANSMISIONES = 79;
const ING_CERTIFICACIONES = 80;
const ING_ESPECIFICACIONES = 81;
const ING_CONFIGURACIONES = 82;
const ING_JURIDICO = 84; // Perfil Jurídico: solo carga de certificaciones

// Submódulos Logística
const LGS_BANDEJA = 150;
const LGS_COSTOS = 151;
const LGS_MADRINAS = 152;
const LGS_CHOFERES = 153;
const LGS_PLATAFORMAS = 154;
const LGS_ENVIOS = 155;
const LGS_PLANEACIONES = 156;
const LGS_APROBACIONES = 157;
const LGS_EJECUCION = 158;
const LGS_EVIDENCIAS = 159;
const LGS_PANELRUTAS = 160;
const LGS_INCIDENCIAS = 161;
const LGS_GASTOSADICIONALES = 162;

// Permisos por pestaña del modal "Configuración de productos" (Inv_inventario)
const MICFG_MONEDA = 180;
const MICFG_PRECIO = 181;
const MICFG_FISCAL = 182;
const MICFG_IMPUESTO = 183;
const MICFG_LINEA = 184;
const MICFG_UBICACIONES = 185;
const MICFG_PROVEEDORES = 186;
const MICFG_CANTIDADES = 187;
const MICFG_PORTALWEB = 188;
const MICFG_VINES = 189;
const MICFG_FOTOSWEB = 190; // Pestaña "Fotos portal" (solo carátula y galería; perfil Marketing)

// Submódulos Capacidad
const MCESTACIONESTRABAJO = 27;
const MCLINEAS = 28;
const MCPLANTAS = 29;

// Submódulos Proveedores
const MPPROVEEDORES = 35;

// Submódulos pedidos
const MCLIENTES_CLIENTES = 36;
const MCLIENTES_PEDIDOS = 37;
const MCLIENTES_PROPUESTAS_COMERCIALES = 38;
const MCLIENTES_AUTORIZACIONES = 39;
const MCLIENTES_ASIGNACION_UNIDADES = 40;
const MCLIENTES_BACKORDERS = 41;
const MCLIENTES_FACTURAS = 42;
const MCLIENTES_ENTREGAS = 43;
const MCLIENTES_CANCELACIONES = 44;
const MCLIENTES_DEVOLUCIONES = 45;
const MCLIENTES_SEGUIMIENTO = 46;
const MCLIENTES_REPORTES = 47;
const MCLIENTES_BITACORA = 48;

// Submódulos Departamentos
const MCDEPARTAMENTOS = 200;
const MCMARCAS = 201;
const MCLI_GRUPOS = 202;
const MCLI_PUESTOS = 203;
const MCLI_CONTACTOS = 204;
const MCLI_REGIONALES = 205;
const MCLI_TIPOS_CLIENTES = 206;

// Submódulos Compras
const COM_COMPRAS = 50;
const COM_NEGOCIACIONES = 56;
const COM_REQUISICIONES = 51;
const COM_ORDENES = 52;
const PRV_PROVEEDORES = 53;
const CXP_FACTURAS = 54;
const CXP_PAGOS = 55;

const PERROR = 9;

// Roles
const RADMINISTRADOR = 1;
const RPLANIFICADORPRODUCCION = 2;
const RANALISTAMATERIALES = 3;
const COMPRAS_JEFE_DEPARTAMENTO = 50;
const COMPRAS_SOLICITANTE  = 51;
const COMPRAS_COMPRADOR = 52;
const COMPRAS_GERENTE = 53;
const COMPRAS_ADMINISTRADOR = 54;
const COMPRAS_DIRECTOR = 55;
const COMPRAS_DIRECTOR_CORPORATIVO = 56;
const COMPRAS_CONTADOR = 57;
const COMPRAS_TESORERO = 58;

const STATUS = array('Completo', 'Aprobado', 'Cancelado', 'Reembolsado', 'Pendiente', 'Entregado');

// Configuración de subida de archivos
const MAX_FILE_SIZE_MB = 5; // Tamaño máximo en MB antes de comprimir en ZIP
const MAX_FILE_SIZE_BYTES = 20 * 1024 * 1024; // Tamaño máximo en bytes (20 MB)
?>
