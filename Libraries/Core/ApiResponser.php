<?php
trait ApiResponser {
    public function apiResponse(ServiceResponse $response) {
        if ($response->success) {
            return $this->successResponse(
                $response->data, 
                $response->message, 
                $response->code
            );
        }
        
        return $this->errorResponse(
            $response->message, 
            $response->code,
            $response->data
        );
    }

    public function successResponse(mixed $data, string $message = "Operación exitosa", int $code = 200) {
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

    public function errorResponse(string $message, int $code = 500, mixed $data = null) {
        header('Content-Type: application/json');
        $response = [
            'status'  => 'error',
            'code'    => $code,
            'message' => $message,
        ];

        if($data) {
            $response['errors'] = $data;
        }

        http_response_code($code);
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}