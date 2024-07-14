<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Callback\Admin\BanUser;

use App\Tests\Shared\BaseApplicationTest;
use App\User\Domain\UserRepository;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use Exception;

class AdminBanUserCallbackTest extends BaseApplicationTest
{
    /**
     * @throws Exception
     */
    public function testSuccess(): void
    {
        $this->sendWebhookCallbackQuery(UserFixtures::ADMIN_CHAT_ID, 123, 'add_ban');
        $this->assertResponseIsSuccessful();

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findByChatId(UserFixtures::ADMIN_CHAT_ID);

        self::assertEquals('RECEIVING_ADD_BAN', $user->getState()->value);

        $message = $this->getFirstMessage(UserFixtures::ADMIN_CHAT_ID);

        self::assertEquals('📝 Введите никнейм участника для бана', $message['text']);
    }
}
