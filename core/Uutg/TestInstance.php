<?php

declare(strict_types=1);

namespace Uutg;

use ReflectionParameter;

class TestInstance
{
    public const HEADER = 'header';
    public const NAMESPACE = 'namespace';
    public const USES = 'uses';
    public const MOCKABLES = 'mockables';
    public const STRONG_MODE = 'strong_mode';
    public const CONSTRUCTOR_PARAMS = 'constructor_params';
    public const METHODS = 'methods';
    public const ADDITIONAL_DATA = 'additional_data';

    /**
     * @var string
     */
    private $fullClassName;
    /**
     * @var array
     */
    private $data;

    /**
     * @param string $fullClassName
     * @param array $data
     */
    public function __construct(string $fullClassName, array $data)
    {
        $this->fullClassName = $fullClassName;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getHeader(): string
    {
        return $this->data[self::HEADER] ?? '';
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->data[self::NAMESPACE] ?? '';
    }

    /**
     * @param bool $fqn
     * @return false|mixed|string
     */
    public function getClassName(bool $fqn = false)
    {
        if ($fqn) {
            return $this->fullClassName;
        }
        $parts = explode('\\', $this->fullClassName);
        return end($parts);
    }

    /**
     * @return array
     */
    public function getUses(): array
    {
        return $this->data[self::USES] ?? [];
    }

    /**
     * @return array
     */
    public function getMockables(): array
    {
        return $this->data[self::MOCKABLES] ?? [];
    }

    /**
     * @return bool
     */
    public function isStrongMode(): bool
    {
        return (bool)($this->data[self::STRONG_MODE] ?? false);
    }

    /**
     * @param string $class
     * @return string
     */
    public function getClassAlias(string $class): string
    {
        return $this->getUses()[$class]['alias'] ?? basename(str_replace('\\', '/', $class));
    }

    /**
     * @return array|ReflectionParameter[]
     */
    public function getConstructorParams(): array
    {
        return $this->data[self::CONSTRUCTOR_PARAMS] ?? [];
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->data[self::METHODS] ?? [];
    }

    /**
     * @return string
     */
    public function getTestInstanceVarName(): string
    {
        $name = lcfirst($this->getClassName());
        $index = 2;
        $checkName = $name;
        while (isset($this->mockables[$checkName])) {
            $parts = explode('\\', $this->getClassName(true));
            if (isset($parts[count($parts) - $index])) {
                $name .= $parts[count($parts) - $index];
                $checkName = $name;
            } else {
                $checkName = $name .= $index;
            }
            $index++;
        }
        return $checkName;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return mixed
     */
    public function getAdditionalData(): array
    {
        return $this->data[self::ADDITIONAL_DATA];
    }

    /**
     * @return bool
     */
    public function hasNonStaticMethods(): bool
    {
        return count(
            array_filter(
                $this->getMethods(),
                function ($method) {
                    return !$method['static'];
                }
            )
        ) > 0;
    }
}
