<?php

namespace sketch\sign\model;

use sketch\sign\SignModelInterface;

class SignErrorModel implements SignModelInterface
{

    /**
     * @var int
     */
    private $id = -1;
    /**
     * @var string
     */
    private $login = '';
    /**
     * @var int
     */
    private $status = -1;
    /**
     * @var array
     */
    private $roles = [];

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
            'status' => $this->status,
            'roles' => $this->roles
       ];
    }

    public function clear()
    {
        $this->id = -1;
        $this->login = '';
        $this->status = -1;
        $this->roles = [];
    }

}
