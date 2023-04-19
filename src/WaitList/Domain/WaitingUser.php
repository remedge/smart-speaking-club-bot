<?php

declare(strict_types=1);

namespace App\WaitList\Domain;

use Ramsey\Uuid\UuidInterface;

class WaitingUser
{
    public function __construct(
        private UuidInterface $id,
        private UuidInterface $userId,
        private UuidInterface $speakingClubId,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getUserId(): UuidInterface
    {
        return $this->userId;
    }

    public function getSpeakingClubId(): UuidInterface
    {
        return $this->speakingClubId;
    }
}
