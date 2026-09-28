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

// Submódulos Capacidad
const MCESTACIONESTRABAJO = 27;
const MCLINEAS = 28;
const MCPLANTAS = 29;

// Submódulos Proveedores
const MPPROVEEDORES = 35;

// Submódulos Clientes
const MCCLIENTES = 39;
const MCMARCAS = 40;
const MCDEPARTAMENTOS = 41;
const MCLI_GRUPOS = 42;
const MCLI_PUESTOS = 43;
const MCLI_CONTACTOS = 44;
const MCLI_REGIONALES = 45;
const MCLI_TIPOS_CLIENTES = 46;

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
