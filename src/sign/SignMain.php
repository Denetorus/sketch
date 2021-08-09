<?php

namespace sign;

use sketch\sign\model\SignWithoutModel;
use sketch\sign\SignBase;

class SignMain extends SignBase
{

    public function options()
    {
        return [
            'class' => new SignWithoutModel(),
        ];


    }


}
