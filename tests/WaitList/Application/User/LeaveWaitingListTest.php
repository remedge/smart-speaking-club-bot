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

class LeaveWaitingListTest extends BaseApplicationTest
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
        /** @var WaitingUserRepository $waitUserRepository */
        $waitUserRepository = self::getContainer()->get(WaitingUserRepository::class);
        $waitUserRepository->save(new WaitingUser(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            userId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
        ));

        $this->sendWebhookCallbackQuery(111111, 123, 'leave_waiting_list:00000000-0000-0000-0000-000000000001');
        
        $this->assertArrayHasKey(self::CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::CHAT_ID, self::MESSAGE_ID);

        self::assertEquals('👌 Вы успешно вышли из списке ожидания', $message['text']);
        self::assertEquals([
            [[
                'text' => 'Перейти к списку ближайших клубов',
                'callback_data' => 'back_to_list',
            ]],
        ], $message['replyMarkup']);
    }

    public function testNotInWaitList(): void
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

        $this->sendWebhookCallbackQuery(111111, 123, 'leave_waiting_list:00000000-0000-0000-0000-000000000001');
        
        $this->assertArrayHasKey(self::CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::CHAT_ID, self::MESSAGE_ID);

        self::assertEquals('🤔 Вы не находитесь в списке ожидания этого клуба', $message['text']);
        self::assertEquals([
            [[
                'text' => 'Перейти к списку ближайших клубов',
                'callback_data' => 'back_to_list',
            ]],
        ], $message['replyMarkup']);
    }
}
