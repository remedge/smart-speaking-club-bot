<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Infrastructure\Doctrine\Repository;

use App\Tests\Shared\BaseApplicationTest;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use App\User\Infrastructure\Doctrine\Repository\DoctrineUserRepository;
use Exception;

class DoctrineUserRepositoryTest extends BaseApplicationTest
{
    /**
     * @throws Exception
     */
    public function testFindAllExceptUsernames(): void
    {
        /** @var DoctrineUserRepository $userRepository */
        $userRepository = self::getContainer()->get(DoctrineUserRepository::class);
        $users = $userRepository->findAllExceptUsernames(['sarah_connor']);

        $this->assertCount(2, $users);

        $this->assertSame(UserFixtures::USER_ID_JOHN_CONNNOR, $users[0]->getId()->toString());
        $this->assertSame(UserFixtures::ADMIN_ID, $users[1]->getId()->toString());
    }
}
