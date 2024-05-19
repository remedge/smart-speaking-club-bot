<?php

declare(strict_types=1);

namespace App\Tests\WaitList\Application\User;

use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\Tests\Shared\BaseApplicationTest;
use App\WaitList\Domain\WaitingUser;
use App\WaitList\Domain\WaitingUserRepository;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

class JoinWaitingListTest extends BaseApplicationTest
{
    public function testSuccess(): void
    {
        /** @var SpeakingClubRepository $clubRepository */
        $clubRepository = self::getContainer()->get(SpeakingClubRepository::class);
        $clubRepository->save(new SpeakingClub(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            name: 'Test club 1',
            description: 'Test description 1',
            minParticipantsCount: 1,
            maxParticipantsCount: 1,
            date: new DateTimeImmutable('2000-01-01 11:11'),
        ));

        $this->sendWebhookCallbackQuery(111111, 123, 'join_waiting_list:00000000-0000-0000-0000-000000000001');
        
        $this->assertArrayHasKey(self::KYLE_REESE_CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::KYLE_REESE_CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::KYLE_REESE_CHAT_ID, self::MESSAGE_ID);

        self::assertEquals('Вы успешно добавлены в список ожидания, я сообщу вам, когда появится свободное место', $message['text']);
        self::assertEquals([
            [[
                'text' => 'Перейти к списку ближайших клубов',
                'callback_data' => 'back_to_list',
            ]],
        ], $message['replyMarkup']);
    }

    public function testAlreadyInWaitingList(): void
    {
        /** @var SpeakingClubRepository $clubRepository */
        $clubRepository = self::getContainer()->get(SpeakingClubRepository::class);
        $clubRepository->save(new SpeakingClub(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            name: 'Test club 1',
            description: 'Test description 1',
            minParticipantsCount: 1,
            maxParticipantsCount: 1,
            date: new DateTimeImmutable('2000-01-01 11:11'),
        ));

        /** @var WaitingUserRepository $waitingUserRepository */
        $waitingUserRepository = self::getContainer()->get(WaitingUserRepository::class);
        $waitingUserRepository->save(new WaitingUser(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            userId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
        ));

        $this->sendWebhookCallbackQuery(111111, 123, 'join_waiting_list:00000000-0000-0000-0000-000000000001');
        
        $this->assertArrayHasKey(self::KYLE_REESE_CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::KYLE_REESE_CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::KYLE_REESE_CHAT_ID, self::MESSAGE_ID);

        self::assertEquals('Вы уже находитесь в списке ожидания', $message['text']);
        self::assertEquals([
            [[
                'text' => 'Перейти к списку ближайших клубов',
                'callback_data' => 'back_to_list',
            ]],
        ], $message['replyMarkup']);
    }
}
