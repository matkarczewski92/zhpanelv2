<?php

namespace App\Domain\Shared\Enums;

enum Sex: int
{
    case Unknown = 1;
    case Male = 2;
    case Female = 3;

    public static function label(int $value): string
    {
        return self::options()[$value] ?? 'n/sex';
    }

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return [
            self::Unknown->value => 'n/sex',
            self::Male->value => 'samiec',
            self::Female->value => 'samica',
        ];
    }
}
