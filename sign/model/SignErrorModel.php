<?php

namespace sketch\sign\model;

use sketch\sign\SignModelInterface;

class SignErrorModel implements SignModelInterface
{

    private $id = -1;
    private $login = '';
    private $status = -1;

    /**
     * @return void
     */
    public function signIn():void
    {
    }

    public function signedIn():bool
    {
        return false;
    }
    public function signedInfo():array
    {
        return [
            'id' => $this->id,
            'login' => $this->login,
            'status' => $this->status
       ];
    }

    public function clear()
    {
        $this->id = -1;
        $this->login = '';
        $this->status = -1;
    }

}
