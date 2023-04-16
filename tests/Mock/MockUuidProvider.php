<?php
/*
 * Copyright (c) Developed by Diffco US inc. https://diffco.us  2023.
 */

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Shared\Application\UuidProvider;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class MockUuidProvider implements UuidProvider
{
    private int $value = 0;

    public function provide(): UuidInterface
    {
        $value = Uuid::fromInteger((string) $this->value);
        $this->value += 1;

        return $value;
    }
}
