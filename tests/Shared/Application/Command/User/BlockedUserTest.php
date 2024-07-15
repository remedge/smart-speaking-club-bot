<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Command\User;

use App\Tests\Shared\BaseApplicationTest;
use App\User\Domain\UserRepository;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use JsonException;

class BlockedUserTest extends BaseApplicationTest
{
    /**
     * @throws JsonException
     */
    public function testWebhook(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findByChatId(UserFixtures::USER_CHAT_ID_SARAH_CONNOR);

        $this->createBlockedUser($user->getId());

        $commands = [
            'start',
            'help',
            'my_upcoming_clubs',
            'upcoming_clubs',
            'show_speaking_club',
            'show_club_separated',
            'show_my_speaking_club',
            'sign_in',
            'sign_in_plus_one',
            'sign_out',
        ];
        $this->sendWebhookCommand(UserFixtures::USER_CHAT_ID_SARAH_CONNOR, $commands[array_rand($commands)]);
        $this->assertResponseIsSuccessful();
        $message = $this->getFirstMessage(UserFixtures::USER_CHAT_ID_SARAH_CONNOR);

        self::assertEquals(
            'Ваш доступ к записи на разговорные клубы временно ограничен. Если вы считаете, что это произошло по ошибке, пожалуйста, свяжитесь с нами для решения этого вопроса @NoviSad_Smartlab',
            $message['text']
        );
        self::assertNull($message['replyMarkup']);
    }
}
