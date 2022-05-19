<?php

namespace sketch\exceptions;

use Exception;

class ExceptionRouterModelNotChosen extends Exception
{

    public function __construct()
    {
        parent::__construct('Router model not chosen in config file');
    }

}