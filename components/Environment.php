<?php

declare(strict_types=1);

namespace app\components;

final class Environment
{
    public static function string(string $name, string $default = ''): string
    {
        $value = getenv($name);
        return $value === false || $value === '' ? $default : $value;
    }

    public static function bool(string $name, bool $default = false): bool
    {
        $value = getenv($name);
        if ($value === false || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    public static function int(string $name, int $default): int
    {
        $value = getenv($name);
        return $value === false || !ctype_digit($value) ? $default : (int) $value;
    }
}
