<?php

namespace sketch\sign\model;

use sketch\sign\SignModelInterface;

class SignErrorModel implements SignModelInterface
{

    private $id = null;
    private $login = '';
    private $status = -1;

    public function signIn(){
    }

    public function signedIn(){
        return false;
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
