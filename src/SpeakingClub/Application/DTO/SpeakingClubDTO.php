<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\DTO;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

class SpeakingClubDTO
{
    public function __construct(
        public UuidInterface $id,
        public string $name,
        public DateTimeImmutable $date,
    ) {
    }
}
