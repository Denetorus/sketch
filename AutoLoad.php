<?php

spl_autoload_register("AutoLoad");

function AutoLoad($className)
{
    $path = str_replace('\\','/',$className);
    $dirs = [
        '',
        '/vendor',
    ];

    $found = false;
    foreach ($dirs as $dir) {
        $fileName = ROOT . $dir. '/'. $path . '.php';
        if (is_file($fileName)) {
            require_once($fileName);
            $found = true;
            break;
        }
    }
    if (!$found) {
        throw new Exception('There is no class file to download_: ' . $path );
    }
    return true;
}

