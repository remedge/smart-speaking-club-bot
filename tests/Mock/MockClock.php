<?php
/*
 * Copyright (c) Developed by Diffco US inc. https://diffco.us  2023.
 */

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Shared\Application\Clock;
use DateTimeImmutable;
use Exception;

class MockClock implements Clock
{
    private ?DateTimeImmutable $now;

    /**
     * @throws Exception
     */
    public function setNow(string $now): void
    {
        $this->now = new DateTimeImmutable($now);
    }

    public function now(): DateTimeImmutable
    {
        return $this->now ?: new DateTimeImmutable('2000-01-01 00:00:00');
    }
}
