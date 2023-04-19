<?php

declare(strict_types=1);

namespace App\WaitList\Application\DTO;

use Ramsey\Uuid\UuidInterface;

class WaitingUserDTO
{
    public function __construct(
        public UuidInterface $id,
        public UuidInterface $userId,
        public UuidInterface $speakingClubId,
    ) {
    }
}
