<?php
//const BASE_URL = "http://mrp.com";
const BASE_URL ="http://pruebasmrp.ldrhumanresources.com";
//Zona horaria
date_default_timezone_set('America/Mexico_City');


//Datos de conexión a Base de Datos
// const DB_HOST = "localhost";
// const DB_NAME = "db_mrp";
// const DB_USER = "root";
// const DB_PASSWORD = "";
// const DB_CHARSET = "utf8";



const DB_HOST = "localhost";
const DB_NAME = "u546825723_dbmrp";
const DB_USER = "u546825723_mrpuser";
const DB_PASSWORD = "L=9xlH6~e";
const DB_CHARSET = "utf8";

//Configuración Email local
const ENVIRONMENT = 0;


const SPD = ".";
const SPM = ",";

//Simbolo de moneda
const SMONEY = "$";
const CURRENCY = "USD";

//Api PayPal
//SANDBOX PAYPAL
const URLPAYPAL = "https://api-m.sandbox.paypal.com";
const IDCLIENTE = "";
const SECRET = "";

//Datos envio de correo
const NOMBRE_REMITENTE = "LDR - SOLUTIONS";
const EMAIL_REMITENTE = "carlosbunti97@gmail.com";
const NOMBRE_EMPESA = "LDR - SOLUTIONS";
const WEB_EMPRESA = "https://www.ldrsolutions.mx/";


const SHAREDHASH = "ldrsolutions";

//Datos Empresa
const DIRECCION = "Prol. P.º de la Reforma 1015-piso 24, Santa Fe, Contadero, Cuajimalpa de Morelos, 05348 Ciudad de México, CDMX";

const WHATSAPP = "+5572227706";


//Datos para Encriptar / Desencriptar
const KEY = 'carloscc';
const METHODENCRIPT = "ACC-128-ECB";

//Envío
// const COSTOENVIO = 5;

//Módulos
const MDASHBOARD = 1;
const MUSUARIOS = 2;

//Submodulos Planeación
const MPBOMCOMPONENTES = 6;
const MPBOMCONTROL = 7;
const MPPRODUCTOSTERMINADOS = 8;
const MPPLANPRODUCCION = 9;
const MPORDENES = 10;

//Submodulos inventario
const MILPRODUCTO = 16;
const MIPRECIOS = 17;
const MIINVENTARIO = 18;
const MIALMACENES = 19;
const MIPRODUCTOSSUSTITUTOS = 20;
const MIESQUEMAIMPUESTOS = 21;
const MIMOVIMIENTOS = 22;

//Submodulos Capacidad

const MCESTACIONESTRABAJO = 27;
const MCLINEAS = 28;
const MCPLANTAS = 29;

//Submodulos Proveedores
const MPPROVEEDORES = 35;

//Submodulos Clientes
const MCCLIENTES = 39;


const PERROR = 9;

//Roles
const RADMINISTRADOR = 1;
const RPLANIFICADORPRODUCCION = 2;
const RANALISTAMATERIALES = 3;


const STATUS = array('Completo', 'Aprobado', 'Cancelado', 'Reembolsado', 'Pendiente', 'Entregado');

?>