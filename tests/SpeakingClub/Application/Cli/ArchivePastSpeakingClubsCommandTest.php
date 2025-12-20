<?php

declare(strict_types=1);

namespace App\Tests\SpeakingClub\Application\Cli;

use App\Shared\Application\Clock;
use App\Shared\Infrastructure\GoogleSheets\GoogleSheetsServiceFactory;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\SpeakingClub\Presentation\Cli\ArchivePastSpeakingClubsCommand;
use App\System\DateHelper;
use App\Tests\Shared\BaseApplicationTest;
use App\Tests\TestCaseTrait;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use App\WaitList\Domain\WaitingUserRepository;
use DateTimeImmutable;
use Exception;
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
    public function testSuccessWithPlusOneName(): void
    {
        $application = new Application();

        /** @var SpeakingClubRepository $speakingClubRepository */
        $speakingClubRepository = $this->getContainer()->get(SpeakingClubRepository::class);
        /** @var ParticipationRepository $participationRepository */
        $participationRepository = $this->getContainer()->get(ParticipationRepository::class);
        /** @var WaitingUserRepository $waitingUserRepository */
        $waitingUserRepository = $this->getContainer()->get(WaitingUserRepository::class);
        /** @var Clock $clock */
        $clock = $this->getContainer()->get(Clock::class);

        $clubDate = new DateTimeImmutable('-1 day');
        $speakingClub = $this->createSpeakingClub(
            'Test club',
            date: $clubDate->format('Y-m-d H:i:s')
        );

        $this->createParticipation(
            $speakingClub->getId(),
            UserFixtures::USER_ID_JOHN_CONNNOR,
            isPlusOne: true,
            plusOneName: 'Мария Петрова',
        );

        $expectedValueRange = new Google_Service_Sheets_ValueRange();
        $expectedRow = [
            $clubDate->format('d.m.Y H:i') . ' ' . DateHelper::getDayOfTheWeek($clubDate->format('d.m.Y')),
            'Test club',
            'Test Description',
            $speakingClub->getMinParticipantsCount(),
            $speakingClub->getMaxParticipantsCount(),
            1,
            'john_connor (+1 Мария Петрова)',
            '',
            0,
        ];
        $expectedValueRange->setValues([$expectedRow]);

        $mockGoogleSheetsService = $this->createMock(Google_Service_Sheets::class);
        $mockSpreadsheetsValues = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['append'])
            ->getMock();
        $mockGoogleSheetsService->spreadsheets_values = $mockSpreadsheetsValues;
        
        $mockSpreadsheetsValues->expects($this->once())
            ->method('append')
            ->with(
                'test-spreadsheet-id',
                'test-range',
                $this->callback(function (Google_Service_Sheets_ValueRange $valueRange) use ($expectedValueRange) {
                    $this->assertEquals($expectedValueRange->getValues(), $valueRange->getValues());
                    return true;
                }),
                ['valueInputOption' => 'USER_ENTERED']
            );

        $mockFactory = $this->createMock(GoogleSheetsServiceFactory::class);
        $mockFactory->method('create')->willReturn($mockGoogleSheetsService);

        $application->add(
            new ArchivePastSpeakingClubsCommand(
                speakingClubRepository: $speakingClubRepository,
                participationRepository: $participationRepository,
                waitingUserRepository: $waitingUserRepository,
                clock: $clock,
                googleSheetsServiceFactory: $mockFactory,
                spreadsheetId: 'test-spreadsheet-id',
                range: 'test-range',
            )
        );

        $command = $application->find('app:archive-clubs');
        $commandTester = new CommandTester($command);

        $result = $commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $result);
    }

    /**
     * @throws Exception
     */
    public function testSuccessWithPlusOneWithoutName(): void
    {
        $application = new Application();

        /** @var SpeakingClubRepository $speakingClubRepository */
        $speakingClubRepository = $this->getContainer()->get(SpeakingClubRepository::class);
        /** @var ParticipationRepository $participationRepository */
        $participationRepository = $this->getContainer()->get(ParticipationRepository::class);
        /** @var WaitingUserRepository $waitingUserRepository */
        $waitingUserRepository = $this->getContainer()->get(WaitingUserRepository::class);
        /** @var Clock $clock */
        $clock = $this->getContainer()->get(Clock::class);

        $clubDate = new DateTimeImmutable('-1 day');
        $speakingClub = $this->createSpeakingClub(
            'Test club',
            date: $clubDate->format('Y-m-d H:i:s')
        );

        $this->createParticipation(
            $speakingClub->getId(),
            UserFixtures::USER_ID_JOHN_CONNNOR,
            isPlusOne: true,
            plusOneName: null,
        );

        $expectedValueRange = new Google_Service_Sheets_ValueRange();
        $expectedRow = [
            $clubDate->format('d.m.Y H:i') . ' ' . DateHelper::getDayOfTheWeek($clubDate->format('d.m.Y')),
            'Test club',
            'Test Description',
            $speakingClub->getMinParticipantsCount(),
            $speakingClub->getMaxParticipantsCount(),
            1,
            'john_connor (+1)',
            '',
            0,
        ];
        $expectedValueRange->setValues([$expectedRow]);

        $mockGoogleSheetsService = $this->createMock(Google_Service_Sheets::class);
        $mockSpreadsheetsValues = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['append'])
            ->getMock();
        $mockGoogleSheetsService->spreadsheets_values = $mockSpreadsheetsValues;
        
        $mockSpreadsheetsValues->expects($this->once())
            ->method('append')
            ->with(
                'test-spreadsheet-id',
                'test-range',
                $this->callback(function (Google_Service_Sheets_ValueRange $valueRange) use ($expectedValueRange) {
                    $this->assertEquals($expectedValueRange->getValues(), $valueRange->getValues());
                    return true;
                }),
                ['valueInputOption' => 'USER_ENTERED']
            );

        $mockFactory = $this->createMock(GoogleSheetsServiceFactory::class);
        $mockFactory->method('create')->willReturn($mockGoogleSheetsService);

        $application->add(
            new ArchivePastSpeakingClubsCommand(
                speakingClubRepository: $speakingClubRepository,
                participationRepository: $participationRepository,
                waitingUserRepository: $waitingUserRepository,
                clock: $clock,
                googleSheetsServiceFactory: $mockFactory,
                spreadsheetId: 'test-spreadsheet-id',
                range: 'test-range',
            )
        );

        $command = $application->find('app:archive-clubs');
        $commandTester = new CommandTester($command);

        $result = $commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $result);
    }
}
