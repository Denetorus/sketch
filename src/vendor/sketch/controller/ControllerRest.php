<?php

namespace sketch\controller;


use sketch\view\ViewBase;

abstract class ControllerRest
{

    public function allowMethods(){
        return "";
    }

    public function setHeaderAllowMethods(){
        header("Allow", $this->allowMethods());
        return "";
    }

    public function actionGet()
    {
        $this->setHeaderAllowMethods();
        http_response_code(405);
        return "Method GET Not Allowed";
    }

    public function actionPost()
    {
        $this->setHeaderAllowMethods();
        http_response_code(405);
        return "Method POST Not Allowed";
    }

    public function actionPut()
    {
        $this->setHeaderAllowMethods();
        http_response_code(405);
        return "Method PUT Not Allowed";
    }

    public function actionDelete()
    {
        $this->setHeaderAllowMethods();
        http_response_code(405);
        return "Method DELETE Not Allowed";
    }

    public function actionView()
    {
        $this->setHeaderAllowMethods();
        http_response_code(405);
        return "VIEW Method Not Allowed";
    }

    public function actionCopy()
    {
        $this->setHeaderAllowMethods();
        http_response_code(405);
        return "COPY Method Not Allowed";
    }

    public function getList(){
        http_response_code(406);
        return "Method GET without parameters for this resource is not available";
    }

    public function render($fileName, $params = [])
    {
        $fileName = VIEW.'/'.$fileName;

        if (is_file($fileName)){
            $view = new ViewBase();
            return $view->render($fileName, $params);
        } else {
            return "This site made with use SKETCH framework ";
        }
    }


}