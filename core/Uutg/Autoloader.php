<?php

declare(strict_types=1);

namespace Uutg;

class Autoloader
{
    /**
     * @var array
     */
    public static $paths = [];
    /**
     * @param $className
     * @return bool
     */
    public static function loader($className): bool
    {
        foreach (self::$paths as $path) {
            $filename = $path . '/' . str_replace("\\", '/', $className) . ".php";
            if (file_exists($filename)) {
                include($filename);
                if (class_exists($className)) {
                    return true;
                }
            }
        }
        return false;
    }
}
