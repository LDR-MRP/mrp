<?php
class Cli_clientes extends Controllers
{
	public function __construct()
	{
		parent::__construct();
		session_start();
		//session_regenerate_id(true);
		if (empty($_SESSION['login'])) {
			header('Location: ' . base_url() . '/login');
			die();
		}
		getPermisos(MCCLIENTES);
	}

	/*
	|--------------------------------------------------------------------------
	| FUNCIÓN PARA REDIRIGIR A LA VISTA PRINCIPAL INDEX.PHP INLCUYENDO EL ARCHIVO JS 
	|--------------------------------------------------------------------------
	*/

	public function Cli_clientes()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
		}
		$data['page_tag'] = "Clientes";
		$data['page_title'] = "Clientes";
		$data['page_functions_js'] = "/clientes/index.js";
		$this->views->getView($this, "index", $data);
	}

	/*
	|--------------------------------------------------------------------------
	| FUNCIÓN PARA OBTENER TODOS LOS CLIENTES
	|--------------------------------------------------------------------------
	*/

	public function getTodos()
	{
		header('Content-Type: application/json; charset=utf-8');

		try {

			$arrData = $this->model->selectTodos();

			if (!is_array($arrData)) {
				$arrData = [];
			}

			echo json_encode(
				$arrData,
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			);

		} catch (Throwable $error) {

			http_response_code(500);

			echo json_encode([
				'status' => false,
				'message' => 'Error al consultar los clientes.',
				'error' => $error->getMessage()
			], JSON_UNESCAPED_UNICODE);
		}

		exit;
	}

	/*
	|--------------------------------------------------------------------------
	| FUNCIÓN PARA OBTENER TODOS LOS DISTRIBUIDORES
	|--------------------------------------------------------------------------
	*/
	public function getDistribuidores()
	{
		header('Content-Type: application/json; charset=utf-8');

		try {

			$arrData = $this->model->selectDistribuidores();

			if (!is_array($arrData)) {
				$arrData = [];
			}

			echo json_encode(
				$arrData,
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			);

		} catch (Throwable $error) {

			http_response_code(500);

			echo json_encode([
				'status' => false,
				'message' => 'Error al consultar los clientes.',
				'error' => $error->getMessage()
			], JSON_UNESCAPED_UNICODE);
		}

		exit;
	}

	/*
	|--------------------------------------------------------------------------
	| FUNCIÓN PARA OBTENER TODOS LOS CLIENTES INTERNOS
	|--------------------------------------------------------------------------
	*/
	public function getInternos()
	{
		header('Content-Type: application/json; charset=utf-8');

		try {

			$arrData = $this->model->selectInternos();

			if (!is_array($arrData)) {
				$arrData = [];
			}

			echo json_encode(
				$arrData,
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			);

		} catch (Throwable $error) {

			http_response_code(500);

			echo json_encode([
				'status' => false,
				'message' => 'Error al consultar los clientes.',
				'error' => $error->getMessage()
			], JSON_UNESCAPED_UNICODE);
		}

		exit;
	}

	/*
	|--------------------------------------------------------------------------
	| FUNCIÓN PARA OBTENER TODOS LOS CLIENTES EXTERNOS
	|--------------------------------------------------------------------------
	*/
	public function getExternos()
	{
		header('Content-Type: application/json; charset=utf-8');

		try {

			$arrData = $this->model->selectExternos();

			if (!is_array($arrData)) {
				$arrData = [];
			}

			echo json_encode(
				$arrData,
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			);

		} catch (Throwable $error) {

			http_response_code(500);

			echo json_encode([
				'status' => false,
				'message' => 'Error al consultar los clientes.',
				'error' => $error->getMessage()
			], JSON_UNESCAPED_UNICODE);
		}

		exit;
	}

	/*
	|--------------------------------------------------------------------------
	| FUNCIÓN PARA OBTENER TODOS LOS CLIENTES GUBERNAMENTALES
	|--------------------------------------------------------------------------
	*/
	public function getGubernamentales()
	{
		header('Content-Type: application/json; charset=utf-8');

		try {

			$arrData = $this->model->selectGubernamentales();

			if (!is_array($arrData)) {
				$arrData = [];
			}

			echo json_encode(
				$arrData,
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			);

		} catch (Throwable $error) {

			http_response_code(500);

			echo json_encode([
				'status' => false,
				'message' => 'Error al consultar los clientes.',
				'error' => $error->getMessage()
			], JSON_UNESCAPED_UNICODE);
		}

		exit;
	}

	/*
	|--------------------------------------------------------------------------
	| FUNCIÓN PARA REDIRIGIR A LA VISTA DE CREAR NUEVO CLIENTE INCLUYENDO EL ARCHIVO JS
	|--------------------------------------------------------------------------
	*/
	public function create()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
		}
		$data['page_tag'] = "Clientes";
		$data['page_title'] = "Clientes";
		$data['page_name'] = "bom";
		$data['page_functions_js'] = "/clientes/create.js";
		$this->views->getView($this, "create", $data);
	}

	/*
|--------------------------------------------------------------------------
| FUNCIÓN PARA REDIRIGIR A LA VISTA DE ACCESOS A CLIENTES INCLUYENDO SU ARCHIVO JS
|--------------------------------------------------------------------------
*/
	public function accesos($idcliente)
	{

		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
		}
		$data['page_tag'] = "Clientes";
		$data['page_title'] = "Clientes";
		$data['page_functions_js'] = "/clientes/accesos.js";

		// Enviar el id del cliente a la vista
		$data['idcliente'] = $idcliente;
		$this->views->getView($this, "accesos", $data);
	}



	
	/*
|--------------------------------------------------------------------------
| FUNCIÓN PARA VER EL HISTORICO DE ACCESOS DE CLIENTES AL PORTAL DE DISTRIBUIDORES 
|--------------------------------------------------------------------------
*/
public function getAccesoCliente($idcliente)
{
    if (empty($_SESSION['permisosMod']['r'])) {
        $this->responseJson(false, 'No tiene permisos para consultar.');
    }

    $idcliente = intval($idcliente);

    if ($idcliente <= 0) {
        $this->responseJson(false, 'El cliente no es válido.');
    }

    $cliente = $this->model->selectClienteAcceso($idcliente);

    if (empty($cliente)) {
        $this->responseJson(false, 'No se encontró el cliente.');
    }

    if (empty($cliente['correo'])) {
        $this->responseJson(
            false,
            'El cliente no tiene un correo registrado.'
        );
    }

    $acceso = $this->model->selectUsuarioAccesoPorCliente($idcliente);

    $correo = strtolower(trim($cliente['correo']));

    $usuarioSugerido = explode('@', $correo)[0];

    $data = [
        'idcliente' => $idcliente,
        'tipo_cliente' => $cliente['tipo_cliente'],
        'nombre_cliente' => $cliente['nombre_comercial']
            ?: $cliente['razon_social'],

        'idusuario_acceso' => intval(
            $acceso['idusuario_acceso'] ?? 0
        ),

        'usuario_acceso' => $acceso['nombre_usuario']
            ?? $usuarioSugerido,

        'correo_acceso' => $correo,

        'liga_acceso' => $acceso['url_portal']
            ?? base_url() . '/orders/login',

        'doble_autenticacion' => intval(
            $acceso['doble_autenticacion'] ?? 0
        ),

        'requiere_cambio_password' => intval(
            $acceso['requiere_cambio_password'] ?? 1
        ),

        'fecha_cambio_password' =>
            $acceso['fecha_cambio_password'] ?? null,

        'ultimo_login' =>
            $acceso['ultimo_login'] ?? null,

        'ultimo_envio_accesos' =>
            $acceso['ultimo_envio_accesos'] ?? null,

        'estado_acceso' => intval(
            $acceso['estado'] ?? 0
        )
    ];

    $this->responseJson(
        true,
        'Información obtenida correctamente.',
        $data
    );
}

public function responseJson(
    bool $status,
    string $message,
    $data = null
) {
    header('Content-Type: application/json; charset=utf-8');

    $response = [
        'status' => $status,
        'message' => $message
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode(
        $response,
        JSON_UNESCAPED_UNICODE
    );

    die();
}



public function setAccesoCliente()
{
    if (empty($_SESSION['permisosMod']['w'])) {
        $this->responseJson(
            false,
            'No tiene permisos para guardar accesos.'
        );
    }

    $idcliente = intval($_POST['idcliente'] ?? 0);
    $idusuarioAcceso = intval(
        $_POST['idusuario_acceso'] ?? 0
    );

    $usuarioAcceso = trim(
        $_POST['usuario_acceso'] ?? ''
    );

    $correoAcceso = strtolower(trim(
        $_POST['correo_acceso'] ?? ''
    ));

    $passwordTemporal = trim(
        $_POST['password_temporal'] ?? ''
    );

    $ligaAcceso = trim(
        $_POST['liga_acceso'] ?? ''
    );

    $dobleAutenticacion = intval(
        $_POST['doble_autenticacion'] ?? 0
    );

    if (
        $idcliente <= 0
        || empty($usuarioAcceso)
        || empty($correoAcceso)
        || empty($passwordTemporal)
        || empty($ligaAcceso)
    ) {
        $this->responseJson(
            false,
            'Todos los campos son obligatorios.'
        );
    }

    if (!filter_var($correoAcceso, FILTER_VALIDATE_EMAIL)) {
        $this->responseJson(
            false,
            'El correo no tiene un formato válido.'
        );
    }

    if (!$this->validarPasswordTemporal($passwordTemporal)) {
        $this->responseJson(
            false,
            'La contraseña debe tener 15 caracteres, mayúscula, minúscula, número y símbolo.'
        );
    }

    $cliente = $this->model->selectClienteAcceso($idcliente);

    if (empty($cliente)) {
        $this->responseJson(
            false,
            'No se encontró el cliente.'
        );
    }

    /*
     * No confíes en el correo readonly del navegador.
     * Se vuelve a tomar desde la base de datos.
     */
    $correoAcceso = strtolower(trim($cliente['correo']));

    $passwordHash = password_hash(
        $passwordTemporal,
        PASSWORD_DEFAULT
    );

    $usuarioAdmin = intval(
        $_SESSION['idUser'] ?? 0
    );

    if ($idusuarioAcceso > 0) {
        $guardado = $this->model->updateUsuarioAcceso(
            $idusuarioAcceso,
            $idcliente,
            $usuarioAcceso,
            $correoAcceso,
            $passwordHash,
            $ligaAcceso,
            $dobleAutenticacion,
            $usuarioAdmin
        );
    } else {
        $guardado = $this->model->insertUsuarioAcceso(
            $idcliente,
            $usuarioAcceso,
            $cliente['nombre_comercial']
                ?: $cliente['razon_social'],
            '',
            $correoAcceso,
            $passwordHash,
            $cliente['telefono'] ?? '',
            $ligaAcceso,
            $dobleAutenticacion,
            $usuarioAdmin
        );

        $idusuarioAcceso = intval($guardado);
    }

    if (!$guardado) {
        $this->responseJson(
            false,
            'No fue posible guardar las credenciales.'
        );
    }

	$esNuevoAcceso = $idusuarioAcceso <= 0;

	$nombreCliente = !empty($cliente['nombre_comercial'])
    ? $cliente['nombre_comercial']
    : $cliente['razon_social'];

$datosCorreo = [
    /*
     * Datos utilizados por tu función de correo.
     * Ajusta estas claves si sendMailLocalCron usa otros nombres.
     */
    'email' => $correoAcceso,
    'nombre_destinatario' => $nombreCliente,
    'asunto' => $esNuevoAcceso
        ? 'Accesos al Portal de Pedidos'
        : 'Actualización de accesos al Portal de Pedidos',

    /*
     * Datos visuales de la plantilla.
     */
    'nombre_cliente' => $nombreCliente,
    'codigo_cliente' => $cliente['codigo_cliente'] ?? '',
    'usuario_acceso' => $correoAcceso,
    'password_temporal' => $passwordTemporal,
    'liga_acceso' => $ligaAcceso,
    'doble_autenticacion' => $dobleAutenticacion,
    'fecha_notificacion' => date('d/m/Y H:i'),
    'anio' => date('Y'),

    /*
     * Puedes cambiar esta liga por la ubicación real de tu logotipo.
     */
    'logo_url' => 'https://viaticos.ldrhumanresources.com/viaticos/Assets/images/Logotipo_Naranja.png'
];

$cc = 'carlos.cruz@ldrsolutions.com.mx';

$correoEnviado = sendMailLocalCron(
    $datosCorreo,
    'email_clientes_accesos',
    $cc
);

$this->model->insertEnvioAcceso(
    $idusuarioAcceso,
    $idcliente,
    $correoAcceso,
    $esNuevoAcceso
        ? 'CREDENCIALES'
        : 'REENVIO_CREDENCIALES',
    $datosCorreo['asunto'],
    $correoEnviado ? 'ENVIADO' : 'FALLIDO',
    $correoEnviado
        ? 'Credenciales enviadas correctamente.'
        : 'Las credenciales se guardaron, pero no fue posible enviar el correo.',
    $usuarioAdmin
);

    $this->model->insertLogAcceso(
        $idusuarioAcceso,
        $idcliente,
        'CREDENCIALES_GENERADAS',
        $correoEnviado ? 'EXITOSO' : 'FALLIDO',
        $correoAcceso,
        $this->getClientIp(),
        'Panel administrativo',
        'Escritorio',
        null,
        null,
        null,
        null,
        session_id(),
        $_SERVER['HTTP_USER_AGENT'] ?? null,
        $correoEnviado
            ? 'Se generó y envió una contraseña temporal.'
            : 'Se guardó la contraseña, pero el correo no pudo enviarse.'
    );

    if (!$correoEnviado) {
        $this->responseJson(
            false,
            'Las credenciales se guardaron, pero no fue posible enviar el correo.',
            [
                'idusuario_acceso' => $idusuarioAcceso
            ]
        );
    }

    $this->model->updateFechaEnvioAccesos(
        $idusuarioAcceso
    );

    $this->responseJson(
        true,
        'Las credenciales fueron guardadas y enviadas correctamente.',
        [
            'idusuario_acceso' => $idusuarioAcceso
        ]
    );
}



public function validarPasswordTemporal(string $password)
{
    if (strlen($password) !== 15) {
        return false;
    }

    return preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password)
        && preg_match('/[!@#$%&*+\-_?]/', $password);
}


public function getClientIp()
{
    return $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['REMOTE_ADDR']
        ?? '0.0.0.0';
}



public function getLogsAcceso($idcliente)
{
    if (empty($_SESSION['permisosMod']['r'])) {
        $this->responseJson(
            false,
            'No tiene permisos para consultar el histórico.'
        );
    }

    $idcliente = intval($idcliente);

    if ($idcliente <= 0) {
        $this->responseJson(
            false,
            'El cliente no es válido.'
        );
    }

    $logs = $this->model->selectLogsAcceso(
        $idcliente
    );

    $this->responseJson(
        true,
        'Histórico obtenido correctamente.',
        $logs
    );
}



public function cambiarPasswordPortal()
{
    if (empty($_SESSION['portal_idusuario_acceso'])) {
        $this->responseJson(
            false,
            'La sesión no es válida.'
        );
    }

    $idusuarioAcceso = intval(
        $_SESSION['portal_idusuario_acceso']
    );

    $passwordActual = trim(
        $_POST['password_actual'] ?? ''
    );

    $passwordNueva = trim(
        $_POST['password_nueva'] ?? ''
    );

    $usuario = $this->model->selectUsuarioAccesoPorId(
        $idusuarioAcceso
    );

    if (empty($usuario)) {
        $this->responseJson(
            false,
            'No se encontró el usuario.'
        );
    }

    if (!password_verify(
        $passwordActual,
        $usuario['password_hash']
    )) {
        $this->responseJson(
            false,
            'La contraseña actual no es correcta.'
        );
    }

    if (
        strlen($passwordNueva) < 10
        || !preg_match('/[A-Z]/', $passwordNueva)
        || !preg_match('/[a-z]/', $passwordNueva)
        || !preg_match('/[0-9]/', $passwordNueva)
        || !preg_match('/[^A-Za-z0-9]/', $passwordNueva)
    ) {
        $this->responseJson(
            false,
            'La nueva contraseña no cumple con los requisitos de seguridad.'
        );
    }

    $nuevoHash = password_hash(
        $passwordNueva,
        PASSWORD_DEFAULT
    );

    $actualizado = $this->model->updatePasswordDefinitiva(
        $idusuarioAcceso,
        $nuevoHash
    );

    if (!$actualizado) {
        $this->responseJson(
            false,
            'No fue posible cambiar la contraseña.'
        );
    }

    $this->model->insertLogAcceso(
        $idusuarioAcceso,
        intval($usuario['idcliente']),
        'PASSWORD_CAMBIADA',
        'EXITOSO',
        $usuario['correo'],
        $this->getClientIp(),
        null,
        null,
        null,
        null,
        null,
        null,
        session_id(),
        $_SERVER['HTTP_USER_AGENT'] ?? null,
        'El distribuidor cambió su contraseña temporal.'
    );

    $this->responseJson(
        true,
        'La contraseña fue actualizada correctamente.'
    );
}







	public function getSelectRegimenFiscal($tipoPersona = null)
	{
		$htmlOptions = '<option value="">--Seleccione--</option>';
		$arrData = $this->model->selectOptionRegimenFiscal($tipoPersona);

		foreach ($arrData as $row) {
			$htmlOptions .= '<option value="' . $row['id'] . '">' .
				$row['c_regimen_fiscal'] . ' - ' . $row['descripcion'] .
				'</option>';
		}

		echo $htmlOptions;
		die();
	}



	public function getSelectPaises()
	{
		$htmlOptions = '<option value="">--Seleccione--</option>';
		$arrData = $this->model->selectOptionPaises();
		if (count($arrData) > 0) {
			for ($i = 0; $i < count($arrData); $i++) {
				if ($arrData[$i]['estado'] == 2) {
					$htmlOptions .= '<option value="' . $arrData[$i]['id'] . '">' . $arrData[$i]['nombre'] . '</option>';
				}
			}
		}
		echo $htmlOptions;
		die();
	}

	public function getSelectEstados($pais_id)
	{
		$htmlOptions = '<option value="">--Seleccione estado--</option>';
		$arrData = $this->model->selectEstadosByPais(intval($pais_id));

		foreach ($arrData as $row) {
			$htmlOptions .= '<option value="' . $row['id'] . '">' . $row['nombre'] . '</option>';
		}

		echo $htmlOptions;
		die();
	}

	public function getSelectMunicipios($estado_id)
	{
		$htmlOptions = '<option value="">--Seleccione municipio--</option>';
		$arrData = $this->model->selectMunicipiosByEstado(intval($estado_id));

		foreach ($arrData as $row) {
			$htmlOptions .= '<option value="' . $row['id'] . '">' . $row['nombre'] . '</option>';
		}

		echo $htmlOptions;
		die();
	}

	public function getRegionByEstado($estado_id)
	{
		$estado_id = intval($estado_id);

		$arrData = $this->model->selectRegionByEstado($estado_id);

		if (!empty($arrData)) {
			echo json_encode([
				"status" => true,
				"data" => $arrData
			]);
		} else {
			echo json_encode([
				"status" => false
			]);
		}
		die();
	}
}
