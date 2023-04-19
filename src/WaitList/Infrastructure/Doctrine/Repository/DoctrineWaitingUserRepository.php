<?php

declare(strict_types=1);

namespace App\WaitList\Infrastructure\Doctrine\Repository;

use App\WaitList\Domain\WaitingUser;
use App\WaitList\Domain\WaitingUserRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<WaitingUser>
 */
class DoctrineWaitingUserRepository extends ServiceEntityRepository implements WaitingUserRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WaitingUser::class);
    }

    public function save(WaitingUser $waitingUser): void
    {
        $this->_em->persist($waitingUser);
        $this->_em->flush();
    }

    public function remove(WaitingUser $waitingUser): void
    {
        $this->_em->remove($waitingUser);
        $this->_em->flush();
    }

    public function findByUserIdAndSpeakingClubId(UuidInterface $userId, UuidInterface $speakingClubId): ?WaitingUser
    {
        return $this->findOneBy([
            'userId' => $userId,
            'speakingClubId' => $speakingClubId,
        ]);
    }
}
