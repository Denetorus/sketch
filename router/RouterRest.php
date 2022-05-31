<?php

namespace sketch\router;

class RouterRest extends RouterBase
{

    /**
     * @param $result
     * @return void
     */
    public function render($result):void
    {
        echo json_encode($result);
    }

    public function getAction(array &$parameters):string
    {
        return "action".$_SERVER["REQUEST_METHOD"];
    }

}