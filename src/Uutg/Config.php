<?php
declare(strict_types=1);

namespace Uutg;

class Config
{
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
        return $this->config['default_uses'] ?? [];
    }

    /**
     * @return array
     */
    public function getNamespaceStrategy(): array
    {
        return $this->config['namespace_strategy'] ?? [];
    }

    /**
     * @return string
     */
    public function getHeader(): string
    {
        $header = $this->config['header'] ?? [];
        return is_array($header) ? implode(PHP_EOL, $header) : $header;
    }

    /**
     * @return array
     */
    public function getNonTestableMethods(): array
    {
        return $this->config['non_testable'] ?? [];
    }

    /**
     * @return array
     */
    public function getNonMockable(): array
    {
        return $this->config['non_mockable'] ?? [];
    }
}
