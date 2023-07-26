<?php

declare(strict_types=1);

namespace App\SpeakingClub\Domain;

use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

interface RatingRepository
{
    public function save(Rating $rating): void;

    public function findBySpeakingClubIdAndUserId(
        UuidInterface $speakingClubId,
        UuidInterface $userId,
    ): ?Rating;

    /**
     * @return array<array{name: string, date: DateTimeInterface, username: string, id: UuidInterface, rating: int, comment: string}>
     */
    public function findAllNondumped(): array;

    public function markDumped(UuidInterface $ratingId): void;
}
