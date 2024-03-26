<?php

declare(strict_types=1);

namespace App\SpeakingClub\Presentation\Cli;

use App\Shared\Application\Clock;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\WaitList\Domain\WaitingUserRepository;
use Google_Service_Sheets;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:archive-clubs', description: 'Every day archive past speaking clubs')]
class ArchivePastSpeakingClubsCommand extends Command
{
    public function __construct(
        private SpeakingClubRepository $speakingClubRepository,
        private ParticipationRepository $participationRepository,
        private WaitingUserRepository $waitingUserRepository,
        private Clock $clock,
        private DumpFactory $dumpFactory,
        private string $spreadsheetId,
        private string $range,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $client = $this->dumpFactory->createClient();
        $client->setApplicationName('Google Sheets API');
        $client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
        $client->setAccessType('offline');
        $path = __DIR__ . '/../../../../credentials/credentials.json';
        $client->setAuthConfig($path);

        $service = $this->dumpFactory->createServiceSheets($client);

        $speakingClubs = $this->speakingClubRepository->findAllPastNotArchived($this->clock->now());

        foreach ($speakingClubs as $speakingClub) {
            $participations = $this->participationRepository->findBySpeakingClubId($speakingClub->getId());
            $participationsCount = count($participations);

            $waitingUsers = $this->waitingUserRepository->findBySpeakingClubId($speakingClub->getId());
            $waitingUsersCount = count($waitingUsers);

            $newRow = [
                $speakingClub->getDate()->format('d.m.Y H:i'),
                $speakingClub->getName(),
                $speakingClub->getDescription(),
                $speakingClub->getMinParticipantsCount(),
                $speakingClub->getMaxParticipantsCount(),
                $participationsCount,
                implode(', ', array_map(fn (array $p) => $p['username'] . (($p['isPlusOne'] === true) ? ' (+1)' : ''), $participations)),
                implode(', ', array_map(fn (array $w) => $w['username'], $waitingUsers)),
                $waitingUsersCount,
            ];
            $rows = [$newRow];
            $valueRange = $this->dumpFactory->createServiceSheetsValueRange();
            $valueRange->setValues($rows);
            $options = [
                'valueInputOption' => 'USER_ENTERED',
            ];
            $service->spreadsheets_values->append($this->spreadsheetId, $this->range, $valueRange, $options);

            $this->speakingClubRepository->markArchived($speakingClub->getId());
        }

        return Command::SUCCESS;
    }
}
