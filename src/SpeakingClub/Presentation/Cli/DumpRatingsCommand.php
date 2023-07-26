<?php

declare(strict_types=1);

namespace App\SpeakingClub\Presentation\Cli;

use App\SpeakingClub\Domain\RatingRepository;
use Google_Client;
use Google_Service_Sheets;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:dump-ratings', description: 'Dump all ratings about past speaking clubs')]
class DumpRatingsCommand extends Command
{
    public function __construct(
        private RatingRepository $ratingRepository,
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

        $ratings = $this->ratingRepository->findAllNondumped();

        foreach ($ratings as $rating) {
            $newRow = [
                $rating['name'],
                $rating['date']->format('d.m.Y H:i'),
                $rating['username'],
                $rating['rating'],
                $rating['comment'],
            ];
            $rows = [$newRow];
            $valueRange = new \Google_Service_Sheets_ValueRange();
            $valueRange->setValues($rows);
            $options = [
                'valueInputOption' => 'USER_ENTERED',
            ];
            $service->spreadsheets_values->append($this->spreadsheetId, $this->range, $valueRange, $options);

            $this->ratingRepository->markDumped($rating['id']);
        }

        return Command::SUCCESS;
    }
}
