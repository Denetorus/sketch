<?php

namespace controller\console;

use sketch\SK;

class TestController
{
    public function actionIndex()
    {
        return "console test console is execute!";
    }

    public function actionProps(){
        var_dump(SK::getProps());
        return "";
    }
}