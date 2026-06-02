<?php

namespace sketch\controller;

use sketch\rest\RequestResult;

abstract class ControllerRest
{

    public function allowMethods(): string
    {
        return "";
    }

    public function setHeaderAllowMethods(){
        header("Allow", $this->allowMethods());
    }

    public function actionGet(): RequestResult
    {
        $this->setHeaderAllowMethods();
        http_response_code(405);
        $result = new RequestResult();
        $result->addError(405, 3, "GET", "Method GET Not Allowed");
        return $result;
    }

    public function actionPost(): RequestResult
    {
        $this->setHeaderAllowMethods();
        http_response_code(405);
        $result = new RequestResult();
        $result->addError(405,3, "POST", "Method POST Not Allowed");
        return $result;
    }

    public function actionPut(): RequestResult
    {
        $this->setHeaderAllowMethods();
        http_response_code(405);
        $result = new RequestResult();
        $result->addError(405,3, "PUT", "Method PUT Not Allowed");
        return $result;
    }

    public function actionDelete(): RequestResult
    {
        $this->setHeaderAllowMethods();
        http_response_code(405);
        $result = new RequestResult();
        $result->addError(405, 3, "DELETE", "Method DELETE Not Allowed");
        return $result;
    }

    public function actionView(): RequestResult
    {
        $this->setHeaderAllowMethods();
        http_response_code(405);
        $result = new RequestResult();
        $result->addError(405,3, "VIEW", "Method VIEW Not Allowed");
        return $result;
    }

    public function actionCopy(): RequestResult
    {
        $this->setHeaderAllowMethods();
        http_response_code(405);
        $result = new RequestResult();
        $result->addError(405,3, "COPY", "Method COPY Not Allowed");
        return $result;
    }


}