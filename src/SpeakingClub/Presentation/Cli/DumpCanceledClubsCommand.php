<?php

declare(strict_types=1);

namespace App\SpeakingClub\Presentation\Cli;

use App\Shared\Application\Clock;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\System\DateHelper;
use Google_Client;
use Google_Service_Sheets;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:dump-canceled', description: 'Dump all ratings about past speaking clubs')]
class DumpCanceledClubsCommand extends Command
{
    public function __construct(
        private SpeakingClubRepository $speakingClubRepository,
        private ParticipationRepository $participationRepository,
        private Clock $clock,
        private string $spreadsheetId,
        private string $range,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API');
        $client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
        $client->setAccessType('offline');
        $path = __DIR__ . '/../../../../credentials/credentials.json';
        $client->setAuthConfig($path);

        $service = new Google_Service_Sheets($client);

        $speakingClubs = $this->speakingClubRepository->findAllCanceled($this->clock->now());

        foreach ($speakingClubs as $speakingClub) {
            $participations = $this->participationRepository->findBySpeakingClubId($speakingClub->getId());
            $participationsCount = count($participations);
            $newRow = [
                $speakingClub->getDate()->format('d.m.Y H:i') . ' ' . DateHelper::getDayOfTheWeek(
                    $speakingClub->getDate()->format('d.m.Y')
                ),
                $speakingClub->getName(),
                $speakingClub->getDescription(),
                $speakingClub->getMinParticipantsCount(),
                $speakingClub->getMaxParticipantsCount(),
                $participationsCount,
                implode(', ', array_map(fn (array $p) => $p['username'] . (($p['isPlusOne'] === true) ? ' (+1)' : ''), $participations)),
            ];
            $rows = [$newRow];
            $valueRange = new \Google_Service_Sheets_ValueRange();
            $valueRange->setValues($rows);
            $options = [
                'valueInputOption' => 'USER_ENTERED',
            ];
            $service->spreadsheets_values->append($this->spreadsheetId, $this->range, $valueRange, $options);
        }

        return Command::SUCCESS;
    }
}
