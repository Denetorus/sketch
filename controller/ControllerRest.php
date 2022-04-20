<?php

namespace sketch\controller;


use sketch\rest\RequestResult;
use sketch\view\ViewBase;

abstract class ControllerRest
{

    public function allowMethods(): string
    {
        return "";
    }

    public function setHeaderAllowMethods(){
        header("Allow", $this->allowMethods());
    }

    public function actionGet()
    {
        $this->setHeaderAllowMethods();
        http_response_code(405);
        $result = new RequestResult();
        $result->addError(3, "GET", "Method GET Not Allowed");
        return $result;
    }

    public function actionPost()
    {
        $this->setHeaderAllowMethods();
        http_response_code(405);
        $result = new RequestResult();
        $result->addError(3, "POST", "Method POST Not Allowed");
        return $result;
    }

    public function actionPut()
    {
        $this->setHeaderAllowMethods();
        http_response_code(405);
        $result = new RequestResult();
        $result->addError(3, "PUT", "Method PUT Not Allowed");
        return $result;
    }

    public function actionDelete()
    {
        $this->setHeaderAllowMethods();
        http_response_code(405);
        $result = new RequestResult();
        $result->addError(3, "DELETE", "Method DELETE Not Allowed");
        return $result;
    }

    public function actionView()
    {
        $this->setHeaderAllowMethods();
        http_response_code(405);
        $result = new RequestResult();
        $result->addError(3, "VIEW", "Method VIEW Not Allowed");
        return $result;
    }

    public function actionCopy()
    {
        $this->setHeaderAllowMethods();
        http_response_code(405);
        $result = new RequestResult();
        $result->addError(3, "COPY", "Method COPY Not Allowed");
        return $result;
    }

    public function render($fileName, $params = [])
    {
        $fileName = VIEW.'/'.$fileName;

        if (is_file($fileName)){
            $view = new ViewBase();
            return $view->render($fileName, $params);
        } else {
            return "This site made with use SKETCH framework";
        }
    }


}