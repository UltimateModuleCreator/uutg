<?php

declare(strict_types=1);

namespace Uutg;

use Uutg\Exception\ConfigException;

class Renderer
{
    /**
     * @var array
     */
    private $templatePaths;
    /**
     * @var Utility
     */
    private $utility;

    /**
     * Renderer constructor.
     * @param Utility $utility
     * @param array $templatePaths
     */
    public function __construct(
        Utility $utility,
        array $templatePaths
    ) {
        $this->utility = $utility;
        $this->templatePaths = $templatePaths;
    }

    /**
     * @param string $template
     * @param array $vars
     * @return false|string
     * @throws ConfigException
     */
    public function render(string $template, array $vars = [])
    {
        $vars['utility'] = $this->utility;
        ob_start();
        extract($vars);
        include $this->findTemplate($template);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    /**
     * @param string $template
     * @return string
     * @throws ConfigException
     */
    private function findTemplate(string $template): string
    {
        foreach ($this->templatePaths as $path) {
            $file = $path . '/' . $template;
            if (is_file($file)) {
                return $file;
            }
        }
        throw new ConfigException(
            "Template {$template} does not exist in paths " . implode(', ', $this->templatePaths)
        );
    }
}
