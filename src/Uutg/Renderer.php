<?php

declare(strict_types=1);

namespace Uutg;

class Renderer
{
    /**
     * @var string
     */
    private $basePath;

    /**
     * Renderer constructor.
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * @param string $template
     * @param array $vars
     * @return false|string
     */
    public function render(string $template, array $vars = [])
    {
        ob_start();
        extract($vars);
        include $this->basePath . '/' . $template;
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
}
