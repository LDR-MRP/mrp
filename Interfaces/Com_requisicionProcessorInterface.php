<?php


interface Com_requisicionProcessorInterface {
    public function execute(Com_requisicionModel $model, int $requisitionId, string $status, int $userId);
    public function getLogAction();
}