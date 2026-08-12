<?php

declare(strict_types=1);

namespace Canopy\Check;

use Canopy\Result\Result;

interface CheckInterface
{
    public function id(): string;

    /**
     * @return iterable<Result>
     */
    public function run(CheckContext $context): iterable;
}
