<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use Exception;

class UserNotFoundException extends Exception
{
    public function __construct(int $chatId)
    {
        parent::__construct(sprintf('User with chatId "%s" not found', $chatId));
    }
}
