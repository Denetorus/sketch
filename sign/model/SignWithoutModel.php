<?php

namespace sketch\sign\model;

use sketch\sign\SignModelInterface;

class SignWithoutModel implements SignModelInterface
{

    /**
     * @var int
     */
    private $id = 1;
    /**
     * @var string
     */
    private $login = 'guest';
    /**
     * @var int
     */
    private $status = 10;
    /**
     * @var array
     */
    private $roles = ['Full'];

    /**
     * @return void
     */
    public function signIn():void
    {
    }

    /**
     * @return bool
     */
    public function signedIn():bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function signedInfo():array
    {
        return [
            'id' => $this->id,
            'login' => $this->login,
            'status' => $this->status,
            'roles' => $this->roles
        ];
    }

    /**
     * @return void
     */
    public function clear():void
    {
        $this->id = -1;
        $this->login = '';
        $this->status = -1;
        $this->roles = [];
    }

}
