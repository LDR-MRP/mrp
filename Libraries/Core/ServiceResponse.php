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
        return new self(true, $data, $message, $code);
    }

    // Método estático para respuestas con error
    public static function error(string $message = "Error interno", int $code = 500, mixed $data = null): self {
        return new self(false, $data, $message, $code);
    }
}