<?php

declare(strict_types=1);

namespace Uutg;

use Exception;
use ReflectionException;
use Uutg\Exception\ConfigException;
use Uutg\Profile\Loader;
use Uutg\TestInstance\Builder;

class App
{
    public const DEFAULT_PROFILE = 'default';
    public const DEFAULT_SEPARATOR = PHP_EOL . PHP_EOL;
    /**
     * @var array
     */
    private $options;

    private $profileLoader;
    private $builder;
    private $renderer;
    private $profile = null;

    /**
     * @param Loader $profileLoader
     * @param Builder $builder
     * @param Renderer $renderer
     * @param array $options
     */
    public function __construct(
        Loader $profileLoader,
        Builder $builder,
        Renderer $renderer,
        array $options
    ) {
        $this->profileLoader = $profileLoader;
        $this->builder = $builder;
        $this->renderer = $renderer;
        $this->options = $options;
    }

    /**
     * generate the tests
     */
    public function run(): string
    {
        try {
            $this->includeAutoloader();
            $contents = array_map(
                function ($className) {
                    return $this->renderer->render(
                        $this->getTemplate(),
                        ['test' => $this->build($className)]
                    );
                },
                explode(',', $this->options['class'] ?? '')
            );
            return implode($this->options['separator'] ?? self::DEFAULT_SEPARATOR, $contents);
        } catch (ConfigException $e) {
            return "Error: " . $e->getMessage() . self::DEFAULT_SEPARATOR;
        } catch (Exception $e) {
            return $e->getMessage() . self::DEFAULT_SEPARATOR . $this->getUsage(). self::DEFAULT_SEPARATOR;
        }
    }

    /**
     * @return string
     */
    private function getUsage(): string
    {
        $usage = $this->isCli()
            ? "Usage: `php index.php --class=\"ClassName\Here,\OtherClass\GoesHere" .
                " [--profile=profile] [--separator=separator]"
            : "Usage index.php?class=\ClassName\Here[&profile=profile]";
        return $usage . self::DEFAULT_SEPARATOR . $this->getParamUsage();
    }

    /**
     * @return string
     */
    private function getParamUsage(): string
    {
        $params = [
            '"class" - mandatory: Class or list of classes separated by comma for which the tests are generated',
            '"profile" - optional: Profile to use for generating tests. Defaults to ' . self::DEFAULT_PROFILE,
            '"separator" - optional: Separator for output when generating content for multiple classes at once. '
                . 'Defaults to 2 new lines'
        ];
        return implode(PHP_EOL, $params);
    }

    /**
     * @return bool
     */
    private function isCli(): bool
    {
        return isset($this->options['cli']) && $this->options['cli'];
    }

    /**
     * @return string
     * @throws ConfigException
     */
    private function getTemplate(): string
    {
        return isset($this->options['template'])
            ? $this->options['template'] . '.phtml'
            : $this->getProfile()->getTemplate() . '.phtml';
    }

    /**
     * @param string $className
     * @return TestInstance
     * @throws ReflectionException|ConfigException
     */
    private function build(string $className): TestInstance
    {
        $this->builder->setProfile($this->getProfile());
        $this->builder->setClassName($className);
        return $this->builder->build();
    }

    /**
     * include autoloader of the app
     * @throws ConfigException
     */
    private function includeAutoloader()
    {
        if ($path = $this->getProfile()->getAutoloadPath()) {
            require $path;
        }
    }

    /**
     * @return Profile
     * @throws ConfigException
     */
    private function getProfile(): Profile
    {
        if ($this->profile === null) {
            $profile = $this->options['profile'] ?? self::DEFAULT_PROFILE;
            $this->profile = $this->profileLoader->get($profile);
        }
        return $this->profile;
    }
}
