<?php

class ExceptionDatabaseConnectParamMissing extends Exception
{

    /**
     * @var string
     */
    public $param_name;

    public function __construct(string $param_name)
    {
        $this->param_name = $param_name;
        parent::__construct("DB connect parameter '$param_name' is missing");
    }

}