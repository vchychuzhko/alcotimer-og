<?php

if (PHP_MAJOR_VERSION < 7 && PHP_MINOR_VERSION < 1) {
    echo 'PHP version of 7.1 or higher is required.';
    exit(1);
}

define('DS', DIRECTORY_SEPARATOR);
define('BP', str_replace(DS, '/', dirname(__DIR__)));
define('APP_DIR', BP . '/app/code');
require_once('functions.php');

/**
 * Function to load classes by provided namespace.
 * @param string $classNamespace
 */
function __autoload($classNamespace)
{
    $path = '';

    foreach (explode('\\', $classNamespace) as $pathItem) {
        $path .= '/' . $pathItem;
    }
    require_once(APP_DIR . $path . '.php');
}
