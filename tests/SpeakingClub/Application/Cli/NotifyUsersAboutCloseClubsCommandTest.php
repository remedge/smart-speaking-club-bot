<?php

declare(strict_types=1);

namespace App\Tests\SpeakingClub\Application\Cli;

use App\Shared\Application\Clock;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\SpeakingClub\Presentation\Cli\NotifyUsersAboutCloseClubsCommand;
use App\Tests\Mock\MockTelegram;
use App\Tests\TestCaseTrait;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class NotifyUsersAboutCloseClubsCommandTest extends KernelTestCase
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
            '00000000-0000-0000-0000-000000000001',
            'Test club 1',
            '2000-01-02 03:00:00'
        );
        $this->createParticipation(
            $speakingClub1->getId()->toString(),
            '00000000-0000-0000-0000-000000000001',
            UserFixtures::USER_ID_1
        );

        $speakingClub2 = $this->createSpeakingClub(
            '00000000-0000-0000-0000-000000000002',
            'Test club 2',
            '2000-01-01 02:00:00'
        );
        $this->createParticipation(
            $speakingClub2->getId()->toString(),
            '00000000-0000-0000-0000-000000000002',
            UserFixtures::USER_ID_2
        );

        $this->createSpeakingClub(
            '00000000-0000-0000-0000-000000000003',
            'Test club 3',
            '2000-01-02 02:59:59'
        );
        $this->createSpeakingClub(
            '00000000-0000-0000-0000-000000000004',
            'Test club 4',
            '2000-01-01 03:00:00'
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

        $messages = array_key_exists(111111, MockTelegram::$messages) ? MockTelegram::$messages[111111] : null;
        self::assertEquals(
            'Разговорный клуб "Test club 1" начнется через 27 часов. Если у вас не получается прийти, пожалуйста, отмените вашу запись, чтобы мы предложили ваше место другим.',
            $messages[0]['text']
        );

        $messages = array_key_exists(222222, MockTelegram::$messages) ? MockTelegram::$messages[222222] : null;
        self::assertEquals(
            'Разговорный клуб "Test club 2" начнется через 2 часа. Если у вас не получается прийти, пожалуйста, отмените вашу запись, чтобы мы предложили ваше место другим.',
            $messages[0]['text']
        );
    }
}
