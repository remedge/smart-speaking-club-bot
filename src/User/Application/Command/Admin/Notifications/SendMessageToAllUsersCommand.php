<?php

namespace App\User\Application\Command\Admin\Notifications;

class SendMessageToAllUsersCommand
{
    public function __construct(public string $text, public int $adminChatId)
    {
    }
}
