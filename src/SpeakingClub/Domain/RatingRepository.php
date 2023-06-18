<?php

declare(strict_types=1);

namespace App\SpeakingClub\Domain;

use Ramsey\Uuid\UuidInterface;

interface RatingRepository
{
    public function save(Rating $rating): void;

    public function findBySpeakingClubIdAndUserId(
        UuidInterface $speakingClubId,
        UuidInterface $userId,
    ): ?Rating;
}
