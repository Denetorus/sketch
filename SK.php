<?php

namespace sketch;

use sketch\exceptions\ExceptionRouterModelNotChosen;
use sketch\exceptions\ExceptionSignModelNotChosen;
use sketch\exceptions\ExceptionSignOptionsNotCorrect;
use sketch\router\RouterBase;
use sketch\sign\SignBase;

class SK
{
    /**
     * @var array
     */
    private static $props = [];
    /**
     * @var array
     */
    private static $listCommands = [];
    /**
     * @var bool
     */
    private static $isRun = false;
    /**
     * @var array
     */
    public static $signInfo = [
        'id' => -1,
        'login' => '',
        'status' => -1
    ];
    /**
     * @var string
     */
    public static $controllers_path = "";
    /**
     * @var string[]
     */
    public static $settings = [
        'controllers_path' => 'web'
    ];

    /**
     * @param array $props
     * @return void
     */
    public static function setProps(array $props):void
    {
        foreach ($props as $key => $value) {
            self::addProp($key, $value);
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function addProp(string $key, $value):void
    {
        self::$props[$key] = $value;
    }

    /**
     * @return array
     */
    public static function getProps():array
    {
        return self::$props;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public static function getProp(string $key)
    {
        return self::$props[$key];
    }

    /**
     * @param array $settings
     * @return void
     */
    private static function setSettings(array $settings):void
    {
        foreach ($settings as $key => $value) {
            self::$settings[$key] = $value;
        }
    }

    /**
     * @param array $routers
     * @return void
     */
    private static function setRouters(array $routers):void
    {
        foreach ($routers as $router) {

            if (!isset($router['path']))
                continue;

            $path = "/{$router['path']}/";
            $len = strlen($path);

            if (substr($_SERVER['REQUEST_URI'],0,$len) !== $path)
                continue;

            $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'],$len-1);

            if (isset($router['props'])){
                self::setProps($router['props']);
                unset($router['props']);
            }

            self::setSettings($router);

            return;
        }

    }

    /**
     * @param string $fileName
     * @return bool
     */
    public static function loadConfig(string $fileName): bool
    {
        if (! is_file($fileName))
            return false;

        $ext = json_decode(file_get_contents($fileName), true);
        if (isset($ext['default'])) {
            self::setSettings($ext['default']);
        }
        if (isset($ext['routers'])) {
            self::setRouters($ext['routers']);
        }
        if (isset($ext['props'])) {
            self::setProps($ext['props']);
        }

        return true;
    }

    /**
     * @param CommandObj $obj
     * @return void
     */
    public static function add(CommandObj $obj):void
    {
        self::$listCommands[] = $obj;
    }


    /**
     * @return bool
     */
    public static function runNext():bool
    {
        if (count(self::$listCommands)===0){
            return false;
        }
        $obj = array_shift(self::$listCommands);
        $obj->run();
        return true;
    }

    /**
     * @param string $fileName
     * @return bool
     * @throws ExceptionRouterModelNotChosen
     * @throws ExceptionSignModelNotChosen
     * @throws ExceptionSignOptionsNotCorrect
     */
    public static function run(string $fileName=''):bool
    {
        if (self::$isRun)
            return false;

        self::$isRun = true;

        if ($fileName !== '')
            self::loadConfig($fileName);

        if (!isset(self::$settings['sign']))
            throw new ExceptionSignModelNotChosen;

        if (!isset(self::$settings['router']))
            throw new ExceptionRouterModelNotChosen;

        self::$controllers_path = self::$settings['controllers_path'];

        $sign = new self::$settings['sign'];
        self::$signInfo = $sign->run();

        $router = new self::$settings['router'];
        $router->controller_path = self::$settings['controllers_path'];
        $router->signInfo = self::$signInfo;
        $router->run();

        while (self::runNext()) {}

        self::$isRun = false;

        return true;
    }
}
