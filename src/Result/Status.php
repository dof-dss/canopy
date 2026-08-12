<?php

declare(strict_types=1);

namespace Canopy\Result;

enum Status: string
{
    case Pass = 'pass';
    case Warn = 'warn';
    case Fail = 'fail';
    case Unknown = 'unknown';
    case Skipped = 'skipped';
}
