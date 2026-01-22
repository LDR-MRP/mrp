<?php
trait ApiResponser {
    public function successResponse(array $data, string $message = "Operación exitosa", int $code = 200) {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode([
            'status'  => 'success',
            'code'    => $code,
            'message' => $message,
            'data'    => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function errorResponse(string $message, int $code = 500, mixed $errors = null) {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode([
            'status'  => 'error',
            'code'    => $code,
            'message' => $message,
            'errors'  => $errors,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}