<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Callback\Admin;

use App\SpeakingClub\Domain\ParticipationRepository;
use App\Tests\Shared\BaseApplicationTest;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use App\WaitList\Domain\WaitingUser;
use App\WaitList\Domain\WaitingUserRepository;
use Exception;
use Ramsey\Uuid\Uuid;

class AdminRemoveParticipantTest extends BaseApplicationTest
{
    /**
     * @throws Exception
     */
    public function testSuccess(): void
    {
        $speakingClub = $this->createSpeakingClub();

        $participation = $this->createParticipation(
            $speakingClub->getId(),
            UserFixtures::USER_ID_JOHN_CONNNOR
        );

        /** @var WaitingUserRepository $waitingUserRepository */
        $waitingUserRepository = self::getContainer()->get(WaitingUserRepository::class);
        $waitingUserRepository->save(new WaitingUser(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            userId: Uuid::fromString(UserFixtures::USER_ID_SARAH_CONNOR),
            speakingClubId: $speakingClub->getId(),
        ));

        $this->sendWebhookCallbackQuery(666666, 123, 'remove_participant:00000000-0000-0000-0000-000000000001');
        $this->assertResponseIsSuccessful();

        /** @var ParticipationRepository $participationRepository */
        $participationRepository = self::getContainer()->get(ParticipationRepository::class);

        self::assertNull($participationRepository->findById($participation->getId()));

        $message = $this->getMessage(666666, 123);

        self::assertEquals('Пользователь успешно удален из списка участников', $message['text']);
        self::assertEquals([
            [[
                'text' => '<< Вернуться к списку участников',
                'callback_data' => 'show_participants:' . $speakingClub->getId(),
            ]],
        ], $message['replyMarkup']);

        $message = $this->getFirstMessage(111111);

        self::assertEquals(
            sprintf(
                'Администратор убрал вас из участников клуба "%s" %s %s',
                $speakingClub->getName(),
                $speakingClub->getDate()->format('d.m.Y'),
                $speakingClub->getDate()->format('H:i'),
            ),
            $message['text']
        );
        self::assertEquals([
            [[
                'text' => 'Посмотреть список ближайших клубов',
                'callback_data' => 'back_to_list',
            ]],
        ], $message['replyMarkup']);

        $message = $this->getFirstMessage(222222);
        self::assertEquals(
            sprintf(
                'Появилось свободное место в клубе "%s" %s %s, спешите записаться!',
                $speakingClub->getName(),
                $speakingClub->getDate()->format('d.m.Y'),
                $speakingClub->getDate()->format('H:i'),
            ),
            $message['text']
        );

        self::assertEquals([
            [[
                'text' => 'Посмотреть информацию о клубе',
                'callback_data' => 'show_speaking_club:' . $speakingClub->getId(),
            ]],
        ], $message['replyMarkup']);
    }

    public function testUnknownParticipant(): void
    {
        $this->sendWebhookCallbackQuery(666666, 123, 'remove_participant:00000000-0000-0000-0000-000000000001');
        $this->assertResponseIsSuccessful();

        $message = $this->getFirstMessage(666666);

        self::assertEquals('Участник не найден', $message['text']);
        self::assertEquals([
            [[
                'text' => '<< Вернуться к списку клубов',
                'callback_data' => 'back_to_admin_list',
            ]],
        ], $message['replyMarkup']);
    }

    public function testUnknownClub(): void
    {
        $this->sendWebhookCallbackQuery(666666, 123, 'remove_participant:00000000-0000-0000-0000-000000000001');
        $this->assertResponseIsSuccessful();

        $message = $this->getFirstMessage(666666);

        self::assertEquals('Участник не найден', $message['text']);
        self::assertEquals([
            [[
                'text' => '<< Вернуться к списку клубов',
                'callback_data' => 'back_to_admin_list',
            ]],
        ], $message['replyMarkup']);
    }
}
