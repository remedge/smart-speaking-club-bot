<?php

declare(strict_types=1);

namespace App\SpeakingClub\Domain;

use DateTimeImmutable;

interface SpeakingClubRepository
{
    public function save(SpeakingClub $speakingClub): void;

    /**
     * @return array<SpeakingClub>
     */
    public function findAllUpcoming(DateTimeImmutable $now): array;
}