<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Command\Admin;

use App\Tests\Shared\BaseApplicationTest;
use DateTimeImmutable;
use Exception;

class AdminListUpcomingSpeakingClubsTest extends BaseApplicationTest
{
    public function testEmpty(): void
    {
        $this->sendWebhookCommand(666666, 'admin_upcoming_clubs');
        $this->assertResponseIsSuccessful();
        $message = $this->getFirstMessage(666666);

        self::assertEquals('Пока не запланировано ни одного клуба', $message['text']);
        self::assertEquals([], $message['replyMarkup']);
    }

    /**
     * @throws Exception
     */
    public function testSuccess(): void
    {
        $speakingClub1 = $this->createSpeakingClub(name: 'Test club 1');
        $speakingClub2 = $this->createSpeakingClub(name: 'Test club 2');
        $this->createSpeakingClub(name: 'Test club 3', isCancelled: true);
        $this->createSpeakingClub(
            name: 'Test club 4',
            date: (new DateTimeImmutable(
                '-1 minute'
            ))->format('Y-m-d H:i:s')
        );

        $this->sendWebhookCommand(666666, 'admin_upcoming_clubs');
        $this->assertResponseIsSuccessful();
        $message = $this->getFirstMessage(666666);

        self::assertEquals(
            'Список ближайших разговорных клубов и других мероприятий школы. Нажмите на один из них, чтобы увидеть подробную информацию',
            $message['text']
        );
        self::assertEquals([
            [
                [
                    'text' => sprintf(
                        '%s %s - %s',
                        $speakingClub1->getDate()->format('d.m'),
                        $speakingClub1->getDate()->format('H:i'),
                        $speakingClub1->getName()
                    ),
                    'callback_data' => 'admin_show_speaking_club:' . $speakingClub1->getId(),
                ],
            ],
            [
                [
                    'text' => sprintf(
                        '%s %s - %s',
                        $speakingClub2->getDate()->format('d.m'),
                        $speakingClub2->getDate()->format('H:i'),
                        $speakingClub2->getName()
                    ),
                    'callback_data' => 'admin_show_speaking_club:' . $speakingClub2->getId(),
                ],
            ],
        ], $message['replyMarkup']);
    }
}
