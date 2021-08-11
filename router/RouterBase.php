<?php

namespace sketch\router;

use sketch\SK;
use sketch\CommandInterface;

class RouterBase implements CommandInterface
{

    public function routesAvailableWithoutSignIn(){
        return [
            'signup' => [
                'path' => 'signup',
                'status' => -1
            ],
            'signin' => [
                'path' => 'signin',
                'status' => -1
            ],
        ];
    }

    public function routes()
    {
        return [
            '([a-z]+)/([a-z]+)' => '$1/$2',
            '([a-z]+)' => '$1',
            '' => 'home/index',
        ];
    }

    public function getUri()
    {
        if (!empty($_SERVER['REQUEST_URI'])){
            return trim($_SERVER['REQUEST_URI'],'/');
        }
        return '';
    }

    public function PathAvailableWithoutSignIn($uri){

        $status = $_SESSION['status'];
        foreach ($this->routesAvailableWithoutSignIn() as $uriPattern => $path) {
            if ($status < $path['status']) continue;

            if ($uri === $uriPattern) {
                return $path['path'];
            }
            if (strpos($uri, $uriPattern)===0){
                return $uri;
            }
        }
        return "";
    }

    public function run($signParams=null)
    {
        if (!isset($_SESSION['status'])) $_SESSION['status']=-1;

        $uri = $this->getUri();

        $AvailablePath = $this->PathAvailableWithoutSignIn($uri);

        if ( $uri!=='signin' && $AvailablePath===""){
            header('Location: '.HOST.'/signin');
            return "";
        }

        $uri = $AvailablePath;


        $controller_path = SK::$controllers_path;
        foreach ($this->routes() as $uriPattern => $path) {
            if (preg_match("~$uriPattern~", $uri)) {

                $internalRoute = preg_replace("~$uriPattern~", $path, $uri);
                $parameters = explode('/', $internalRoute);
                $controllerName = ucfirst(array_shift($parameters)).'Controller';

                $controllerFile = CONTROLLER ."/". $controller_path ."/". $controllerName . '.php';
                if (! file_exists($controllerFile)) {
                    break;
                }

                $actionName = ucfirst(array_shift($parameters));
                if ($actionName === '') {
                    $actionName='index';
                }
                $actionName = 'action'.$actionName;

                include_once($controllerFile);

                $className = "controller\\".$controller_path.'\\'.$controllerName;
                $controllerObject = new $className;

                $result = call_user_func_array(array($controllerObject, $actionName), $parameters);

                if ($result === null) {
                    break;
                }

                echo $result;

                break;
            }
        }
        return "";
    }

}
