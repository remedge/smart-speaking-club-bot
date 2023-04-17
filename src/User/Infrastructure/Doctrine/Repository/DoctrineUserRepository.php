<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Doctrine\Repository;

use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\User;
use App\User\Domain\UserRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class DoctrineUserRepository extends ServiceEntityRepository implements UserRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $user): void
    {
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function findByChatId(int $chatId): ?User
    {
        return parent::findOneBy([
            'chatId' => $chatId,
        ]);
    }

    public function getByChatId(int $chatId): User
    {
        $user = $this->findByChatId($chatId);

        if ($user === null) {
            throw new UserNotFoundException($chatId);
        }

        return $user;
    }

    public function findById(UuidInterface $id): ?User
    {
        return parent::findOneBy([
            'id' => $id,
        ]);
    }
}
