<?php

declare(strict_types=1);

namespace Uutg\Rule;

use Uutg\TestInstance\Builder;

interface RuleInterface
{
    /**
     * @param Builder $builder
     */
    public function process(Builder $builder): void;
}
