<?php

declare(strict_types=1);

namespace App\SpeakingClub\Presentation\Cli;

use App\UserWarning\Domain\UserWarningRepository;
use Google_Client;
use Google_Service_Sheets;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:dump-user-warning', description: 'Dump all users with warnings')]
class DumpUserWarningCommand extends Command
{
    public function __construct(
        private UserWarningRepository $userWarningRepository,
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

        $userWarnings = $this->userWarningRepository->findAllWarning();

        foreach ($userWarnings as $userWarning) {
            $newRow = [
                $userWarning['firstName'] ?? '',
                $userWarning['lastName'] ?? '',
                $userWarning['username'] ?? '',
                $userWarning['createdAt']->format('d.m.Y H:i'),
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
