<?php

namespace sketch\sign\processor;

interface SignProcessorInterface
{

    public function signIn();
    public function signedIn();
    public function signedInfo();
    public function clear();

}
