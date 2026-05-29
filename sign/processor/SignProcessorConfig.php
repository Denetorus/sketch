<?php

namespace sketch\sign\processor;

class SignProcessorConfig extends SignProcessorBase
{

    public array $users = [];

    /**
     * @return bool
     */
    public function fillByUserID():bool
    {
        $this->user_data = [];
        foreach( $this->users as $value) {
            if ($value['login'] === $this->user_login){
                $this->user_data = $value;
            }
        }
        return $this->fillByUserData();
    }

    /**
     * @return bool
     */
    public function fillByUserLogin():bool
    {
        $this->user_data = [];
        foreach( $this->users as $value) {
            if ($value['id'] === $this->user_id){
                $this->user_data = $value;
            }
        }
        return $this->fillByUserData();
    }
}
