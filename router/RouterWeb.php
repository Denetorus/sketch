<?php

namespace sketch\router;

class RouterWeb extends RouterBase
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

        if ($uri !== $this->settings['sign_in_path']){
            header('Location: '.HOST.'/'.$this->settings['sign_in_path']);
            return '';
        }

        return $this->settings['error_path'];

    }

}