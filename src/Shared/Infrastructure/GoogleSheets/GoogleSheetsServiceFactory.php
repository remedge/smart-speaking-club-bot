<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\GoogleSheets;

use Google_Client;
use Google_Service_Sheets;

class GoogleSheetsServiceFactory
{
    public function create(): Google_Service_Sheets
    {
        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API');
        $client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
        $client->setAccessType('offline');
        $path = __DIR__ . '/../../../../credentials/credentials.json';
        $client->setAuthConfig($path);

        return new Google_Service_Sheets($client);
    }
}
