<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Command\Admin;

use App\Tests\Shared\BaseApplicationTest;
use App\User\Domain\UserRepository;

class InitClubCreationTest extends BaseApplicationTest
{
    public function testSuccess(): void
    {
        $this->sendWebhookCommand(666666, 'admin_create_club');
        $this->assertResponseIsSuccessful();
        $message = $this->getFirstMessage(666666);

        self::assertEquals('Введите название клуба', $message['text']);

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findByChatId(666666);

        self::assertEquals('RECEIVING_NAME_FOR_CREATING', $user->getState()->value);
    }
}
