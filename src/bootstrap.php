<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 'on');

require_once 'src/Uutg/Autoloader.php';

spl_autoload_register(\Uutg\Autoloader::class . '::loader');

$externalAppBootstrap = 'path//to/your/app/goes/here';

if (file_exists($externalAppBootstrap)) {
    require_once $externalAppBootstrap;
}
