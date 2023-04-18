<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\DTO;

use Ramsey\Uuid\UuidInterface;

class ParticipationDTO
{
    public function __construct(
        public UuidInterface $id,
        public string $username,
        public bool $isPlusOne,
    ) {
    }
}
