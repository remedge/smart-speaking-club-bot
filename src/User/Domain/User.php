<?php

declare(strict_types=1);

namespace App\User\Domain;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

class User
{
    public function __construct(
        private UuidInterface $id,
        private int $chatId,
        private string $firstName,
        private string $lastName,
        private string $username,
        private DateTimeImmutable $createdAt,
        private UserStateEnum $state = UserStateEnum::IDLE,
        private array $actualSpeakingClubData = [],
    )
    {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getChatId(): int
    {
        return $this->chatId;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getState(): UserStateEnum
    {
        return $this->state;
    }

    public function setState(UserStateEnum $state): void
    {
        $this->state = $state;
    }

    public function getActualSpeakingClubData(): array
    {
        return $this->actualSpeakingClubData;
    }

    public function setActualSpeakingClubData(array $actualSpeakingClubData): void
    {
        $this->actualSpeakingClubData = $actualSpeakingClubData;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}