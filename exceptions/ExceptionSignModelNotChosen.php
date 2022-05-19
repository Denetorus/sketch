<?php

namespace sketch\exceptions;

use Exception;

class ExceptionSignModelNotChosen extends Exception
{

    public function __construct()
    {
        parent::__construct('Sign model not chosen in config file');
    }

}