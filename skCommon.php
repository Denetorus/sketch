<?php

define('ROOT', dirname(__FILE__));
define('VIEW', ROOT.'/view');
define('MODEL', ROOT.'/model');
define('CONTROLLER', ROOT.'/controller');
define('VENDOR', ROOT.'/vendor');
define('SIGN', ROOT.'/sign');
define('SKETCH', ROOT.'/vendor/sketch');
define('SKETCH_EXT', ROOT.'/vendor/sketchExt');

define('OJApi', 'https://admin.1-joule.com/rest/');

require_once(VENDOR.'/AutoLoad.php');

header('Content-type: text/html');
header('Access-Control-Allow-Origin: *');

$parse_url = parse_url($_SERVER['REQUEST_URI']);
$_SERVER['REQUEST_URI'] = $parse_url["path"];
if (isset($parse_url['query'])){
    parse_str($parse_url['query'], $_GET);
}

//$NewURI = strstr($_SERVER['REQUEST_URI'], '?', true);
//if ($NewURI !== false) {
//    $_SERVER['REQUEST_URI'] = $NewURI;
//}
