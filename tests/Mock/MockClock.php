<?php
/*
 * Copyright (c) Developed by Diffco US inc. https://diffco.us  2023.
 */

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Shared\Application\Clock;
use DateTimeImmutable;

class MockClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2000-01-01 00:00:00');
    }
}
