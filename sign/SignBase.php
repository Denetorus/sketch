<?php

namespace sketch\sign;

use sketch\exceptions\ExceptionSignOptionsNotCorrect;

abstract class SignBase
{
    /**
     * @var array
     */
    public $signedResult = [];

    /**
     * @return array
     */
    abstract public function options():array;

    /**
     * @throws ExceptionSignOptionsNotCorrect
     */
    public function signOff():void
    {

        $signOptions = $this->options();

        if (empty($signOptions))
            throw new ExceptionSignOptionsNotCorrect("not filled");

        if (isset($signOptions['class']))
            throw new ExceptionSignOptionsNotCorrect('not content parameter class');

        $SM = new $signOptions['class'];
        $SM->clear();

        $this->signedResult = $SM->signedIn() ? $SM->signedInfo() : null;
        $_SESSION['signed_data'] = $this->signedResult;

    }

    /**
     * @return array
     * @throws ExceptionSignOptionsNotCorrect
     */
    public function run():array
    {
        $signOptions = $this->options();

        if (empty($signOptions))
            throw new ExceptionSignOptionsNotCorrect("not filled");

        if (isset($signOptions['class']))
            throw new ExceptionSignOptionsNotCorrect('not content parameter class');


        $SM = $signOptions['class'];

        unset($signOptions['class']);
        foreach ($signOptions as $key => $value) {
            $SM->{$key} = $value;
        }

        $SM->signIn();

        return $SM->signedInfo();

    }

}