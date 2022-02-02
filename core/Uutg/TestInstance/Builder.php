<?php

declare(strict_types=1);

namespace Uutg\TestInstance;

use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use Uutg\Profile;
use Uutg\ReflectionFactory;
use Uutg\Rule\Factory as RuleFactory;
use Uutg\TestInstance;

class Builder
{
    /**
     * @var ReflectionFactory
     */
    private $reflectionFactory;
    /**
     * @var RuleFactory
     */
    private $ruleFactory;
    /**
     * @var string
     */
    private $className;
    /**
     * @var ReflectionClass
     */
    private $reflection;
    /**
     * @var array
     */
    private $uses = [];
    /**
     * @var array
     */
    private $constructorParams = [];
    /**
     * @var Profile
     */
    private $profile;
    /**
     * @var array
     */
    private $mockables = [];
    /**
     * @var array
     */
    private $parameters = [];
    /**
     * @var array
     */
    private $methods = [];
    /**
     * @var string
     */
    private $header;
    /**
     * @var array
     */
    private $additionalData = [];

    /**
     * @param ReflectionFactory $reflectionFactory
     * @param RuleFactory $ruleFactory
     */
    public function __construct(
        ReflectionFactory $reflectionFactory,
        RuleFactory $ruleFactory
    ) {
        $this->reflectionFactory = $reflectionFactory;
        $this->ruleFactory = $ruleFactory;
    }

    /**
     * @param Profile $profile
     */
    public function setProfile(Profile $profile): void
    {
        $this->profile = $profile;
    }

    /**
     * @return Profile
     * @throws Exception
     */
    public function getProfile(): Profile
    {
        if ($this->profile === null) {
            throw new Exception("Profile should be set before building the test");
        }
        return $this->profile;
    }

    /**
     * @param null|string $className
     */
    public function setClassName(?string $className): void
    {
        $this->className = $className;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getClassName(): string
    {
        if ($this->className === null) {
            throw new Exception("Class name should be set before building the test");
        }
        return trim($this->className, '\\');
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function build(): TestInstance
    {
        $this->addUse(trim($this->getClassName(), '\\'));
        $defaultUses = $this->getProfile()->getDefaultUses();
        array_walk(
            $defaultUses,
            function ($use) {
                $this->addUse($use);
            }
        );
        $this->parseConstructor();
        $this->parseMethods();
        foreach ($this->getProfile()->getRuleSet() as $ruleClass) {
            $rule = $this->ruleFactory->create($ruleClass);
            $rule->process($this);
        }
        $instance = new TestInstance(
            $this->getClassName(),
            [
                TestInstance::HEADER => $this->getHeader(),
                TestInstance::USES => $this->getSortedUses(),
                TestInstance::NAMESPACE => $this->getNamespace(),
                TestInstance::CONSTRUCTOR_PARAMS => $this->constructorParams,
                TestInstance::METHODS => $this->methods,
                TestInstance::MOCKABLES => $this->mockables,
                TestInstance::STRONG_MODE => $this->getProfile()->isStrongType(),
                TestInstance::ADDITIONAL_DATA => $this->getAdditionalData()

            ]
        );
        $this->reset();
        return $instance;
    }

    /**
     * @param string $header
     */
    public function setHeader(string $header): void
    {
        $this->header = $header;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getHeader(): string
    {
        if ($this->header === null) {
            $header = $this->getProfile()->getHeader();
            $this->setHeader(is_array($header) ? implode(PHP_EOL, $header) : $header);
        }
        return $this->header;
    }

    /**
     * @param string $class
     */
    public function addUse(string $class)
    {
        $cleanUse = $this->getCleanUse($class);
        $cleanUse && ($this->uses[$class] = $this->getCleanUse($class));
    }

    /**
     * @param string $class
     * @param int $aliasLevel
     * @return array|null
     */
    private function getCleanUse(string $class, int $aliasLevel = 0): ?array
    {
        $candidate = $this->getAliasCandidate($class, $aliasLevel);
        foreach ($this->uses as $use) {
            if ($class === $use['class']) {
                return null;
            }
            if ($use['alias'] === $candidate) {
                return $this->getCleanUse($class, $aliasLevel+1);
            }
        }
        return [
            'class' => $class,
            'alias' => $candidate,
            'level' => $aliasLevel
        ];
    }

    /**
     * @param string $className
     * @param int $level
     * @return string
     */
    private function getAliasCandidate(string $className, int $level): string
    {
        $parts = explode('\\', $className);
        $candidates = [];
        for ($i = 0; $i <= $level; $i++) {
            $candidates[] = array_pop($parts);
        }
        return implode('', $candidates);
    }

    /**
     * @return array
     */
    private function getSortedUses(): array
    {
        uasort(
            $this->uses,
            function (array $useA, array $useB) {
                return strcmp($useA['class'], $useB['class']);
            }
        );
        return $this->uses;
    }

    /**
     * @param string $type
     * @param string $name
     * @return array
     */
    public function addParam(string $type, string $name): array
    {
        $key = $this->getParamKey($type, $name);
        if (isset($this->parameters[$key])) {
            return $this->parameters[$key];
        }
        $cleanParam = [
            'class' => $type,
            'name' => $this->getCleanParamName($type, $name),
        ];
        $cleanKey = $this->getParamKey($cleanParam['class'], $cleanParam['name']);
        $this->parameters[$cleanKey] = $cleanParam;
        return $cleanParam;
    }

    /**
     * @param string $type
     * @param string $name
     * @param int $level
     * @return string
     */
    private function getCleanParamName(string $type, string $name, int $level = 1): string
    {
        foreach ($this->parameters as $parameter) {
            if ($parameter['name'] === $name) {
                if ($parameter['class'] === $type) {
                    return $name;
                } else {
                    $parts = explode('\\', $type);
                    $classPart = $parts[count($parts) - $level] ?? '';
                    return $this->getCleanParamName($type, $name . ucfirst($classPart), $level+1);
                }
            }
        }
        return $name;
    }

    /**
     * @param string $type
     * @param string $name
     * @return string
     */
    private function getParamKey(string $type, string $name): string
    {
        return implode('##', array_filter([$type, $name]));
    }

    /**
     * @param array $param
     */
    public function addMockable(array $param)
    {
        $this->mockables = $this->mockables ?? [];
        $this->mockables[$param['name']] = $param;
    }

    /**
     * @param ReflectionParameter $param
     * @return array
     * @throws Exception
     */
    private function processParam(ReflectionParameter $param): array
    {
        $nonMockable = $this->getProfile()->getNonMockable();
        if ($this->isParamMockable($param)) {
            $realType = $this->getType($param->getType()->getName());
            $this->addUse($realType);
            $paramData = $this->addParam($realType, $param->getName());
            $this->addMockable($paramData);
            $paramData['mockable'] = true;
            $paramData['value'] = null;
        } else {
            $type = $param->getType() ? $param->getType()->getName() : '';
            $paramData = [
                'class' => $type,
                'name' => $param->getName(),
                'value' => $nonMockable[$type] ?? '""',
                'mockable' => false
            ];
        }
        return $paramData;
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    private function parseConstructor()
    {
        $constructor = $this->getReflection()->getConstructor();
        $params = ($constructor) ? $constructor->getParameters() : [];
        foreach ($params as $param) {
            $paramData = $this->processParam($param);
            $this->constructorParams[$paramData['name']] = $paramData;
        }
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    private function parseMethods()
    {
        $hasConstructor = $this->getReflection()->getConstructor()
            && $this->getReflection()->getConstructor()->getNumberOfParameters() > 0;
        $methods = array_filter(
            $this->getReflection()->getMethods(),
            function (ReflectionMethod $method) {
                return $this->isOwnMethod($method)
                    && !in_array($method->getName(), $this->getProfile()->getNonTestableMethods());
            }
        );
        $methodNames = array_map(
            function (ReflectionMethod $method) {
                return $method->getName();
            },
            $methods
        );
        foreach ($methods as $method) {
            if (in_array($method->getName(), $this->getProfile()->getNonTestableMethods())) {
                continue;
            }
            $covers = [$method->getName()];
            $content = $this->getMethodCalls($method);
            $covers = array_merge($covers, array_intersect($content ?? [], $methodNames));
            if (!$method->isStatic() && $hasConstructor) {
                $covers[] = '__construct';
            }
            $methodParams = [];
            foreach ($method->getParameters() as $parameter) {
                $paramData = $this->processParam($parameter);
                $methodParams[] = $paramData;
            }
            if ($method->isPublic()) {
                $this->methods[] = [
                    'name' => $method->getName(),
                    'covers' => $covers,
                    'static' => $method->isStatic(),
                    'params' => $methodParams
                ];
            }
        }
    }

    /**
     * @param $type
     * @return string
     * @throws Exception
     */
    private function getType($type): string
    {
        return $this->getProfile()->getReplacements()[$type] ?? $type;
    }

    /**
     * @param ReflectionParameter $parameter
     * @return bool
     * @throws Exception
     */
    private function isParamMockable(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();
        if ($type === null) {
            return false;
        }
        return !isset($this->getProfile()->getNonMockable()[$parameter->getType()->getName()]);
    }

    /**
     * @return ReflectionClass
     * @throws ReflectionException
     * @throws Exception
     */
    public function getReflection(): ReflectionClass
    {
        if ($this->reflection === null) {
            $this->reflection = $this->reflectionFactory->create($this->getClassName());
        }
        return $this->reflection;
    }

    /**
     * @throws ReflectionException
     */
    public function getNamespace(): string
    {
        $namespace = $this->getReflection()->getNamespaceName();
        if (!$namespace) {
            return '';
        }
        $parts = explode('\\', trim($namespace, '\\'));
        foreach ($this->profile->getNamespaceStrategy() as $position => $value) {
            array_splice($parts, $position, 0, $value);
        }
        return implode('\\', $parts);
    }

    /**
     * @param ReflectionMethod $method
     * @param array $collected
     * @return array
     * @throws ReflectionException
     */
    private function getMethodCalls(ReflectionMethod $method, array $collected = []): array
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
                $subMethod = $this->getReflection()->getMethod($methodName);
                $collected = $this->getMethodCalls($subMethod, $collected);
            }
        }
        return $collected;
    }

    /**
     * @param ReflectionMethod $method
     * @return bool
     * @throws ReflectionException
     */
    public function isOwnMethod(ReflectionMethod $method): bool
    {
        return $method->class === $this->getReflection()->getName();
    }

    /**
     * @param array $data
     */
    public function setAdditionalData(array $data): void
    {
        $this->additionalData = $data;
    }

    /**
     * @return array
     */
    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }

    /**
     * @param string $key
     * @param $data
     */
    public function addAdditionalData(string $key, $data): void
    {
        $this->additionalData[$key] = $data;
    }

    /**
     * reset params for next build
     */
    private function reset()
    {
        $this->className = null;
        $this->uses = [];
        $this->mockables = [];
        $this->constructorParams = [];
        $this->profile = null;
        $this->parameters = [];
        $this->methods = [];
        $this->additionalData = [];
        $this->header = null;
    }
}
