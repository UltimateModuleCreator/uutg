<?php

declare(strict_types=1);

namespace Uutg;

class ReflectionFactory
{
    /**
     * @param string $className
     * @return \ReflectionClass
     * @throws \ReflectionException
     */
    public function create(string $className) : \ReflectionClass
    {
        return new \ReflectionClass($className);
    }
}
