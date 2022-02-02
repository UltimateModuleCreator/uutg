<?php

declare(strict_types=1);

namespace Uutg\Profile;

use Uutg\Exception\ConfigException;
use Uutg\Profile;

class Loader
{
    /**
     * @var array
     */
    private $profilePaths;
    /**
     * @var |array
     */
    private $profiles = [];
    /**
     * @var null | array
     */
    private $config = null;

    /**
     * @param array $profilePaths
     */
    public function __construct(array $profilePaths)
    {
        $this->profilePaths = $profilePaths;
    }

    /**
     * @param $key
     * @return Profile
     * @throws ConfigException
     */
    public function get($key): Profile
    {
        if (!isset($this->profiles[$key])) {
            $this->profiles[$key] = new Profile($this->process($key));
        }
        return $this->profiles[$key];
    }

    /**
     * @return array
     */
    private function loadAllConfigs(): array
    {
        if ($this->config === null) {
            $this->config = [];
            $files = array_reduce(
                $this->profilePaths,
                function ($all, string $path) {
                    return array_merge($all, glob($path . '*.php'));
                },
                []
            );
            $this->config = array_reduce(
                $files,
                function ($collected, $file) {
                    return array_merge_recursive($collected, require $file);
                },
                []
            );
        }
        return $this->config;
    }

    /**
     * @param $key
     * @param array $collected
     * @return array
     * @throws ConfigException
     */
    private function process($key, array $collected = []): array
    {
        $all = $this->loadAllConfigs();
        if (!isset($all[$key])) {
            throw new ConfigException("Missing profile key $key");
        }
        if (in_array($key, $collected)) {
            $collected[] = $key;
            $message = implode('->', $collected);
            throw new ConfigException("Profile Circular Dependency found: $message");
        }
        $collected[] = $key;
        $config = $all[$key];
        return isset($config['extends'])
            ? array_replace_recursive($this->process($config['extends'], $collected), $config)
            : $config;
    }
}
