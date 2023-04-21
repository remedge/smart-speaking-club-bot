<?php

declare(strict_types=1);

namespace App\SpeakingClub\Domain;

use Ramsey\Uuid\UuidInterface;

interface ParticipationRepository
{
    public function save(Participation $participation): void;

    public function remove(Participation $participation): void;

    public function isUserParticipantOfSpeakingClub(UuidInterface $userId, UuidInterface $speakingClubId): bool;

    public function findByUserIdAndSpeakingClubId(UuidInterface $userId, UuidInterface $speakingClubId): ?Participation;

    public function countByClubId(UuidInterface $speakingClubId): int;

    /**
     * @return array<array{id: UuidInterface, username: string, firstName: string, lastName: string, chatId: int, isPlusOne: bool}>
     */
    public function findBySpeakingClubId(UuidInterface $speakingClubId): array;

    public function findById(UuidInterface $id): ?Participation;
}
