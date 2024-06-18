<?php

/**
 * Ultimate Unit Test Generator (Uutg)
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 *
 * @license   http://opensource.org/licenses/mit-license.php MIT License
 * @author    Marius Strajeru
 */

declare(strict_types=1);

namespace Umc\Uutg;

use ReflectionMethod;

class Method
{

    public function __construct(
        private string $name,
        private array $parameters,
        private ReflectionMethod $reflectionMethod
    ) {
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return bool
     */
    public function isStatic(): bool
    {
        return $this->reflectionMethod->isStatic();
    }

    /**
     * @return ReflectionMethod
     */
    public function getReflectionMethod(): ReflectionMethod
    {
        return $this->reflectionMethod;
    }
}
