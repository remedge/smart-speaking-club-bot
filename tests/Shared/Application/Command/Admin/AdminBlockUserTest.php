<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Command\Admin;

use App\Tests\Shared\BaseApplicationTest;
use App\User\Domain\UserRepository;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use Exception;

class AdminBlockUserTest extends BaseApplicationTest
{
    /**
     * @throws Exception
     */
    public function testSuccess(): void
    {
        $this->sendWebhookCommand(UserFixtures::ADMIN_CHAT_ID, 'block_user');
        $this->assertResponseIsSuccessful();

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findByChatId(UserFixtures::ADMIN_CHAT_ID);

        self::assertEquals('RECEIVING_USERNAME_TO_BLOCK', $user->getState()->value);

        $message = $this->getFirstMessage(UserFixtures::ADMIN_CHAT_ID);

        self::assertEquals('📝 Введите username участника(без "@"), которого хотите заблокировать', $message['text']);
        self::assertNull($message['replyMarkup']);
    }
}
