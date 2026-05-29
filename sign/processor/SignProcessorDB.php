<?php

namespace sketch\sign\processor;

class SignProcessorDB extends SignProcessorBase
{

    /**
     * @var object|null
     */
    public ?object $user = null;

    /**
     * @return bool
     */
    public function fillByUserID(): bool
    {
        if($this->user !== null){
            $this->user->key = $this->user_id;
            $this->user->load();
            $this->user_data = $this->user->props;
        }
        return $this->fillByUserData();
    }

    public function fillByUserLogin(): bool
    {
        if($this->user !== null){
            $this->user->loadByLogin($this->user_login);
            $this->user_data = $this->user->props;
        }
        return $this->fillByUserData();
    }


}
