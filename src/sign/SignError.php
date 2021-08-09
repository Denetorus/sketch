<?php

namespace sign;

use sketch\sign\model\SignErrorModel;
use sketch\sign\SignBase;

class SignError extends SignBase
{
    public function options()
    {
        return [
            'class' => new SignErrorModel(),
        ];

    }

}