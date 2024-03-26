<?php

namespace App\SpeakingClub\Presentation\Cli;

use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;

class DumpFactory
{
    public function createClient(): Google_Client
    {
        return new Google_Client();
    }

    public function createServiceSheets(Google_Client $client): Google_Service_Sheets
    {
        return new Google_Service_Sheets($client);
    }

    public function createServiceSheetsValueRange(): Google_Service_Sheets_ValueRange
    {
        return new Google_Service_Sheets_ValueRange();
    }
}
