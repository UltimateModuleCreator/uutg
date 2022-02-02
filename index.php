<?php

error_reporting(E_ALL);
ini_set("display_errors", "on");

require_once 'core/Uutg/Autoloader.php';

\Uutg\Autoloader::$paths = ['core', 'custom'];
spl_autoload_register(\Uutg\Autoloader::class . '::loader');

$isCli = php_sapi_name() === "cli";
$options = $isCli
    ? getopt("", ["class:", "profile::", "separator::"])
    : $_GET;
$options['cli'] = $isCli;

$profilePaths = [__DIR__ . '/core/profile/', __DIR__ . '/custom/profile/'];
$loader = new \Uutg\Profile\Loader($profilePaths);

$manager = new \Uutg\Di\Manager([get_class($loader) => $loader]);


/** @var \Uutg\App $app */
try {
    $app = $manager->get(
        \Uutg\App::class,
        [
            'renderer' => $manager->get(
                \Uutg\Renderer::class,
                ['templatePaths' => ['custom/templates', 'core/templates']]
            ),
            'options' => $options
        ]
    );
} catch (ReflectionException $e) {
    echo $e->getMessage();
}
echo $app->run();
