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


    public function inRoles($roles):bool
    {
        $user_roles = $_SESSION['roles'] ?? [];

        if (in_array('Full', $user_roles))
            return true;

        foreach ($roles as $role) {
            if (in_array($role, $user_roles))
                return true;
        }

        return false;
    }

    public function PathAvailableWithoutSignIn($uri):string
    {

        $status = $_SESSION['status'] ?? -1;

        foreach ($this->routesAvailableWithoutSignIn() as $uriPattern => $params) {

            if ($status < ($params['status'] ?? -1)) continue;

            $internal = $params['internal'] ?? false;
            if ($uri === $uriPattern
                || ($internal && strpos($uri, $uriPattern."\\")===0))
            {
                if (isset($params['roles'])){
                    if (!$this->inRoles($params['roles']))
                    {
                        continue;
                    }
                }

                return $params['path'] ?? $uri;
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

            if (isset($_SESSION["is_console"]) && $_SESSION["is_console"])
            {
                echo "\e[31m", "resource is unavailable\n", "\e[0m";

            }else{

                header('Location: '.HOST.'/signin');
            }
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
