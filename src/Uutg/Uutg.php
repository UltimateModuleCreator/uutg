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

class Uutg
{
    public function __construct(
        private Config $config,
        private ?string $className
    ) {
    }

    public function run(): string
    {
        if (empty($this->className)) {
            return $this->getUsage();
        }
        if (!class_exists($this->className)) {
            return sprintf('Class "%s" does not exist', $this->className);
        }
        $generator = new Generator($this->config, $this->className);
        return $generator->generate();
    }

    private function getUsage(): string
    {
        $usage = [
            "Usage: `php uutg --class=\"ClassName\Here\" --config=\"path/to/config\"",
            '"class" - mandatory: Class or list of classes separated by comma for which the tests are generated',
            '"config" - optional: Config to use for generating tests'
        ];
        return  implode("\n", $usage) . "\n";
    }
}
