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
                $user = $this->vendorModel->findByEmail($strUsuario);

                if (!$user) {
                    return \ServiceResponse::error("El usuario no se encuentra registrado.", 401);
                }

                if (!password_verify($rawPassword, $user['password'])) {
                    return \ServiceResponse::error("La contraseña ingresada es incorrecta.", 401);
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
                        'vendor_id' => $user['proveedor_id'],
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
                $user = $userModel->findByEmail($strUsuario);

                if (!$user) {
                    return \ServiceResponse::error("El usuario no se encuentra registrado.", 401);
                }

                $strPasswordHash = hash("SHA256", $rawPassword);

                if (strtolower($user['password']) !== strtolower($strPasswordHash)) {
                    return \ServiceResponse::error("La contraseña ingresada es incorrecta.", 401);
                }

                if ($user['status'] != 1) {
                    return \ServiceResponse::error("El usuario se encuentra inactivo. Contacte al administrador.", 403);
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

            // --- INICIO AGREGADO: LIMPIEZA DE BLOQUEO SSO ---
            // Si el usuario logró autenticarse manualmente, eliminamos cualquier 
            // restricción de "Forced Logout" para habilitar el SSO en el futuro.
            if (isset($_COOKIE['mrp_forced_logout'])) {
                setcookie('mrp_forced_logout', '', time() - 3600, '/', COOKIE_DOMAIN);
            }
            // --- FIN AGREGADO ---

            // 4. Firmar Token (Asegúrate de que JWT_SECRET sea robusto)
            $jwt = JWT::encode($tokenPayload, JWT_SECRET, 'HS256');

            // Asignamos el nombre de la cookie dinámicamente según el canal de login
            $cookieName = ($loginType === 'VENDOR') ? 'srm_token' : 'mrp_token';

            // El navegador recibirá esto y guardará la cookie por ti.
            setcookie($cookieName, $jwt, [
                'expires'  => time() + 36000,
                'path'     => '/',
                'domain'   => COOKIE_DOMAIN, // .ldrhumanresources.local o .com
                'secure'   => COOKIE_SECURE,
                'httponly' => false, // Para que tu JS de permisos pueda decodificarlo
                'samesite' => 'Lax'
            ]);

            return \ServiceResponse::success([
                'access_token' => $jwt,
                'cookie_name'  => $cookieName,
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

     /**
     * FLUJO C: SSO TOKEN EXCHANGE (RRHH -> MRP)
     * Implementa Just-In-Time Provisioning para usuarios validados por el sistema ajeno.
     * @param array $data Datos decodificados del JWT de RRHH
     */
    public function authenticateViaSso(array $data): \ServiceResponse 
    {
        $userModel = new UsuariosModel();
        
        try {
            $email = strtolower(trim($data['correo'] ?? ''));
            if (empty($email)) throw new \Exception("Identidad incompleta.", 400);

            $this->db->beginTransaction();

            // 1. Localizar al usuario por el Anchor (Email)
            $userBase = $userModel->findByEmail($email);
            $userId = null;

            if (!$userBase) {
                // 2. JIT Provisioning (Solo si no existe)
                $userId = $this->provisionUserFromSso($data, $userModel);
                $this->logMessage("SSO: Usuario provicionado: {$email}", \LogLevel::INFO);
            } else {
                // 3. Ya existe, tomamos su ID local
                $userId = (int)$userBase['idusuario'];
            }

            // ---------------------------------------------------------
            // AQUÍ ESTÁ EL CAMBIO CLAVE:
            // ---------------------------------------------------------
            // Fuera del IF, recuperamos el perfil ENRIQUECIDO (con JOINs) 
            // para TODOS los casos (nuevo o existente).
            $userFull = $userModel->loginUserById($userId);

            if (!$userFull) {
                throw new \Exception("No se pudo recuperar el perfil operativo en MRP.");
            }

            // 4. Validar Estatus (Ahora sobre el perfil completo)
            if ((int)$userFull['status'] !== 1) {
                return \ServiceResponse::error("Acceso denegado: Usuario inactivo.", 403);
            }

            // 5. Preparar Payload con datos enriquecidos (rol_nombre, planta_nombre, etc.)
            $now = time();
            $tokenPayload = [
                'iat'  => $now,
                'exp'  => $now + (60 * 60 * 10),
                'data' => [
                    'id'       => $userFull['idusuario'],
                    'nombre'   => $userFull['nombres'] . ' ' . $userFull['apellidos'],
                    'rolid'    => $userFull['rolid'],
                    'plantaid' => $userFull['plantaid'],
                    'rol'      => $userFull['rol_nombre'], // Viene del JOIN
                    'planta'   => $userFull['planta_nombre'], // Viene del JOIN
                    'avatar'   => $userFull['avatar_file'],
                    'is_vendor'=> false,
                    'auth_type'=> 'SSO_RRHH'
                ]
            ];

            $userModel->registrarAcceso($userFull['idusuario'], 'SSO Exchange (RRHH)', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
            $this->db->commit();

            // --- INICIO AGREGADO: LIMPIEZA DE BLOQUEO SSO ---
            // Si el intercambio es exitoso (porque el usuario activó el SSO manualmente), 
            // eliminamos la cookie de bloqueo.
            if (isset($_COOKIE['mrp_forced_logout'])) {
                setcookie('mrp_forced_logout', '', time() - 3600, '/', COOKIE_DOMAIN);
            }
            // --- FIN AGREGADO ---

            $jwt = \Firebase\JWT\JWT::encode($tokenPayload, JWT_SECRET, 'HS256');

            return \ServiceResponse::success([
                'access_token' => $jwt,
                'user'         => $tokenPayload['data']
            ], "Sincronización SSO exitosa.");

        } catch (\PDOException $p) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logMessage($p, \LogLevel::CRITICAL, [
                'action' => 'authenticateViaSso',
                'id_user' => $email
            ]);
            return ServiceResponse::error(message: "Ocurrió un error de integridad en la base de datos.");
            
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->logMessage($e, \LogLevel::ERROR, ['context' => 'SSO_EXCHANGE']);
            return \ServiceResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Crea un usuario local basado en el contexto de identidad externo.
     */
    private function provisionUserFromSso(array $data, UsuariosModel $model): int 
    {
        // Mapeo de Sede (RRHH) a Planta (MRP)
        $plantaId = match($data['nombre_sede'] ?? '') {
            'CORPORATIVO' => 50,
            default       => 50
        };

        // Separación simple de nombre completo
        $parts = explode(' ', $data['nombre'], 2);
        
        return $model->insertUserFromSso([
            'nombres'     => $parts[0],
            'apellidos'   => $parts[1] ?? '',
            'email'       => strtolower($data['correo']),
            'rolid'       => 3, // Rol 'Empleado' por defecto
            'plantaid'    => $plantaId,
            'status'      => 1,
            'password'    => 'SSO_LDR_IDENTITY', // Placeholder de seguridad
            'avatar_file' => 'avatar_default.svg'
        ]);
    }
}