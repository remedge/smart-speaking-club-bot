<?php

declare(strict_types=1);

namespace App\Tests\SpeakingClub\Application\Cli;

use App\SpeakingClub\Presentation\Cli\ArchivePastSpeakingClubsCommand;
use App\SpeakingClub\Presentation\Cli\DumpFactory;
use App\Tests\Mock\MockTelegram;
use App\Tests\Shared\BaseApplicationTest;
use App\Tests\TestCaseTrait;
use App\Tests\WithConsecutive;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use DateTimeImmutable;
use Exception;
use Google\Service\Sheets\Resource\SpreadsheetsValues;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ArchivePastSpeakingClubsCommandTest extends BaseApplicationTest
{
    use TestCaseTrait;

    /**
     * @throws Exception
     */
    public function testSuccess(): void
    {
        $application = new Application();
        MockTelegram::$messages = [];

        $speakingClubRepository = $this->getSpeakingClubRepository();
        $participationRepository = $this->getParticipationRepository();
        $waitingUserRepository = $this->getWaitingUserRepository();

        $speakingClub1 = $this->createSpeakingClub(
            name: 'Test Club 1',
            date: (new DateTimeImmutable('-1 hour'))->format('Y-m-d H:i:s'),
        );
        $this->createParticipation(
            $speakingClub1->getId(),
            UserFixtures::USER_ID_1
        );

        $this->createSpeakingClub(
            name: 'Test Club 2',
            date: (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s')
        );

        $speakingClub3 = $this->createSpeakingClub(
            name: 'Test Club 3',
            date: (new DateTimeImmutable('-1 minute'))->format('Y-m-d H:i:s')
        );
        $this->createParticipation(
            $speakingClub3->getId(),
            UserFixtures::USER_ID_3
        );
        $this->createParticipation(
            $speakingClub3->getId(),
            UserFixtures::USER_ID_2
        );

        $this->createSpeakingClub(
            date: (new DateTimeImmutable('-3 days'))->format('Y-m-d H:i:s'),
            isArchived: true
        );
        $this->createSpeakingClub(
            date: (new DateTimeImmutable('-2 days'))->format('Y-m-d H:i:s'),
            isCancelled: true
        );

        $spreadsheetId = 'some spreadsheet id';
        $range = 'some range';

        $dumpClient = $this->createMock(Google_Client::class);

        $dumpServiceSheetsValueRange = $this->createMock(Google_Service_Sheets_ValueRange::class);
        $dumpServiceSheetsValueRange
            ->expects(self::exactly(2))
            ->method('setValues')
            ->with(
                ...
                WithConsecutive::create(
                    [
                        [
                            [
                                $speakingClub1->getDate()->format('d.m.Y H:i'),
                                'Test Club 1',
                                'Test Description',
                                5,
                                10,
                                1,
                                'john_connor',
                                '',
                                0,
                            ]
                        ]
                    ],
                    [
                        [
                            [
                                $speakingClub3->getDate()->format('d.m.Y H:i'),
                                'Test Club 3',
                                'Test Description',
                                5,
                                10,
                                2,
                                'ed_traxler, sarah_connor',
                                '',
                                0,
                            ]
                        ]
                    ],
                )
            );

        $dumpServiceSheets = $this->createMock(Google_Service_Sheets::class);
        $dumpServiceSheets->spreadsheets_values = $this->createMock(SpreadsheetsValues::class);

        $dumpFactory = $this->createMock(DumpFactory::class);
        $dumpFactory
            ->method('createClient')
            ->willReturn($dumpClient);
        $dumpFactory
            ->method('createServiceSheetsValueRange')
            ->willReturn($dumpServiceSheetsValueRange);
        $dumpFactory
            ->method('createServiceSheets')
            ->with($dumpClient)
            ->willReturn($dumpServiceSheets);

        $application->add(
            new ArchivePastSpeakingClubsCommand(
                $speakingClubRepository,
                $participationRepository,
                $waitingUserRepository,
                $this->clock,
                $dumpFactory,
                $spreadsheetId,
                $range,
            )
        );

        $command = $application->find('app:archive-clubs');
        $commandTester = new CommandTester($command);

        $result = $commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $result);
    }
}
