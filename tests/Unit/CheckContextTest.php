<?php

declare(strict_types=1);

namespace Canopy\Tests\Unit;

use Canopy\Check\CheckContext;
use PHPUnit\Framework\TestCase;

final class CheckContextTest extends TestCase
{
    public function testCapabilitiesMustBeExplicitlyPermitted(): void
    {
        $context = new CheckContext('/project', ['database' => true]);

        self::assertTrue($context->permits('database'));
        self::assertFalse($context->permits('network'));
    }
}
