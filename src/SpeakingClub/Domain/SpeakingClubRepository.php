<?php

declare(strict_types=1);

namespace App\SpeakingClub\Domain;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

interface SpeakingClubRepository
{
    public function save(SpeakingClub $speakingClub): void;

    /**
     * @return array<SpeakingClub>
     */
    public function findAllUpcoming(DateTimeImmutable $now): array;

    public function findById(UuidInterface $id): ?SpeakingClub;

    /**
     * @return array<SpeakingClub>
     */
    public function findUserUpcoming(UuidInterface $userId, DateTimeImmutable $now): array;

    /**
     * @return array<SpeakingClub>
     */
    public function findBetweenDates(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array;

    /**
     * @return array<SpeakingClub>
     */
    public function findAllPastNotArchived(DateTimeImmutable $now): array;
}
