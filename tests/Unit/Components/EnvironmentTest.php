<?php

declare(strict_types=1);

namespace app\tests\Unit\Components;

use app\components\Environment;
use Codeception\Test\Unit;

final class EnvironmentTest extends Unit
{
    public function testUsesDefaultsWhenVariablesAreMissing(): void
    {
        self::assertSame('fallback', Environment::string('UNDEFINED_TEST_VARIABLE', 'fallback'));
        self::assertSame(15, Environment::int('UNDEFINED_TEST_VARIABLE', 15));
        self::assertTrue(Environment::bool('UNDEFINED_TEST_VARIABLE', true));
    }
}
