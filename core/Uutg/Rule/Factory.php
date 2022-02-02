<?php

declare(strict_types=1);

namespace Uutg\RUle;

use ReflectionException;
use Uutg\Di\Manager;
use Uutg\Exception\ConfigException;

class Factory
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param string $className
     * @param bool $newInstance
     * @return RuleInterface
     * @throws ReflectionException|ConfigException
     */
    public function create(string $className, bool $newInstance = false): RuleInterface
    {
        $instance = $newInstance ? $this->manager->create($className) : $this->manager->get($className);
        if (!$instance instanceof RuleInterface) {
            throw new ConfigException($className . " does not implement " . RuleInterface::class);
        }
        return $instance;
    }
}
