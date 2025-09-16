<?php

namespace App\System;

final class DateHelper
{
    public static array $russianDays = [
        1 => 'ПН',
        2 => 'ВТ',
        3 => 'СР',
        4 => 'ЧТ',
        5 => 'ПТ',
        6 => 'СБ',
        7 => 'ВС'
    ];

    public static function getDayOfTheWeek(string $date): string
    {
        $timestamp = strtotime($date);

        return self::$russianDays[date('N', $timestamp)];
    }
}
