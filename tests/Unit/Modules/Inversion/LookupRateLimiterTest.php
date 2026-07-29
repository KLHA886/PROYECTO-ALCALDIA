<?php

declare(strict_types=1);

namespace app\tests\Unit\Modules\Inversion;

use app\modules\inversion\services\LookupRateLimiter;
use Codeception\Test\Unit;

final class LookupRateLimiterTest extends Unit
{
    protected function _after(): void
    {
        (new LookupRateLimiter())->clear();
    }

    public function testBlocksAfterFiveFailedAttempts(): void
    {
        $limiter = new LookupRateLimiter();
        self::assertFalse($limiter->tooManyAttempts());

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $limiter->hit();
        }

        self::assertTrue($limiter->tooManyAttempts());
    }

    public function testClearRestoresAccess(): void
    {
        $limiter = new LookupRateLimiter();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $limiter->hit();
        }
        $limiter->clear();

        self::assertFalse($limiter->tooManyAttempts());
    }
}
