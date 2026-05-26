<?php
//namespace Services;

use Firebase\JWT\JWT;
use Requests\Auth\LoginRequest;

class AuthService{

    use Loggable;

    protected PrvUsuariosModel $vendorModel;

    protected object $db;

    public function __construct()
    {
        $this->vendorModel = new PrvUsuariosModel;
        $this->db = $this->vendorModel->getConexion();
    }
    
    public function authenticate(): \ServiceResponse {
        $request = new LoginRequest();

        try {
            // 1. Validación estricta del Request (Evita Mass Assignment y Null Payloads)
            $request->validate();
            $payload = $request->all();

            $strUsuario  = strtolower(trim($payload['txtEmail'] ?? ''));
            $rawPassword = $payload['txtPassword'] ?? '';
            // Discriminador clave. Si no viene, asumimos empleado interno.
            $loginType   = strtoupper(trim($payload['login_type'] ?? 'INTERNAL')); 

            // Validar que el loginType no sea alterado por un atacante
            if (!in_array($loginType, ['INTERNAL', 'VENDOR'])) {
                throw new \InvalidArgumentException("Tipo de acceso no permitido.");
            }

            $now = time();
            $tokenPayload = [];

            // =================================================================
            // FLUJO A: LOGIN PROVEEDORES (SRM) - Cifrado Moderno
            // =================================================================
            if ($loginType === 'VENDOR') {
                // Buscamos solo por email para evitar Timing Attacks en la DB
                $user = $this->vendorModel->findByEmail($strUsuario);

                // Prevención de Enumeración de Usuarios y Timing Attacks
                // Si no existe, hasheamos un string dummy para tardar el mismo tiempo
                if (!$user) {
                    password_verify('dummy_string', '$2y$10$dummyhashdummyhashdummyhashdu');
                    return \ServiceResponse::error("El usuario o la contraseña es incorrecto.", 401);
                }

                // Verificación BCRYPT/ARGON2 (El estándar actual)
                if (!password_verify($rawPassword, $user['password'])) {
                    return \ServiceResponse::error("El usuario o la contraseña es incorrecto.", 401);
                }

                if ($user['estatus'] !== 'ACTIVE') {
                    return \ServiceResponse::error("Su cuenta de proveedor no está activa. Estatus: " . $user['estatus'], 403);
                }

                // Payload específico para Proveedor
                $tokenPayload = [
                    'iat'  => $now,
                    'exp'  => $now + (60 * 60 * 10), // 10 horas
                    'data' => [
                        'id'        => $user['id'],
                        'vendor_id' => $user['proveedor_id'], // CLAVE PARA PREVENIR IDOR EN EL FUTURO
                        'nombre'    => $user['nombre_contacto'],
                        'rol'       => 'VENDOR',
                        'is_vendor' => true
                    ]
                ];

                // Actualizar último acceso
                $this->vendorModel->updateLastLogin($user['id']);

            // =================================================================
            // FLUJO B: LOGIN INTERNO (ERP Legacy) - Cifrado SHA256
            // =================================================================
            } else {
                $userModel = new UsuariosModel();
                $strPasswordHash = hash("SHA256", $rawPassword);

                $user = $userModel->loginUser($strUsuario, $strPasswordHash);

                if (!$user) {
                    return \ServiceResponse::error("El usuario o la contraseña es incorrecto.", 401);
                }

                if ($user['status'] != 1) {
                    return \ServiceResponse::error("Usuario inactivo. Contacte al administrador.", 403);
                }

                // Auditoría interna
                $userModel->registrarAcceso(
                    $user['idusuario'], 
                    'Inicio de Sesión API', 
                    $_SERVER['REMOTE_ADDR'], 
                    $_SERVER['HTTP_USER_AGENT']
                );

                // Payload específico para Empleado
                $tokenPayload = [
                    'iat'  => $now,
                    'exp'  => $now + (60 * 60 * 10),
                    'data' => [
                        'id'       => $user['idusuario'],
                        'nombre'   => $user['nombres'] . ' ' . $user['apellidos'],
                        'rolid'    => $user['rolid'],
                        'plantaid' => $user['plantaid'],
                        'rol'      => $user['rol_nombre'],
                        'avatar'   => $user['avatar_file'],
                        'is_vendor'=> false
                    ]
                ];
            }

            // 4. Firmar Token (Asegúrate de que JWT_SECRET sea robusto)
            $jwt = JWT::encode($tokenPayload, JWT_SECRET, 'HS256');

            return \ServiceResponse::success([
                'access_token' => $jwt,
                'redirect_to'  => $loginType === 'VENDOR' ? '/srm/dashboard' : '/dashboard',
                'user'         => $tokenPayload['data']
            ], "Acceso autorizado.");

        } catch (\InvalidArgumentException $i) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ServiceResponse::validation(errors: $i->getMessage());
            
        } catch (\PDOException $p) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logMessage($p, \LogLevel::CRITICAL, [
                'action' => 'authenticate',
                'id_user' => $strUsuario
            ]);
            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos.");
            
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logMessage($e, \LogLevel::WARNING, [
                'action' => 'storeRequisition',
                'payload' => $payload ?? []
            ]);
            $code = $e->getCode() !== 0 ? $e->getCode() : 500;
            return ServiceResponse::error(message: $e->getMessage(), code: $code);
        }
    }
}