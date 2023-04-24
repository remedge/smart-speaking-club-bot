<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Callback\Admin;

use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\Tests\Shared\BaseApplicationTest;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

class AdminAddParticipantTest extends BaseApplicationTest
{
    public function testSuccess(): void
    {
        /** @var SpeakingClubRepository $clubRepository */
        $clubRepository = self::getContainer()->get(SpeakingClubRepository::class);
        $clubRepository->save(new SpeakingClub(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            name: 'Test Club',
            description: 'Test Description',
            maxParticipantsCount: 10,
            date: new DateTimeImmutable('2021-01-01 12:00'),
        ));

        $this->sendWebhookCallbackQuery(666666, 123, 'admin_add_participant:00000000-0000-0000-0000-000000000001');
        $this->assertResponseIsSuccessful();

        $message = $this->getMessage(666666, 123);

        self::assertEquals('Введите username участника, которого хотите добавить в разговорный клуб', $message['text']);
        self::assertEquals([], $message['replyMarkup']);
    }

    public function testClubNotFound(): void
    {
        $this->sendWebhookCallbackQuery(666666, 123, 'admin_add_participant:00000000-0000-0000-0000-000000000001');
        $this->assertResponseIsSuccessful();

        $message = $this->getMessage(666666, 123);

        self::assertEquals('🤔 Разговорный клуб не найден', $message['text']);
        self::assertEquals([
            [[
                'text' => 'Вернуться к списку',
                'callback_data' => 'back_to_admin_list',
            ]],
        ], $message['replyMarkup']);
    }
}
