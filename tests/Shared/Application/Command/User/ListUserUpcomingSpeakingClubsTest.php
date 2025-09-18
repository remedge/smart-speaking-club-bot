<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Command\User;

use App\System\DateHelper;
use App\Tests\Shared\BaseApplicationTest;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;

class ListUserUpcomingSpeakingClubsTest extends BaseApplicationTest
{
    public function testEmpty(): void
    {
        $this->sendWebhookCommand(111111, 'my_upcoming_clubs');
        $this->assertResponseIsSuccessful();
        $message = $this->getFirstMessage(111111);

        self::assertEquals(
            'Вы не записаны ни на один клуб. Выберите клуб из списка, чтобы записаться.',
            $message['text']
        );
        self::assertEquals([
            [
                [
                    'text'          => 'Перейти к списку ближайших клубов',
                    'callback_data' => 'back_to_list',
                ]
            ],
        ], $message['replyMarkup']);
    }

    /**
     * @throws \Exception
     */
    public function testExistingUpcoming(): void
    {
        $speakingClub1 = $this->createSpeakingClub(name: 'Test club 1');
        $speakingClub2 = $this->createSpeakingClub(name: 'Test club 2', isCancelled: true);

        $this->createParticipation(
            $speakingClub1->getId(),
            UserFixtures::USER_ID_JOHN_CONNNOR
        );
        $this->createParticipation(
            $speakingClub2->getId(),
            UserFixtures::USER_ID_JOHN_CONNNOR
        );

        $this->sendWebhookCommand(111111, 'my_upcoming_clubs');
        $this->assertResponseIsSuccessful();
        $message = $this->getFirstMessage(111111);

        self::assertEquals('Список ближайших клубов, куда вы записаны:', $message['text']);
        self::assertEquals([
            [
                [
                    'text'          => sprintf(
                        '%s %s - %s',
                        $speakingClub1->getDate()->format('d.m'),
                        $speakingClub1->getDate()->format('H:i') . ' ' . DateHelper::getDayOfTheWeek(
                            $speakingClub1->getDate()->format('d.m.Y')
                        ),
                        $speakingClub1->getName()
                    ),
                    'callback_data' => 'show_my_speaking_club:' . $speakingClub1->getId(),
                ]
            ],
        ], $message['replyMarkup']);
    }
}
