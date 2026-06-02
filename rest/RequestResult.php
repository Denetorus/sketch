<?php

namespace sketch\rest;

class RequestResult
{
    public array $data = [];

    public int $status = 200;

    public array $errors = [];
    public bool $hasErrors = false;

    public function addError($status, $code, $description="", $message=""){

        $this->hasErrors = true;
        $this->status = $status;

        if ($description === ""){
            $description = $this->getErrorDescriptionByCode($code);
        }
        $this->errors[] = [
            "code" => $code,
            "description" => $description,
            "message" => $message
        ];

    }

    public function insertData($data){
        $this->data = $data;
    }

    public function addDataParam($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function addDataRow($row){
        $this->data[] = $row;
    }

    public function toJson(): array
    {
        $result = [
            'status' => $this->status,
            'hasErrors' => $this->hasErrors,
            'data' => $this->data,
        ];
        if($this->hasErrors){
            $result['errors'] = $this->errors;
        }
        return $result;
    }

    private function getErrorDescriptionByCode($code): string
    {
        switch ($code){
            case '0': return 'No error';
            case '1': return 'Data unavailable';
            case '2': return 'Invalid data';
            case '3': return 'Method unavailable';
        }
        return 'Unknown error';
    }
}

