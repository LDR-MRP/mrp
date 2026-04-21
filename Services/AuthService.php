<?php
//namespace Services;

use Firebase\JWT\JWT;
use Requests\Auth\LoginRequest;
use UsuariosModel;

class AuthService{
    
    public function authenticate(): \ServiceResponse {
        $request = new LoginRequest();

        try {
            $request->validate();
            $payload = $request->all();

            $userModel = new UsuariosModel();

            // 1. Aplicamos tu lógica de limpieza y cifrado SHA256
            $strUsuario  = strtolower(trim($payload['txtEmail']));
            $strPassword = hash("SHA256", $payload['txtPassword']);

            // 2. Consultar base de datos
            $user = $userModel->loginUser($strUsuario, $strPassword);

            if (!$user) {
                return \ServiceResponse::error("El usuario o la contraseña es incorrecto.", 401);
            }

            if ($user['status'] != 1) {
                return \ServiceResponse::error("Usuario inactivo. Contacte al administrador.", 403);
            }

            // 3. Registrar Acceso (Auditoría que ya tenías)
            $userModel->registrarAcceso(
                $user['idusuario'], 
                'Inicio de Sesión API', 
                $_SERVER['REMOTE_ADDR'], 
                $_SERVER['HTTP_USER_AGENT']
            );

            // 4. Generar Payload del JWT (Reemplaza a la $_SESSION)
            $now = time();
            $tokenPayload = [
                'iat'  => $now,
                'exp'  => $now + (60 * 60 * 10), // 10 horas de validez
                'data' => [
                    'id'     => $user['idusuario'],
                    'nombre' => $user['nombres'] . ' ' . $user['apellidos'],
                    'rolid'  => $user['rolid'],
                    'rol'    => $user['rol_nombre'],
                    'avatar' => $user['avatar_file']
                ]
            ];

            $jwt = JWT::encode($tokenPayload, JWT_SECRET, 'HS256');

            // 5. Retornar respuesta exitosa con el token
            return \ServiceResponse::success([
                'access_token' => $jwt,
                'user' => $tokenPayload['data']
            ], "Bienvenido al sistema.");

        } catch (\InvalidArgumentException $i) {
            return \ServiceResponse::validation($i->getMessage());
        } catch (\Exception $e) {
            return \ServiceResponse::error("Error crítico: " . $e->getMessage(), 500);
        }
    }
}