<?php

abstract class Requests {
    protected $errors = [];
    protected $data = [];

    public function __construct(array $data) {
        $this->data = $data;
    }

    abstract public function rules();

    public function validate() {
        $this->rules();
        if (!empty($this->errors)) {
            throw new Exception(json_encode($this->errors), 422);
        }
        return true;
    }

    protected function addError(string $field, string $message) {
        $this->errors[$field] = $message;
    }

    public function all() {
        return $this->data;
    }
}