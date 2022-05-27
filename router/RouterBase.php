<?php

namespace sketch\router;

class RouterBase
{

    /**
     * @var array
     */
    public $settings = [];

    /**
     * @var array
     */
    public $signInfo = [
        'id'=>-1,
        'status'=>-1,
        'login'=>''
    ];

    /**
     * @return array[]
     */
    public function routes():array
    {
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

    /**
     * @return string[]
     */
    public function routesMasks():array
    {
        return [
            '([a-z]+)/([a-z]+)' => '$1/$2',
            '([a-z]+)' => '$1',
            '' => 'home/index',
        ];
    }

    /**
     * @return string
     */
    public function getUri():string
    {
        if (empty($_SERVER['REQUEST_URI']))
            return '';

        return trim($_SERVER['REQUEST_URI'],'/');
    }

    /**
     * @param $roles
     * @return bool
     */
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

    /**
     * @param $uri
     * @return string
     */
    public function uriTransform($uri):string
    {

        foreach ($this->routes() as $uriPattern => $params) {

            if ($this->settings['use_status'] && $this->signInfo['status'] < ($params['status'] ?? -1))
                continue;


            $internal = $params['internal'] ?? false;

            if ($uri !== $uriPattern
                && (!$internal || strpos($uri, $uriPattern."/")===false))
                    continue;

            if ($this->settings['use_roles']){

                if (!isset($params['roles']))
                    continue;

                if (!$this->inRoles($params['roles']))
                    continue;

            }

            return $params['path'] ?? $uri;

        }

        return '';
    }

    /**
     * @param string $uri
     * @param string $transformed_uri
     * @return string
     */
    public function checkUri(string $uri, string $transformed_uri):string
    {

        if ( $transformed_uri!=='' )
            return $transformed_uri;

        return '';

    }

    /**
     * @param $result
     * @return void
     */
    public function render($result):void
    {
        echo $result;
    }

    /**
     * @return void
     */
    public function run():void
    {

        $uri = $this->getUri();
        $uri = $this->checkUri($uri, $this->uriTransform($uri));

        if ( $uri==='' )
            return;

        foreach ($this->routesMasks() as $uriPattern => $path) {

            if (!preg_match("~$uriPattern~", $uri))
                continue;

            $internalRoute = preg_replace("~$uriPattern~", $path, $uri);
            $parameters = explode('/', $internalRoute);
            $controllerName = ucfirst(array_shift($parameters)).'Controller';

            $controllerFile = ROOT."/controller/{$this->settings['controller_path']}/$controllerName.php";
            if (! file_exists($controllerFile))
                break;

            $actionName = ucfirst(array_shift($parameters));
            if ($actionName === '')
                $actionName='index';

            $actionName = 'action'.$actionName;

            include_once($controllerFile);

            $className = 'controller\\'.$this->settings['controller_path'].'\\'.$controllerName;
            $controllerObject = new $className;

            $result = call_user_func_array(array($controllerObject, $actionName), $parameters);

            if ($result !== null)
                $this->render($result);

            break;

        }

    }

}
