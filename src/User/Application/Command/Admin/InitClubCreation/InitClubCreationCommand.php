<?php

declare(strict_types=1);

namespace App\User\Application\Command\Admin\InitClubCreation;

class InitClubCreationCommand
{
    public const COMMAND_NAME = '/admin_create_club';

    public const COMMAND_DESCRIPTION = 'Создать новый разговорный клуб';

    public function __construct(
        public int $chatId,
    ) {
    }
}
