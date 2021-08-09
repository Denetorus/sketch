<?php

namespace sketch\sign;

use sketch\CommandInterface;
use sketch\CommandObj;
use sketch\SK;

class SignBase implements CommandInterface
{
    private $User = null;

    public function options()
    {
        return null;
    }

    public function getSignParams()
    {
        return $this->User;
    }

    public function signOff($params=[])
    {
        $SignOptions = $this->options();

        if ($SignOptions !== null && isset($SignOptions['class'])) {

            $SM = new $SignOptions['class'];
            unset($SignOptions['class']);
            foreach ($SignOptions as $key => $value) {
                $SM->{$key} = $value;
            }

            $SM->Clear();

        }

    }

    public function run($params=[])
    {
        $SignOptions = $this->options();

        if ($SignOptions !== null && isset($SignOptions['class'])) {

            $SM = new $SignOptions['class'];
            unset($SignOptions['class']);
            foreach ($SignOptions as $key => $value) {
                $SM->{$key} = $value;
            }

            $SM->signIn();
            $this->User = $SM->signedIn() ? $SM->signedInfo() : null;
            $_SESSION['data'] = $this->User;

        }

        SK::add(
            new CommandObj(
                $params['router'],
                $this->getSignParams()
            )
        );
    }
}