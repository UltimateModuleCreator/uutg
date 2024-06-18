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

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;

class Generator
{
    public const USES_PLACEHOLDER = '--USES--';
    public const CLASS_PREFIX = '##';
    public const CLASS_SUFFIX = '##';

    private ?ReflectionClass $reflection = null;
    private ?string $namespace = null;
    private ?array $parameters = null;
    private ?array $constructorParams = null;
    private ?array $methods = null;
    private ?bool $hasNonStaticMethods = null;
    private ?string $testInstanceVarName = null;
    private ?array $mockables = null;
    private ?bool $hasConstructor = null;
    private ?array $classMethods = null;
    public function __construct(private Config $config, private string $className)
    {
    }

    public function generate(): string
    {
        ob_start();
        include $this->config->getTemplate();
        $content = ob_get_contents();
        ob_end_clean();
        return $this->replaceClassNames($content);
    }

    private function getClassName(): string
    {
        return trim($this->className, '\\');
    }

    public function getClassShortName(): string
    {
        $parts = explode('\\', $this->getClassName());
        return end($parts);
    }

    /**
     * @throws ReflectionException
     */
    private function getReflection(): ReflectionClass
    {
        if ($this->reflection === null) {
            $this->reflection = new ReflectionClass($this->getClassName());
        }
        return $this->reflection;
    }

    /**
     * @throws ReflectionException
     */
    public function getNamespace(): string
    {
        if ($this->namespace === null) {
            $namespace = $this->getReflection()->getNamespaceName();
            if (!$namespace) {
                return $this->namespace = '';
            }
            $strategy = $this->config->getNamespaceStrategy();
            if (is_callable($strategy)) {
                return $this->namespace = $strategy($namespace);
            }
            $parts = explode('\\', trim($namespace, '\\'));
            foreach ($this->config->getNamespaceStrategy() as $position => $value) {
                array_splice($parts, $position, 0, $value);
            }
            $this->namespace = implode('\\', $parts);
        }
        return $this->namespace;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function renderClassName(string $className): string
    {
        return self::CLASS_PREFIX . '\\' . ltrim($className, '\\') . self::CLASS_SUFFIX;
    }

    /**
     * @return Parameter[]
     * @throws ReflectionException
     */
    public function getParameters(): array
    {
        if ($this->parameters === null) {
            $this->collectParams();
        }
        return $this->parameters;
    }

    /**
     * @throws ReflectionException
     */
    public function getConstructorParams(): array
    {
        if ($this->constructorParams === null) {
            $this->collectParams();
        }
        return $this->constructorParams;
    }

    /**
     * @throws ReflectionException
     */
    private function collectParams()
    {
        $this->parseConstructor();
        $this->parseMethods();
    }

    private function parseConstructor()
    {
        $constructor = $this->getReflection()->getConstructor();
        $params = ($constructor) ? $constructor->getParameters() : [];
        foreach ($params as $param) {
            $paramData = $this->buildParam($param);
            $this->constructorParams[$paramData->getName()] = $paramData;
        }
    }

    /**
     * @return Method[]
     * @throws ReflectionException
     */
    private function parseMethods(): array
    {
        if ($this->methods === null) {
            $this->methods = [];
            foreach ($this->getClassMethods() as $method) {
                if (in_array($method->getName(), $this->getConfig()->getNonTestableMethods())) {
                    continue;
                }
                $methodParams = [];
                foreach ($method->getParameters() as $parameter) {
                    $parameter = $this->buildParam($parameter);
                    $methodParams[] = $parameter;
                }
                if ($method->isPublic()) {
                    $this->methods[] = new Method($method->getName(), $methodParams, $method);
                }
            }
        }
        return $this->methods;
    }

    private function isParamMockable(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();
        if ($type === null) {
            return false;
        }
        return !isset($this->getConfig()->getNonMockable()[$parameter->getType()->getName()]);
    }

    private function buildParam(ReflectionParameter $param): Parameter
    {
        $realType = $this->getType($param->getType() ? $param->getType()->getName() : '');
        $paramKey = $this->getParamKey($realType, $param->getName());
        $this->parameters = $this->parameters ?? [];
        if (array_key_exists($paramKey, $this->parameters)) {
            return $this->parameters[$paramKey];
        }
        $parameter = new Parameter(
            $realType,
            $param->getName(),
            $this->isParamMockable($param),
            $this->config->getNonMockable()[$realType] ?? null
        );
        $this->parameters[$paramKey] = $parameter;
        return $parameter;
    }

    private function getType(string $type): string
    {
        return $this->getConfig()->getReplace()[$type] ?? $type;
    }

    private function getParamKey(string $type, string $name): string
    {
        return implode('##', array_filter([$type, $name]));
    }

    /**
     * @throws ReflectionException
     */
    public function isOwnMethod(ReflectionMethod $method): bool
    {
        return $method->class === $this->getReflection()->getName();
    }

    /**
     * @return Method[]
     * @throws ReflectionException
     */
    public function getMethods(): array
    {
        if ($this->methods === null) {
            $this->collectParams();
        }
        return $this->methods;
    }

    /**
     * @throws ReflectionException
     */
    public function hasNonStaticMethods(): bool
    {
        if ($this->hasNonStaticMethods === null) {
            $this->hasNonStaticMethods = count(
                array_filter(
                    $this->getMethods(),
                    function (Method $method) {
                        return !$method->isStatic();
                    }
                )
            ) > 0;
        }
        return $this->hasNonStaticMethods;
    }

    /**
     * @throws ReflectionException
     */
    public function getTestInstanceVarName(): string
    {
        if ($this->testInstanceVarName === null) {
            $name = lcfirst($this->getClassShortName());
            $index = 2;
            $checkName = $name;
            $this->collectParams();
            while (isset($this->parameters[$checkName])) {
                $parts = explode('\\', $this->getClassName());
                if (isset($parts[count($parts) - $index])) {
                    $name .= $parts[count($parts) - $index];
                    $checkName = $name;
                } else {
                    $checkName = $name .= $index;
                }
                $index++;
            }
            return $this->testInstanceVarName = $checkName;
        }
        return $this->testInstanceVarName;
    }

    /**
     * @throws ReflectionException
     */
    public function getMockables()
    {
        if ($this->mockables === null) {
            $this->mockables = array_filter(
                $this->getParameters(),
                function (Parameter $parameter) {
                    return $parameter->isMockable();
                }
            );
        }
        return $this->mockables;
    }

    public function formatMethodParams(
        string $params,
        int $prefixLength,
        int $suffixLength,
        int $indentation,
        int $stringLimit = 120
    ): string {
        if (strlen($params) + $prefixLength + $suffixLength <= $stringLimit) {
            return $params;
        }
        $indent = str_repeat(' ', $indentation);
        $parts = array_map(
            function ($part) use ($indent) {
                return $indent . $part;
            },
            array_map('trim', explode(',', $params))
        );
        return PHP_EOL .
            implode(',' . PHP_EOL, $parts) . PHP_EOL . substr($indent, 0, strlen($indent) - 4);
    }

    public function getTestMethodName(Method $method): string
    {
        $prefix = $this->getConfig()->getTestMethodPrefix();
        return $prefix . ($prefix !== '' ? ucfirst($method->getName()) : $method->getName());
    }

    /**
     * @throws ReflectionException
     */
    public function getCoverage(Method $method): array
    {
        $methodNames = array_map(
            function (ReflectionMethod $method) {
                return $method->getName();
            },
            $this->getClassMethods()
        );
        $covers = [$method->getName()];
        $content = $this->collectMethodCalls($method->getReflectionMethod());
        $covers = array_merge($covers, array_intersect($content ?? [], $methodNames));
        if (!$method->isStatic() && $this->hasConstructor()) {
            $covers[] = '__construct';
        }
        return $covers;
    }

    /**
     * @throws ReflectionException
     */
    private function collectMethodCalls(ReflectionMethod $method, array $collected = []): array
    {
        $regexes = [
            '/\$this->(\w+)\(.*?\)/',
            '/self\:\:(\w+)\(.*?\)/',
            '/static\:\:(\w+)\(.*?\)/'
        ];
        $file = $this->getReflection()->getFileName();
        $startLine = $method->getStartLine() - 1;
        $endLine = $method->getEndLine();
        $length = $endLine - $startLine;

        $source = file($file);
        $body = implode("", array_slice($source, $startLine, $length));
        $methods = array_reduce(
            $regexes,
            function (array $all, string $regex) use ($body) {
                $matches = [];
                preg_match_all($regex, $body, $matches);
                return array_unique(array_merge($all, $matches[1] ?? []));
            },
            []
        );
        foreach ($methods as $methodName) {
            if (!in_array($methodName, $collected)) {
                $collected[] = $methodName;
                try {
                    $subMethod = $this->getReflection()->getMethod($methodName);
                    $collected = $this->collectMethodCalls($subMethod, $collected);
                } catch (\ReflectionException $e) {
                    continue;
                }
            }
        }
        return $collected;
    }

    /**
     * @throws ReflectionException
     */
    private function hasConstructor(): bool
    {
        if ($this->hasConstructor === null) {
            $this->hasConstructor = $this->getReflection()->getConstructor()
                && $this->getReflection()->getConstructor()->getNumberOfParameters() > 0;
        }
        return $this->hasConstructor;
    }

    /**
     * @throws ReflectionException
     */
    public function getClassMethods()
    {
        if ($this->classMethods === null) {
            $this->classMethods = array_filter(
                $this->getReflection()->getMethods(),
                function (ReflectionMethod $method) {
                    return $this->isOwnMethod($method)
                        && !in_array($method->getName(), $this->getConfig()->getNonTestableMethods());
                }
            );
        }
        return $this->classMethods;
    }

    private function replaceClassNames(string $content): string
    {
        $regex = "/" . self::CLASS_PREFIX . '(.+)' . self::CLASS_SUFFIX . '/U';
        preg_match_all($regex, $content, $matches);
        if ($this->getConfig()->useFQN()) {
            $map = [self::USES_PLACEHOLDER => ''];
            foreach ($matches[0] ?? [] as $key => $match) {
                if (isset($matches[1][$key])) {
                    $map[$match] = $matches[1][$key];
                }
            }
            return str_replace(array_keys($map), array_values($map), $content);
        }
        $uses = $this->collectUses($matches);
        $map = [];
        $useLines = [];
        foreach ($uses as $use) {
            $useLines[] = 'use ' . ltrim($use['class'], '\\') . ($use['level'] > 0 ? ' as ' . $use['alias'] : '') . ';';
            $map[$use['original']] = $use['alias'];
        }
        usort($useLines, 'strcmp');
        $map[self::USES_PLACEHOLDER] = PHP_EOL . implode(PHP_EOL, $useLines) . PHP_EOL;
        return str_replace(array_keys($map), array_values($map), $content);
    }

    private function collectUses(array $matches): array
    {
        $result = [];
        foreach ($matches[0] as $key => $match) {
            if (!isset($matches[1][$key])) {
                continue;
            }
            $class = $matches[1][$key];
            $cleanUse = $this->getCleanUse($class, 0, $result);
            if ($cleanUse) {
                $result[$class] = $cleanUse;
                $result[$class]['original'] = $match;
            }
        }
        return $result;
    }

    private function getCleanUse(string $class, int $aliasLevel, array $collected): ?array
    {
        $candidate = $this->getAliasCandidate($class, $aliasLevel);
        foreach ($collected as $use) {
            if ($class === $use['class']) {
                return null;
            }
            if ($use['alias'] === $candidate) {
                return $this->getCleanUse($class, $aliasLevel+1, $collected);
            }
        }
        return [
            'class' => $class,
            'alias' => $candidate,
            'level' => $aliasLevel
        ];
    }

    private function getAliasCandidate(string $className, int $level): string
    {
        $parts = explode('\\', $className);
        $candidates = [];
        for ($i = 0; $i <= $level; $i++) {
            $candidates[] = array_pop($parts);
        }
        return implode('', $candidates);
    }
}
