<?php
namespace Controllers\Api\V1;

class CurrencyController
{
    use \ApiResponser;

    protected \CurrencyService $currencyService;

    public function __construct()
    {
        $this->currencyService = new \CurrencyService;
    }

    public function index()
    {
        return $this->apiResponse($this->currencyService->getAll());
    }
}