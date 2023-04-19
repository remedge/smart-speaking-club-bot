<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Exception;

use Exception;
use Ramsey\Uuid\UuidInterface;

class SpeakingClubNotFoundException extends Exception
{
    public function __construct(UuidInterface $id)
    {
        parent::__construct(sprintf('SpeakingClub with id "%s" not found', $id->toString()));
    }
}
