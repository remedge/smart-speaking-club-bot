<?php

declare(strict_types=1);

namespace App\UserBan\Infrastructure\Doctrine\Repository;

use App\User\Domain\User;
use App\UserBan\Domain\UserBan;
use App\UserBan\Domain\UserBanRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;
use DateTimeImmutable;

/**
 * @extends ServiceEntityRepository<UserBan>
 */
class DoctrineUserBanRepository extends ServiceEntityRepository implements UserBanRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBan::class);
    }

    public function save(UserBan $userBan): void
    {
        $this->_em->persist($userBan);
        $this->_em->flush();
    }

    public function remove(UserBan $userBan): void
    {
        $this->_em->remove($userBan);
        $this->_em->flush();
    }

    public function findById(UuidInterface $id): ?UserBan
    {
        return $this->find($id);
    }

    public function findByUserId(UuidInterface $userId, DateTimeImmutable $now): ?array
    {
        return $this->createQueryBuilder('ub')
            ->andWhere('ub.user_id = :userId')
            ->andWhere('ub.end_date > :now')
            ->setParameter('userId', $userId)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllBan(): ?array
    {
        return $this->createQueryBuilder('ub')
            ->select('ub.id, u.username, u.chatId, u.firstName, u.lastName, ub.endDate')
            ->join(User::class, 'u', 'WITH', 'ub.userId = u.id')
            ->getQuery()
            ->getResult();
    }
}
