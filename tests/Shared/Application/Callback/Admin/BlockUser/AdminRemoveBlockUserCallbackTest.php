<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Callback\Admin\BlockUser;

use App\BlockedUser\Domain\BlockedUserRepository;
use App\Tests\Shared\BaseApplicationTest;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use Exception;
use Ramsey\Uuid\Uuid;

class AdminRemoveBlockUserCallbackTest extends BaseApplicationTest
{
    /**
     * @throws Exception
     */
    public function testRemove(): void
    {
        $this->createBlockedUser(
            Uuid::fromString(UserFixtures::USER_ID_JOHN_CONNNOR)
        );
        $blockedUser = $this->createBlockedUser(
            Uuid::fromString(UserFixtures::USER_ID_SARAH_CONNOR)
        );

        $this->sendWebhookCallbackQuery(
            UserFixtures::ADMIN_CHAT_ID,
            123,
            'remove_block:' . $blockedUser->getId()->toString()
        );
        $this->assertResponseIsSuccessful();

        $message = $this->getFirstMessage(UserFixtures::ADMIN_CHAT_ID);

        /** @var BlockedUserRepository $blockUserRepository */
        $blockUserRepository = self::getContainer()->get(BlockedUserRepository::class);
        $user = $blockUserRepository->findById($blockedUser->getId());

        $this->assertNull($user);
        self::assertEquals('Пользователь успешно разблокирован', $message['text']);
    }

    public function testRemoveWhenBlockedUserNotFound(): void
    {
        $this->sendWebhookCallbackQuery(
            UserFixtures::ADMIN_CHAT_ID,
            123,
            'remove_block:' . $this->uuidProvider->provide()
        );
        $this->assertResponseIsSuccessful();

        $message = $this->getFirstMessage(UserFixtures::ADMIN_CHAT_ID);

        self::assertEquals('🤔 Пользователь не заблокирован', $message['text']);
    }
}
