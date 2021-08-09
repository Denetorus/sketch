<?php

namespace sketch\sign;

interface SignModelInterface
{
    public function signIn();
    public function signedIn();
    public function signedInfo();
    public function clear();

}
