<?php

declare(strict_types=1);

namespace App\Tests\SpeakingClub\Application\Cli;

use App\Shared\Application\Clock;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\SpeakingClub\Presentation\Cli\NotifyUsersAboutCloseClubsCommand;
use App\Tests\Mock\MockTelegram;
use App\Tests\Shared\BaseApplicationTest;
use App\Tests\TestCaseTrait;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use DateTimeImmutable;
use Exception;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class NotifyUsersAboutCloseClubsCommandTest extends BaseApplicationTest
{
    use TestCaseTrait;

    /**
     * @throws Exception
     */
    public function testSuccess(): void
    {
        $application = new Application();
        MockTelegram::$messages = [];

        /** @var SpeakingClubRepository $speakingClubRepository */
        $speakingClubRepository = $this->getContainer()->get(SpeakingClubRepository::class);
        /** @var ParticipationRepository $participationRepository */
        $participationRepository = $this->getContainer()->get(ParticipationRepository::class);

        $speakingClub1 = $this->createSpeakingClub(
            'Test club 1',
            date: (new DateTimeImmutable())->modify('+27 hours')->format('Y-m-d H:i:s')
        );
        $this->createParticipation(
            $speakingClub1->getId(),
            UserFixtures::USER_ID_1
        );

        $speakingClub2 = $this->createSpeakingClub(
            'Test club 2',
            date: (new DateTimeImmutable())->modify('+2 hours')->format('Y-m-d H:i:s')
        );
        $this->createParticipation(
            $speakingClub2->getId(),
            UserFixtures::USER_ID_2
        );

        $this->createSpeakingClub(
            'Test club 3',
            date: (new DateTimeImmutable())->modify('+26 hours 59 minutes 59 seconds')->format('Y-m-d H:i:s')
        );
        $this->createSpeakingClub(
            'Test club 4',
            date: (new DateTimeImmutable())->modify('+1 hours  59 minutes 59 seconds')->format('Y-m-d H:i:s')
        );

        $application->add(
            new NotifyUsersAboutCloseClubsCommand(
                speakingClubRepository: $speakingClubRepository,
                participationRepository: $participationRepository,
                clock: $this->getContainer()->get(Clock::class),
                telegram: $this->getContainer()->get(TelegramInterface::class),
            )
        );

        $command = $application->find('app:notify-users');
        $commandTester = new CommandTester($command);

        $result = $commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $result);

        $this->assertArrayHasKey(self::CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::CHAT_ID);

        self::assertEquals(
            'Разговорный клуб "Test club 1" начнется через 27 часов. Если у вас не получается прийти, пожалуйста, отмените вашу запись, чтобы мы предложили ваше место другим.',
            $messages[0]['text']
        );

        $this->assertArrayHasKey(222222, $this->getMessages());
        $messages = $this->getMessagesByChatId(222222);

        self::assertEquals(
            'Разговорный клуб "Test club 2" начнется через 2 часа. Если у вас не получается прийти, пожалуйста, отмените вашу запись, чтобы мы предложили ваше место другим.',
            $messages[0]['text']
        );
    }
}
