<?php

declare(strict_types=1);

namespace App\Tests\Integration\BlockedUser\Infrastructure\Doctrine\Repository;

use App\BlockedUser\Domain\BlockedUser;
use App\BlockedUser\Domain\BlockedUserRepository;
use App\BlockedUser\Infrastructure\Doctrine\Repository\DoctrineBlockedUserRepository;
use App\Shared\Application\Clock;
use App\Shared\Application\Command\GenericText\AdminGenericTextCommand;
use App\Shared\Application\UuidProvider;
use App\Shared\Domain\TelegramInterface;
use App\Shared\Domain\UserRolesProvider;
use App\Tests\Shared\BaseApplicationTest;
use App\User\Domain\User;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class DoctrineBlockedUserRepositoryTest extends BaseApplicationTest
{
    /**
     * @throws Exception
     */
    public function testSave(): void
    {
        $clock = $this->getContainer()->get(Clock::class);

        $blockedUser = new BlockedUser(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            userId: Uuid::fromString(UserFixtures::USER_ID_1),
            createdAt: $clock->now(),
        );
        /** @var DoctrineBlockedUserRepository $blockedUserRepository */
        $blockedUserRepository = self::getContainer()->get(DoctrineBlockedUserRepository::class);
        $blockedUserRepository->save($blockedUser);

        $savedBlockedUser = $blockedUserRepository->findById($blockedUser->getId());

        $this->assertNotNull($savedBlockedUser);

        $this->assertSame($blockedUser->getId(), $savedBlockedUser->getId());
    }
}
