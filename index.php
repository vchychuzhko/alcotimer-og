<?php

define('DS', DIRECTORY_SEPARATOR);
define('BP', __DIR__);
define('PUB_DIR', 'pub');
require_once(BP . DS . 'app' . DS . 'autoload.php');

$app = new \Awesome\Frontend\App();
$app->run();