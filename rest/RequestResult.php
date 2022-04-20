<?php

namespace sketch\rest;

class RequestResult
{
    public $data = [];
    public $error = [
        'code' => 0,
        'description' => "",
        'serverMessage' => []
    ];
    public $hasErrors = false;

    public function addError($code, $key="", $description=""){

        $this->hasErrors = true;

        if ($this->error['code']===0){
            $this->error['code'] = $code;
            $this->error['description'] = $this->getErrorDescriptionByCode($code);
        }
        if ($key!==""){
            $this->error['serverMessage'][] = [
                'key' => $key,
                'description' => $description
            ];
        }
    }

    public function insertData($data){
        $this->data = $data;
    }

    public function toJson(): array
    {
        $result = [
            'data' => $this->data,
            'hasErrors' => $this->hasErrors
        ];
        if($this->hasErrors){
            $result['error'] = $this->error;
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

