<?php
declare(strict_types=1);

namespace Uutg;

class Generator
{
    /**
     * @var ReflectionFactory
     */
    private $reflectionFactory;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var string
     */
    private $className;
    /**
     * @var \ReflectionClass | null
     */
    private $reflection;
    /**
     * @var string[]
     */
    private $uses;
    /**
     * @var \ReflectionParameter[]
     */
    private $methodParams;
    /**
     * @var array
     */
    private $constructorParams;

    /**
     * Generator constructor.
     * @param ReflectionFactory $reflectionFactory
     * @param Config $config
     * @param string $className
     */
    public function __construct(ReflectionFactory $reflectionFactory, Config $config, string $className)
    {
        $this->reflectionFactory = $reflectionFactory;
        $this->config = $config;
        $this->className = $className;
    }

    /**
     * @return \ReflectionClass
     * @throws \ReflectionException
     */
    public function getReflection(): \ReflectionClass
    {
        if ($this->reflection === null) {
            $this->reflection = $this->reflectionFactory->create($this->className);
        }
        return $this->reflection;
    }

    /**
     * @return string
     */
    public function getHeader(): string
    {
        return $this->config->getHeader();
    }

    /**
     * @return string
     * @throws \ReflectionException
     */
    public function getNamespace(): string
    {
        $namespace = $this->getReflection()->getNamespaceName();
        if (!$namespace) {
            return '';
        }
        $parts = explode('\\', trim($namespace, '\\'));
        foreach ($this->config->getNamespaceStrategy() as $position => $value) {
            array_splice($parts, $position, 0, $value);
        }
        return implode('\\', $parts);
    }

    /**
     * @return string[]
     * @throws \ReflectionException
     */
    public function getUses(): ?array
    {
        if ($this->uses === null) {
            $this->uses = $this->config->getDefaultUses();
            $this->uses[] = trim($this->className, '\\');
            foreach ($this->getConstructParams() as $param) {
                if ($this->isParamMockable($param)) {
                    $this->uses[] = $param->getType()->getName();
                }
            }
            $this->uses = array_merge($this->uses, array_keys($this->getMethodMockableParameters()));
            $this->uses = array_unique($this->uses);
            sort($this->uses);
        }
        return $this->uses;
    }

    /**
     * @param string|null $className
     * @param bool $trim
     * @return string
     */
    public function getFullClassName(string $className = null, $trim = false): string
    {
        if ($className === null) {
            $className = $this->className;
        }
        $prefix = (!$trim) ? '\\' : '';
        return $prefix . trim($className, '\\');
    }

    /**
     * @param null|string $className
     * @return mixed
     */
    public function getClassName($className = null): string
    {
        if ($className === null) {
            $className = $this->className;
        }
        $parts = explode('\\', $className);
        return end($parts);
    }

    /**
     * @return string
     */
    public function getTestClassName(): string
    {
        return $this->getClassName() . 'Test';
    }

    /**
     * @param \ReflectionParameter $parameter
     * @return string
     * TODO: check for special classes
     */
    public function getMock(\ReflectionParameter $parameter): string
    {
        return '$this->createMock(' . $this->getClassName($parameter->getType()->getName()) . '::class);';
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    public function getMockables(): array
    {
        $mockables = array_filter(
            $this->getConstructParams(),
            function (\ReflectionParameter $param) {
                return $this->isParamMockable($param);
            }
        );
        $mockables = array_merge($mockables, array_values($this->getMethodMockableParameters()));
        return $mockables;
    }

    /**
     * @return array|null|\ReflectionParameter[]
     * @throws \ReflectionException
     */
    public function getMethodMockableParameters()
    {
        if ($this->methodParams === null) {
            $this->methodParams = [];
            foreach ($this->getReflection()->getMethods() as $method) {
                if (in_array($method->getName(), $this->config->getNonTestableMethods())) {
                    continue;
                }
                foreach ($method->getParameters() as $parameter) {
                    if (!$this->isParamMockable($parameter)) {
                        continue;
                    }
                    if (!$parameter->getType()) {
                        continue;
                    }
                    $class = $this->getFullClassName($parameter->getType()->getName(), true);
                    $this->methodParams[$class] = $parameter;
                }
            }
        }
        return $this->methodParams;
    }

    /**
     * @return array|\ReflectionParameter[]
     */
    public function getConstructParams(): array
    {
        if ($this->constructorParams === null) {
            $this->constructorParams = [];
            $constructor = $this->reflection->getConstructor();
            $this->constructorParams = ($constructor) ? $constructor->getParameters() : [];
        }
        return $this->constructorParams;
    }

    /**
     * @return \ReflectionMethod[]
     * @throws \ReflectionException
     */
    public function getTestableMethods(): array
    {
        $reflection = $this->getReflection();
        $methods = [];
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($this->canTestMethod($method)) {
                $methods[] = $method;
            }
        }
        return $methods;
    }

    /**
     * @param \ReflectionMethod $method
     * @return bool
     */
    public function canTestMethod(\ReflectionMethod $method): bool
    {
        if ($method->getDeclaringClass()->getName() !== $this->reflection->getName()) {
            return false;
        }
        if (in_array($method->getName(), $this->config->getNonTestableMethods())) {
            return false;
        }
        return true;
    }

    /**
     * @param \ReflectionParameter $parameter
     * @return bool
     */
    public function isParamMockable(\ReflectionParameter $parameter)
    {
        $type = $parameter->getType();
        if ($type === null) {
            return false;
        }
        return !isset($this->config->getNonMockable()[$parameter->getType()->getName()]);
    }

    /**
     * @param \ReflectionParameter $parameter
     * @return null|string
     */
    public function getConstructParamValue(\ReflectionParameter $parameter)
    {
        if ($this->isParamMockable($parameter)) {
            return '$this->' . $parameter->getName();
        }
        $nonMockable = $this->config->getNonMockable();
        $name = $parameter->getType() ? $parameter->getType()->getName() : '';
        return $nonMockable[$name] ?? '""';
    }
}
