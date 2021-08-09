<?php

namespace controller\rest;

use sketch\controller\ControllerRest;


class TestController extends ControllerRest
{

    public function allowMethods()
    {
        return "GET";
    }

    public function actionGet()
    {
        return [ 'message' => 'rest test is execute'];


    }
}
