<?php

declare(strict_types=1);

namespace App\SpeakingClub\Domain;

use Ramsey\Uuid\UuidInterface;

class Participation
{
    public function __construct(
        private UuidInterface $id,
        private UuidInterface $userId,
        private UuidInterface $speakingClubId,
        private bool $isPlusOne,
        private ?string $plusOneName = null,
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

    public function isPlusOne(): bool
    {
        return $this->isPlusOne;
    }

    public function setIsPlusOne(bool $isPlusOne): void
    {
        $this->isPlusOne = $isPlusOne;
    }

    public function getPlusOneName(): ?string
    {
        return $this->plusOneName;
    }

    public function setPlusOneName(?string $plusOneName): void
    {
        $this->plusOneName = $plusOneName;
    }
}
