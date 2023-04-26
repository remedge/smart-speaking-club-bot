<?php

declare(strict_types=1);

namespace App\Tests\SpeakingClub\Application\Cli;

use App\Shared\Application\Clock;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\Participation;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\SpeakingClub\Presentation\Cli\NotifyUsersAboutCloseClubsCommand;
use App\Tests\Mock\MockTelegram;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class NotifyUsersAboutCloseClubsCommandTest extends KernelTestCase
{
    public function testSuccess(): void
    {
        $application = new Application();
        MockTelegram::$messages = [];

        /** @var SpeakingClubRepository $speakingClubRepository */
        $speakingClubRepository = $this->getContainer()->get(SpeakingClubRepository::class);
        /** @var ParticipationRepository $participationRepository */
        $participationRepository = $this->getContainer()->get(ParticipationRepository::class);

        $speakingClubRepository->save(new SpeakingClub(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            name: 'Test club 1',
            description: 'Test description',
            maxParticipantsCount: 10,
            date: new DateTimeImmutable('2000-01-02 00:00:00'),
        ));
        $participationRepository->save(new Participation(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            userId: Uuid::fromString(UserFixtures::USER_ID_1),
            speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            isPlusOne: false,
        ));

        $speakingClubRepository->save(new SpeakingClub(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000002'),
            name: 'Test club 2',
            description: 'Test description',
            maxParticipantsCount: 10,
            date: new DateTimeImmutable('2000-01-01 02:00:00'),
        ));
        $participationRepository->save(new Participation(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000002'),
            userId: Uuid::fromString(UserFixtures::USER_ID_2),
            speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000002'),
            isPlusOne: false,
        ));

        $application->add(new NotifyUsersAboutCloseClubsCommand(
            speakingClubRepository: $speakingClubRepository,
            participationRepository: $participationRepository,
            clock: $this->getContainer()->get(Clock::class),
            telegram: $this->getContainer()->get(TelegramInterface::class),
        ));

        $command = $application->find('app:notify-users');
        $commandTester = new CommandTester($command);

        $result = $commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $result);

        $messages = array_key_exists(111111, MockTelegram::$messages) ? MockTelegram::$messages[111111] : null;
        self::assertEquals('Разговорный клуб "Test club 1" начнется через 24 часа', $messages[0]['text']);

        $messages = array_key_exists(222222, MockTelegram::$messages) ? MockTelegram::$messages[222222] : null;
        self::assertEquals('Разговорный клуб "Test club 2" начнется через 2 часа', $messages[0]['text']);
    }
}
