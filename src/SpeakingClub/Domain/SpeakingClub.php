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
        private bool $isCancelled = false,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setMaxParticipantsCount(int $maxParticipantsCount): void
    {
        $this->maxParticipantsCount = $maxParticipantsCount;
    }

    public function getMaxParticipantsCount(): int
    {
        return $this->maxParticipantsCount;
    }

    public function setDate(DateTimeImmutable $date): void
    {
        $this->date = $date;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function cancel(): void
    {
        $this->isCancelled = true;
    }

    public function isCancelled(): bool
    {
        return $this->isCancelled;
    }
}
