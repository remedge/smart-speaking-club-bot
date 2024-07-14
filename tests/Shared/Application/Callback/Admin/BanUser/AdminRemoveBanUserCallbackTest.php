<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Callback\Admin\BanUser;

use App\Tests\Shared\BaseApplicationTest;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use App\UserBan\Domain\UserBanRepository;
use DateTimeImmutable;
use Exception;
use Ramsey\Uuid\Uuid;

class AdminRemoveBanUserCallbackTest extends BaseApplicationTest
{
    /**
     * @throws Exception
     */
    public function testSuccess(): void
    {
        $this->createBannedUser(
            Uuid::fromString(UserFixtures::USER_ID_JOHN_CONNNOR),
            new DateTimeImmutable('-1 minute')
        );
        $bannedUser = $this->createBannedUser(
            Uuid::fromString(UserFixtures::USER_ID_SARAH_CONNOR),
            new DateTimeImmutable('+3 days')
        );

        $this->sendWebhookCallbackQuery(
            UserFixtures::ADMIN_CHAT_ID,
            123,
            'remove_ban:' . $bannedUser->getId()->toString()
        );
        $this->assertResponseIsSuccessful();

        $message = $this->getFirstMessage(UserFixtures::ADMIN_CHAT_ID);

        /** @var UserBanRepository $userBanRepository */
        $userBanRepository = self::getContainer()->get(UserBanRepository::class);
        $user = $userBanRepository->findById($bannedUser->getId());

        $this->assertNull($user);
        self::assertEquals('Пользователь успешно разбанен', $message['text']);
    }

    public function testWhenUserBanNotFound(): void
    {
        $this->sendWebhookCallbackQuery(
            UserFixtures::ADMIN_CHAT_ID,
            123,
            'remove_ban:' . $this->uuidProvider->provide()
        );
        $this->assertResponseIsSuccessful();

        $message = $this->getFirstMessage(UserFixtures::ADMIN_CHAT_ID);

        self::assertEquals('🤔 Пользователь не забанен', $message['text']);
    }
}
