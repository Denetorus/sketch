<?php

namespace sketch\sign\model;

use sketch\sign\SignModelInterface;

class SignWithoutModel implements SignModelInterface
{

    private $id = 1;
    private $login = 'guest';
    private $status = 1;

    public function signIn(){
    }

    public function signedIn(){
        return true;
    }
    public function signedInfo()
    {
        return [
            'id' => $this->id,
            'login' => $this->login,
            'status' => $this->status
        ];
    }

    public function clear(){
        $this->id = null;
        $this->login = '';
        $this->status = -1;

    }

}
