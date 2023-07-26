<?php

declare(strict_types=1);

namespace App\SpeakingClub\Domain;

use Ramsey\Uuid\UuidInterface;

class Rating
{
    // 1 🥱 - плохо, никогда больше не приду
    // 2 😐 - не очень, скорее не приду
    // 3 🙂 - не плохо, скорее приду
    // 4 🤩 - отлично, обязательно приду еще

    public function __construct(
        private UuidInterface $id,
        private UuidInterface $userId,
        private UuidInterface $speakingClubId,
        private int $rating,
        private bool $isDumped = false,
        private ?string $comment = null,
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

    public function getRating(): int
    {
        return $this->rating;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    public function isDumped(): bool
    {
        return $this->isDumped;
    }

    public function markAsDumped(): void
    {
        $this->isDumped = true;
    }
}
