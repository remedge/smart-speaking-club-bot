<?php

declare(strict_types=1);

namespace App\SpeakingClub\Presentation\Cli;

use App\UserBan\Domain\UserBanRepository;
use Google_Client;
use Google_Service_Sheets;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:dump-user-ban', description: 'Dump all users with ban')]
class DumpUserBanCommand extends Command
{
    public function __construct(
        private UserBanRepository $userBanRepository,
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

        $userBans = $this->userBanRepository->findAllBan();

        foreach ($userBans as $userBan) {
            $newRow = [
                $userBan['firstName'] ?? '',
                $userBan['lastName'] ?? '',
                $userBan['username'] ?? '',
                $userBan['endDate']->format('d.m.Y H:i'),
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
