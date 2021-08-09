<?php

use sketch\SK;

if (!isset($_SERVER["USE_SESSIONS"])){
    $_SERVER["USE_SESSIONS"]=true;
}

define('HOST', 'https://'.$_SERVER['HTTP_HOST']);

include 'skCommon.php';

if ($_SERVER["USE_SESSIONS"]) {
    session_start();
}

SK::run(ROOT."/config.json");
