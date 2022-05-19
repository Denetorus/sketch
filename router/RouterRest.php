<?php

namespace sketch\router;

use sketch\SK;


class RouterRest extends RouterBase
{

    public function run():void
    {
        $uri = $this->getUri();

        $uri = $this->uriTransform($uri);

        if ( $uri ==='' )
            return;

        $controller_path = SK::$controllers_path;
        foreach ($this->routesMasks() as $uriPattern => $path) {

            if (!preg_match("~$uriPattern~", $uri))
                continue;

            $internalRoute = preg_replace("~$uriPattern~", $path, $uri);
            $parameters = explode('/', $internalRoute);
            $controllerName = ucfirst(array_shift($parameters)).'Controller';

            $controllerFile = ROOT.'/controller/'. $controller_path ."/". $controllerName . '.php';
            if (! file_exists($controllerFile))
                break;

            include_once($controllerFile);

            $actionName = "action".$_SERVER["REQUEST_METHOD"];

            $className = "controller\\".$controller_path.'\\'.$controllerName;
            $controllerObject = new $className;

            $result = call_user_func_array(array($controllerObject, $actionName), $parameters);

            if ($result !== null)
                echo json_encode($result);

            break;

        }

    }
}