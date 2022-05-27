<?php

namespace sketch\router;

class RouterConsole extends RouterBase
{

    /**
    * @param string $uri
    * @param string $transformed_uri
    * @return string
    */
    public function checkUri(string $uri, string $transformed_uri):string
    {

        if ( $transformed_uri!=='' )
            return $transformed_uri;

        echo "\e[31m", "resource is unavailable\n", "\e[0m";
        return '';

    }

}