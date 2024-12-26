<?php

namespace App;

use function array_rand;

enum Status: int
{
    case TO_DO = 1;
    case  DOING = 2;
    case DONE = 3;

    protected const STATUSES = [
        self::TO_DO->value => 'To Do',
        self::DOING->value => 'Doing',
        self::DONE->value => 'Done',
    ];

    public static function getAll(): array
    {
        return self::STATUSES;
    }

    public static function getText(int $value): string
    {
        return self::STATUSES[$value];
    }

    public static function getRandomValue(): int
    {
        return self::cases()[array_rand(self::cases())]->value;
    }
}
