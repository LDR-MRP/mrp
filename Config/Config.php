<?php 
	const BASE_URL = "http://mrp.com";
	//Zona horaria
	date_default_timezone_set('America/Guatemala');

	//Datos de conexión a Base de Datos
	const DB_HOST = "localhost";
	const DB_NAME = "db_mrp";
	const DB_USER = "root";
	const DB_PASSWORD = "";
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
	const EMAIL_REMITENTE = "no-reply@abelosh.com";
	const NOMBRE_EMPESA = "LDR - SOLUTIONS";
	const WEB_EMPRESA = "www.abelosh.com";

	// const DESCRIPCION = "La mejor tienda en línea con artículos de moda.";
	const SHAREDHASH = "ldrsolutions";

	//Datos Empresa
	const DIRECCION = "Prol. P.º de la Reforma 1015-piso 24, Santa Fe, Contadero, Cuajimalpa de Morelos, 05348 Ciudad de México, CDMX";
	const TELEMPRESA = "+525572227706";
	const WHATSAPP = "+5572227706";
	const EMAIL_EMPRESA = "info@abelosh.com";
	// const EMAIL_PEDIDOS = "info@abelosh.com"; 
	// const EMAIL_SUSCRIPCION = "info@abelosh.com";
	const EMAIL_CONTACTO = "carlos.cruz@ldrsolutions.com.mx";

	// const CAT_SLIDER = "1,2,3";
	// const CAT_BANNER = "4,5,6";
	// const CAT_FOOTER = "1,2,3,4,5";

	//Datos para Encriptar / Desencriptar
	const KEY = 'carloscc';
	const METHODENCRIPT = "AES-128-ECB";

	//Envío
	// const COSTOENVIO = 5;

	//Módulos
	const MDASHBOARD = 1;
	const MUSUARIOS = 2;

	//Submodulos Planeación
	const MPBOM = 3;
	const MPCAPACIDAD = 4;
	const MPDEMANDA = 5;
	const MPORDENES = 6;

	//Submodulos Requerimientos
	const MRFORECAST = 7;
	const MRPROGRAMACIONSEMANAL = 8;

	//Submodulos Ordenes
	const MOBOM = 9;
	const MOLEADTIMES = 10;
	const MOMRPRUN = 11;

	//Submodulos Materiales
	const MMLIBERACION = 12;
	const MMSEGUIMIENTO = 13;
	const MMCIERRE = 14;

		//Submodulos Capacidad
	const MCCALENDARIOP = 15;
	const MCREQUERIMIENTOS = 16;
	const MCTRANSFERENCIAS = 17;


	//Páginas
	// const PINICIO = 1;
	// const PTIENDA = 2;
	// const PCARRITO = 3;
	// const PNOSOTROS = 4;
	// const PCONTACTO = 5;
	// const PPREGUNTAS = 6;
	// const PTERMINOS = 7;
	// const PSUCURSALES = 8;
	const PERROR = 9;

	//Roles
	const RADMINISTRADOR = 1;
	const RPLANIFICADORPRODUCCION = 2;
	const RANALISTAMATERIALES = 3;
	// const RANALISTAMATERIALES = 3;
	// const RANALISTAMATERIALES = 3;

	const STATUS = array('Completo','Aprobado','Cancelado','Reembolsado','Pendiente','Entregado');

	//Productos por página
	const CANTPORDHOME = 8;
	const PROPORPAGINA = 4;
	const PROCATEGORIA = 4;
	const PROBUSCAR = 4;

	//REDES SOCIALES
	// const FACEBOOK = "https://www.facebook.com/abelosh";
	// const INSTAGRAM = "https://www.instagram.com/febel24/";
	

 ?>