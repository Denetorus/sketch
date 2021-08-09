<?php

spl_autoload_register("AutoLoad");

/**
 * @throws Exception
 */
function AutoLoad($className): bool
{
    $path = str_replace('\\','/',$className);
    $dirs = [
        '',
        '/vendor/denetorus/',
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
        throw new Exception('There is no class file to download: ' . $path );
    }
    return true;
}

