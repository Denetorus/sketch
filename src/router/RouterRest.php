<?php


namespace router;

class RouterRest extends \sketch\router\RouterRest
{

    public function routesAvailableWithoutSignIn(){
        return [

            'test' => [
                'path' => 'test',
                'status' => -1
            ],


        ];
    }
}