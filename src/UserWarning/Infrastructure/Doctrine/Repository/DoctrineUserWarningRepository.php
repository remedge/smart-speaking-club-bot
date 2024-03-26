<?php

declare(strict_types=1);

namespace App\UserWarning\Infrastructure\Doctrine\Repository;

use App\User\Domain\User;
use App\UserWarning\Domain\UserWarning;
use App\UserWarning\Domain\UserWarningRepository;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<UserWarning>
 */
class DoctrineUserWarningRepository extends ServiceEntityRepository implements UserWarningRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserWarning::class);
    }

    public function save(UserWarning $userWarning): void
    {
        $this->_em->persist($userWarning);
        $this->_em->flush();
    }

    public function remove(UserWarning $userWarning): void
    {
        $this->_em->remove($userWarning);
        $this->_em->flush();
    }

    public function findById(UuidInterface $id): ?UserWarning
    {
        return $this->find($id);
    }

    public function findAllWarning(): ?array
    {
        return $this->createQueryBuilder('uw')
            ->select('uw.id, u.username, u.chatId, u.firstName, u.lastName, uw.createdAt')
            ->join(User::class, 'u', 'WITH', 'uw.userId = u.id')
            ->getQuery()
            ->getResult();
    }

    public function findUserUpcoming(UuidInterface $userId, DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('user_warning')
            ->andWhere('user_warning.createdAt > :now')
            ->andWhere('user_warning.userId = :user')
            ->setParameter('now', $now)
            ->setParameter('user', $userId)
            ->orderBy('user_warning.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
