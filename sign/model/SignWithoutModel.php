<?php

namespace sketch\sign\model;

use sketch\sign\SignModelInterface;

class SignWithoutModel implements SignModelInterface
{

    private $id = 1;
    private $login = 'guest';
    private $status = 1;

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
            'status' => $this->status
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
    }

}
