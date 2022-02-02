<?php

declare(strict_types=1);

namespace Uutg;

class Utility
{
    /**
     * @param string $params
     * @param int $prefixLength
     * @param int $suffixLength
     * @param int $indentation
     * @param int $stringLimit
     * @return string
     */
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
}
