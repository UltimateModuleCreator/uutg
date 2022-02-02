<?php

declare(strict_types=1);

namespace Uutg;

class Profile
{
    public const DEFAULT_TEMPLATE = 'test';
    /**
     * @var array
     */
    private $config;

    /**
     * Config constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getDefaultUses(): array
    {
        return $this->getConfig('default_uses', []);
    }

    /**
     * @return array
     */
    public function getNamespaceStrategy(): array
    {
        return $this->getConfig('namespace_strategy', []);
    }

    /**
     * @return string
     */
    public function getHeader(): string
    {
        $header = $this->getConfig('header', '');
        return is_array($header) ? implode(PHP_EOL, $header) : $header;
    }

    /**
     * @return array
     */
    public function getNonTestableMethods(): array
    {
        return $this->getConfig('non_testable', []);
    }

    /**
     * @return array
     */
    public function getNonMockable(): array
    {
        return $this->getConfig('non_mockable', []);
    }

    /**
     * @return bool
     */
    public function isStrongType(): bool
    {
        return (bool)$this->getConfig('strong_type', false);
    }

    /**
     * @return array
     */
    public function getRuleSet(): array
    {
        return $this->getConfig('rule_set', []);
    }

    /**
     * @return string|null
     */
    public function getAutoloadPath(): ?string
    {
        return $this->getConfig('autoload_path');
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->getConfig('template', self::DEFAULT_TEMPLATE);
    }

    public function getReplacements()
    {
        return $this->getConfig('replacements', []);
    }

    /**
     * @param $path
     * @param null $default
     * @return array|mixed|null
     */
    public function getConfig($path, $default = null)
    {
        $parts = explode('/', $path);
        $data = $this->config;
        foreach ($parts as $part) {
            if (array_key_exists($path, $data)) {
                $data = $data[$part];
            } else {
                return $default;
            }
        }
        return $data;
    }
}
