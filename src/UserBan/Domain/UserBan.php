<?php

declare(strict_types=1);

namespace App\UserBan\Domain;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

class UserBan
{
    public function __construct(
        private UuidInterface $id,
        private UuidInterface $userId,
        private DateTimeImmutable $endDate,
        private DateTimeImmutable $createdAt,
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

    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
