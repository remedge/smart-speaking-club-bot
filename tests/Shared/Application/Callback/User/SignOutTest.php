<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Callback\User;

use App\Tests\Shared\BaseApplicationTest;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use App\WaitList\Domain\WaitingUser;
use App\WaitList\Domain\WaitingUserRepository;
use DateTimeImmutable;
use Exception;
use Ramsey\Uuid\Uuid;

class SignOutTest extends BaseApplicationTest
{
    /**
     * @throws Exception
     */
    public function testSuccessWhenMoreThan24HoursToStart(): void
    {
        $speakingClub = $this->createSpeakingClub(
            date: (new DateTimeImmutable())->modify('+25 hours')->format('Y-m-d H:i:s')
        );

        $this->createParticipation(
            $speakingClub->getId(),
            UserFixtures::USER_ID_1
        );

        /** @var WaitingUserRepository $waitlistRepository */
        $waitlistRepository = self::getContainer()->get(WaitingUserRepository::class);
        $waitlistRepository->save(
            new WaitingUser(
                id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
                userId: Uuid::fromString(UserFixtures::USER_ID_2),
                speakingClubId: $speakingClub->getId(),
            )
        );

        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'sign_out:' . $speakingClub->getId()
        );

        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(self::CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::CHAT_ID, self::MESSAGE_ID);

        self::assertEquals(
            <<<HEREDOC
👌 Вы успешно отписаны от разговорного клуба
HEREDOC,
            $message['text']
        );

        self::assertEquals([
            [
                [
                    'text'          => '<< Перейти к списку ближайших клубов',
                    'callback_data' => 'back_to_list',
                ]
            ],
        ], $message['replyMarkup']);

        $message = $this->getFirstMessage(222222);

        self::assertEquals(
            sprintf(
                'В клубе "%s" %s %s появилось свободное место. Перейдите к описанию клуба, чтобы записаться',
                $speakingClub->getName(),
                $speakingClub->getDate()->format('d.m.Y'),
                $speakingClub->getDate()->format('H:i'),
            ),
            $message['text']
        );

        self::assertEquals([
            [
                [
                    'text'          => 'Перейти к описанию клуба',
                    'callback_data' => 'show_speaking_club:' . $speakingClub->getId()
                ]
            ],
        ], $message['replyMarkup']);
    }

    public function testClubNotFound(): void
    {
        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'sign_out:00000000-0000-0000-0000-000000000001'
        );

        $this->assertArrayHasKey(self::CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::CHAT_ID, self::MESSAGE_ID);

        self::assertEquals(
            <<<HEREDOC
🤔 Разговорный клуб не найден
HEREDOC,
            $message['text']
        );

        self::assertEquals([
            [
                [
                    'text'          => '<< Перейти к списку ближайших клубов',
                    'callback_data' => 'back_to_list',
                ]
            ],
        ], $message['replyMarkup']);
    }

    public function testAlreadySignedOut(): void
    {
        $speakingClub = $this->createSpeakingClub();

        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'sign_out:' . $speakingClub->getId()
        );

        $this->assertArrayHasKey(self::CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::CHAT_ID, self::MESSAGE_ID);

        self::assertEquals(
            <<<HEREDOC
🤔 Вы не записаны на этот клуб
HEREDOC,
            $message['text']
        );

        self::assertEquals([
            [
                [
                    'text'          => '<< Перейти к списку ближайших клубов',
                    'callback_data' => 'back_to_list',
                ]
            ],
        ], $message['replyMarkup']);
    }
}
