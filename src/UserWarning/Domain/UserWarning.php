<?php

declare(strict_types=1);

namespace App\UserWarning\Domain;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

class UserWarning
{
    public function __construct(
        private UuidInterface $id,
        private UuidInterface $userId,
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

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
