<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Callback\User;

use App\SpeakingClub\Domain\Participation;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\Tests\Shared\BaseApplicationTest;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use DateTimeImmutable;
use Exception;
use Ramsey\Uuid\Uuid;

class ShowSpeakingClubTest extends BaseApplicationTest
{
    /**
     * @throws Exception
     */
    public function testShowClubWithNoParticipation(): void
    {
        /** @var SpeakingClubRepository $clubRepository */
        $clubRepository = self::getContainer()->get(SpeakingClubRepository::class);
        $clubRepository->save(new SpeakingClub(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            name: 'Test Club',
            description: 'Test Description',
            minParticipantsCount: 5,
            maxParticipantsCount: 10,
            date: new DateTimeImmutable('2021-01-01 12:00'),
        ));

        $this->sendWebhookCallbackQuery(111111, 123, 'show_speaking_club:00000000-0000-0000-0000-000000000001');
        $this->assertResponseIsSuccessful();
        $message = $this->getMessage(111111, 123);

        self::assertEquals(<<<HEREDOC
Название: Test Club
Описание: Test Description
Дата: 01.01.2021 12:00
Минимальное количество участников: 5
Максимальное количество участников: 10
Записалось участников: 0

Вы не записаны

HEREDOC, $message['text']);

        self::assertEquals([
            [[
                'text' => 'Записаться',
                'callback_data' => 'sign_in:00000000-0000-0000-0000-000000000001',
            ]],
            [[
                'text' => 'Записаться с +1 человеком',
                'callback_data' => 'sign_in_plus_one:00000000-0000-0000-0000-000000000001',
            ]],
            [[
                'text' => '<< Вернуться к списку клубов',
                'callback_data' => 'back_to_list',
            ]],
        ], $message['replyMarkup']);
    }

    public function testShowClubWithSingleParticipation(): void
    {
        /** @var SpeakingClubRepository $clubRepository */
        $clubRepository = self::getContainer()->get(SpeakingClubRepository::class);
        $clubRepository->save(new SpeakingClub(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            name: 'Test Club',
            description: 'Test Description',
            minParticipantsCount: 5,
            maxParticipantsCount: 10,
            date: new DateTimeImmutable('2021-01-01 12:00'),
        ));

        /** @var ParticipationRepository $participationRepository */
        $participationRepository = self::getContainer()->get(ParticipationRepository::class);
        $participationRepository->save(new Participation(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            userId: Uuid::fromString(UserFixtures::USER_ID_1),
            speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            isPlusOne: false,
        ));

        $this->sendWebhookCallbackQuery(111111, 123, 'show_speaking_club:00000000-0000-0000-0000-000000000001');
        $this->assertResponseIsSuccessful();
        $message = $this->getMessage(111111, 123);

        self::assertEquals(<<<HEREDOC
Название: Test Club
Описание: Test Description
Дата: 01.01.2021 12:00
Минимальное количество участников: 5
Максимальное количество участников: 10
Записалось участников: 1

Вы записаны

HEREDOC, $message['text']);

        self::assertEquals([
            [[
                'text' => 'Отменить запись',
                'callback_data' => 'sign_out:00000000-0000-0000-0000-000000000001',
            ]],
            [[
                'text' => 'Добавить +1 человека с собой',
                'callback_data' => 'add_plus_one:00000000-0000-0000-0000-000000000001',
            ]],
            [[
                'text' => '<< Вернуться к списку клубов',
                'callback_data' => 'back_to_list',
            ]],
        ], $message['replyMarkup']);
    }

    public function testShowClubWithPlusOneParticipation(): void
    {
        /** @var SpeakingClubRepository $clubRepository */
        $clubRepository = self::getContainer()->get(SpeakingClubRepository::class);
        $clubRepository->save(new SpeakingClub(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            name: 'Test Club',
            description: 'Test Description',
            minParticipantsCount: 5,
            maxParticipantsCount: 10,
            date: new DateTimeImmutable('2021-01-01 12:00'),
        ));

        /** @var ParticipationRepository $participationRepository */
        $participationRepository = self::getContainer()->get(ParticipationRepository::class);
        $participationRepository->save(new Participation(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            userId: Uuid::fromString(UserFixtures::USER_ID_1),
            speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            isPlusOne: true,
        ));

        $this->sendWebhookCallbackQuery(111111, 123, 'show_speaking_club:00000000-0000-0000-0000-000000000001');
        $this->assertResponseIsSuccessful();
        $message = $this->getMessage(111111, 123);

        self::assertEquals(<<<HEREDOC
Название: Test Club
Описание: Test Description
Дата: 01.01.2021 12:00
Минимальное количество участников: 5
Максимальное количество участников: 10
Записалось участников: 2

Вы записаны с +1 человеком

HEREDOC, $message['text']);

        self::assertEquals([
            [[
                'text' => 'Отменить запись',
                'callback_data' => 'sign_out:00000000-0000-0000-0000-000000000001',
            ]],
            [[
                'text' => 'Убрать +1 человека с собой',
                'callback_data' => 'remove_plus_one:00000000-0000-0000-0000-000000000001',
            ]],
            [[
                'text' => '<< Вернуться к списку клубов',
                'callback_data' => 'back_to_list',
            ]],
        ], $message['replyMarkup']);
    }
}
