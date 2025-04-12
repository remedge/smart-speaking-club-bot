<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Event;

use Ramsey\Uuid\UuidInterface;

class SpeakingClubScheduleChangedEvent
{
    public function __construct(
        public UuidInterface $speakingClubId,
        public string $date,
    ) {
    }
}
