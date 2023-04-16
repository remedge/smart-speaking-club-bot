<?php

declare(strict_types=1);

namespace App\SpeakingClub\Domain;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

class SpeakingClub
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private string $description,
        private int $maxParticipantsCount,
        private DateTimeImmutable $date,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getMaxParticipantsCount(): int
    {
        return $this->maxParticipantsCount;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }
}
