<?php

declare(strict_types=1);

namespace Uutg\Di;

use ReflectionClass;
use ReflectionException;

class Manager
{
    /**
     * @var array array
     */
    private $instances;

    /**
     * @param array $instances
     */
    public function __construct(array $instances)
    {
        $instances[$this->getHash(get_class($this))] = $this;
        $this->instances = $instances;
    }

    /**
     * @throws ReflectionException
     */
    public function create(string $class, array $args = []): object
    {

        $class = ltrim($class, '\\');
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        $arguments = [];
        if ($constructor) {
            $params = $reflection->getConstructor()->getParameters();
            foreach ($params as $param) {
                $type = (string)$param->getType();
                $name = $param->getName();
                if (isset($args[$name])) {
                    $arguments[] = $args[$name];
                } elseif (class_exists($type)) {
                    if (!isset($this->instances[$type])) {
                        $this->instances[$type] = $this->get($type);
                    }
                    $arguments[] = $this->instances[$type];
                } elseif ($param->isDefaultValueAvailable()) {
                    $arguments[] = $param->getDefaultValue();
                }
            }
        }
        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * @param string $class
     * @param array $args
     * @return object
     * @throws ReflectionException
     */
    public function get(string $class, array $args = []): object
    {
        $hash = $this->getHash($class, $args);
        if (!isset($this->instances[$hash])) {
            $this->instances[$hash] = $this->create($class, $args);
        }
        return $this->instances[$hash];
    }

    /**
     * @param string $className
     * @param array $args
     * @return string
     */
    private function getHash(string $className, array $args = []): string
    {
        return $className . (count($args) > 0 ? '-' . hash('sha256', json_encode($args))  : '' );
    }
}
