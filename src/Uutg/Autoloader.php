<?php

declare(strict_types=1);

namespace Uutg;

class Autoloader
{
    /**
     * @param $className
     * @return bool
     */
    public static function loader($className)
    {
        $filename = "src/" . str_replace("\\", '/', $className) . ".php";
        if (file_exists($filename)) {
            include($filename);
            if (class_exists($className)) {
                return true;
            }
        }
        return false;
    }
}
