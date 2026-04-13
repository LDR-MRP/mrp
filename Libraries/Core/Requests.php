<?php

abstract class Requests {
    protected $errors = [];
    protected $data = [];

    public function __construct() {
        $this->data = $this->parsePayload();
    }

    abstract public function rules(): void;

    private function parsePayload(): array
    {
        $payload = [];

        // 1. Capturar Query Params (?key=value)
        if (!empty($_GET)) {
            $payload = array_merge($payload, $_GET);
        }

        // 2. Capturar Form-Data o x-www-form-urlencoded ($_POST)
        if (!empty($_POST)) {
            $payload = array_merge($payload, $_POST);
        }

        // 3. Capturar JSON Body (Raw)
        $json = file_get_contents('php://input');
        if (!empty($json)) {
            $decoded = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $payload = array_merge($payload, $decoded);
            }
        }

        unset($payload['url']);

        return $payload;
    }

    public function validate(): bool {
        $this->rules();
        if (!empty($this->errors)) {
            throw new InvalidArgumentException(json_encode([
                'status' => false,
                'errors' => $this->errors
            ]), 422);
        }
        return true;
    }

    protected function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function files(): array
    {
        return $_FILES;
    }
}