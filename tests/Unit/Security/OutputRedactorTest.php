<?php

declare(strict_types=1);

namespace Canopy\Tests\Unit\Security;

use Canopy\Security\OutputRedactor;
use PHPUnit\Framework\TestCase;

final class OutputRedactorTest extends TestCase
{
    public function testRedactsSecretsPathsCredentialsAndControlCharacters(): void
    {
        $redactor = new OutputRedactor();
        $value = $redactor->text(
            "token=example-value at /srv/private/project and https://user:pass@example.test/\e[31m",
            ['/srv/private/project'],
        );

        self::assertSame(
            'token=[redacted] at [path] and https://[redacted]@example.test/[31m',
            $value,
        );
    }

    public function testRedactsValuesUnderSensitiveEvidenceKeys(): void
    {
        $redactor = new OutputRedactor();
        $value = $redactor->value([
            'api_token' => 'example-value',
            'nested' => ['password' => 'example-password'],
            'safe' => 'retained',
        ]);

        self::assertSame('[redacted]', $value['api_token']);
        self::assertSame('[redacted]', $value['nested']['password']);
        self::assertSame('retained', $value['safe']);
    }
}
