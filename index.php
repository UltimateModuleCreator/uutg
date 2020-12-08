<?php

require_once 'src/bootstrap.php';

try {
    if (php_sapi_name() === "cli") {
        if (isset($argv[1])) {
            $className = $argv[1];
        } else {
            throw new \InvalidArgumentException(
                "Missing class name. Usage: `php index.php \"ClassName\Here\"`"
            );
        }
    } else {
        if (isset($_GET['class'])) {
            $className = $_GET['class'];
        } else {
            throw new \InvalidArgumentException(
                "Missing class name. Usage `index.php?class=ClassName\Here`"
            );
        }
    }
    $configData = require 'config/config.php';
    $config = new \Uutg\Config($configData);
    $renderer = new \Uutg\Renderer('templates');
    $generator = new \Uutg\Generator(
        new \Uutg\ReflectionFactory(),
        $config,
        $className
    );
    echo $renderer->render('test.phtml', ['generator' => $generator]);
} catch (\Exception $e) {
    echo implode("\n\n", [$e->getMessage(), $e->getTraceAsString()]);
}
