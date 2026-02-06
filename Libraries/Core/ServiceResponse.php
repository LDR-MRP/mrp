<?php

class ServiceResponse {
    public bool $success;
    public $data;
    public string $message;
    public int $code;

    public function __construct(bool $success, $data, string $message, int $code) {
        $this->success = $success;
        $this->data = $data;
        $this->message = $message;
        $this->code = $code;
    }

    // Método estático para respuestas exitosas
    public static function success(mixed $data = null, string $message = "Operación exitosa", int $code = 200): self {
        return new self(
            true,
            $data,
            $message,
            $code
        );
    }

    /**
     * Respuesta para errores genéricos (500, 403, 404, etc.)
     * El mensaje de la excepción viaja en el parámetro 'message'.
     *
     * @param string $message Mensaje de error o excepción.
     * @param int $code Código HTTP.
     * @return self
     */
    public static function error(string $message = "Error interno", int $code = 500): self {
        return new self(
            success: false,
            data: null,
            message: $message,
            code: ($code >= 400 && $code <= 599) ? $code : 500
        );
    }
    
    /**
     * Respuesta específica para fallos de validación (422).
     * Los detalles de los errores viajan en el parámetro 'data'.
     *
     * @param mixed $errors Array o JSON con los errores por campo.
     * @return self
     */
    public static function validation(mixed $errors): self {
        return new self(
            success: false,
            data: is_string($errors) ? json_decode($errors) : $errors,
            message: "Errores de validación",
            code: 422
        );
    }
}