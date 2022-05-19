<?php

namespace sketch\exceptions;

use Exception;

class ExceptionSignOptionsNotCorrect extends Exception
{

    /**
     * @param string $add_text
     */
    public function __construct(string $add_text)
    {
        parent::__construct("Sign options in not correct: $add_text");
    }
}