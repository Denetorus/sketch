<?php

namespace router;

use sketch\router\RouterBase;

class RouterMain extends RouterBase
{

    public function routesAvailableWithoutSignIn(){
        return [

            '' => [
                'path' => 'home/index',
                'status' => -1
            ],
            'index' => [
                'path' => 'home/index',
                'status' => -1
            ],

        ];
    }

}
