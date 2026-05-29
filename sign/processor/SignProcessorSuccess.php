<?php

namespace sketch\sign\processor;

class SignProcessorSuccess extends SignProcessorBase
{

    public array $user_data = [
        'id' => 1,
        'login' => 'Guest',
        'password_hash' => "",
        'status' => 10,
        'role' => [],
    ];

    /**
     * @return void
     */
    public function signIn():void
    {
        $this->fillByUserData();
    }

    public function signedIn():bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function fillByUserID():bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function fillByUserLogin():bool
    {
        return true;
    }

}
