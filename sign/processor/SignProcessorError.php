<?php

namespace sketch\sign\processor;

class SignProcessorError extends SignProcessorBase
{

    /**
     * @return void
     */
    public function signIn():void
    {
        $this->clear();
    }

    public function signedIn():bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function fillByUserID():bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function fillByUserLogin():bool
    {
        return false;
    }
}
