<?php

namespace sketch\router;

use sketch\SK;

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


}