<?php

declare(strict_types=1);

namespace app\modules\inversion\services;

use Yii;

final class LookupRateLimiter
{
    private const SESSION_KEY = 'subsanacion_lookup_attempts';
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_SECONDS = 600;

    public function tooManyAttempts(): bool
    {
        $attempts = $this->recentAttempts();
        Yii::$app->session->set(self::SESSION_KEY, $attempts);

        return count($attempts) >= self::MAX_ATTEMPTS;
    }

    public function hit(): void
    {
        $attempts = $this->recentAttempts();
        $attempts[] = time();
        Yii::$app->session->set(self::SESSION_KEY, $attempts);
    }

    public function clear(): void
    {
        Yii::$app->session->remove(self::SESSION_KEY);
    }

    /**
     * @return int[]
     */
    private function recentAttempts(): array
    {
        $cutoff = time() - self::WINDOW_SECONDS;
        $attempts = Yii::$app->session->get(self::SESSION_KEY, []);
        if (!is_array($attempts)) {
            return [];
        }

        return array_values(array_filter(
            $attempts,
            static fn (mixed $timestamp): bool => is_int($timestamp) && $timestamp >= $cutoff,
        ));
    }
}
