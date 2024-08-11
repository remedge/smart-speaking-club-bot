<?php

declare(strict_types=1);

namespace App\Shared\Application\Command\Help;

use App\BlockedUser\Application\Command\BlockedUsersList\BlockedUsersListCommand;
use App\BlockedUser\Application\Command\BlockUser\BlockUserCommand;
use App\Shared\Application\Command\Start\StartCommand;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Application\Command\Admin\AdminListUpcomingSpeakingClubs\AdminListUpcomingSpeakingClubsCommand;
use App\SpeakingClub\Application\Command\User\ListUpcomingSpeakingClubs\ListUpcomingSpeakingClubsCommand;
use App\SpeakingClub\Application\Command\User\ListUserUpcomingSpeakingClubs\ListUserUpcomingSpeakingClubsCommand;
use App\User\Application\Command\Admin\InitClubCreation\InitClubCreationCommand;
use App\UserBan\Application\Command\ListBan\ListBanCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class HelpCommandHandler
{
    public function __construct(
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(HelpCommand $command): void
    {
        $adminCommands = [
            'Список команд администратора:',
            sprintf(
                '%s - %s',
                StartCommand::COMMAND_NAME,
                StartCommand::COMMAND_DESCRIPTION
            ),
            sprintf(
                '%s - %s',
                HelpCommand::COMMAND_NAME,
                HelpCommand::COMMAND_DESCRIPTION
            ),
            sprintf(
                '%s - %s',
                AdminListUpcomingSpeakingClubsCommand::COMMAND_NAME,
                AdminListUpcomingSpeakingClubsCommand::COMMAND_DESCRIPTION
            ),
            sprintf(
                '%s - %s',
                InitClubCreationCommand::COMMAND_NAME,
                InitClubCreationCommand::COMMAND_DESCRIPTION
            ),
            sprintf(
                '%s - %s',
                ListBanCommand::COMMAND_NAME,
                ListBanCommand::COMMAND_DESCRIPTION
            ),
            sprintf(
                '%s - %s',
                BlockUserCommand::COMMAND_NAME,
                BlockUserCommand::COMMAND_DESCRIPTION
            ),
            sprintf(
                '%s - %s',
                BlockedUsersListCommand::COMMAND_NAME,
                BlockedUsersListCommand::COMMAND_DESCRIPTION
            )
        ];

        $userCommands = [
            'Список команд:',
            sprintf(
                '%s - %s',
                StartCommand::COMMAND_NAME,
                StartCommand::COMMAND_DESCRIPTION
            ),
            sprintf(
                '%s - %s',
                HelpCommand::COMMAND_NAME,
                HelpCommand::COMMAND_DESCRIPTION
            ),
            sprintf(
                '%s - %s',
                ListUpcomingSpeakingClubsCommand::COMMAND_NAME,
                ListUpcomingSpeakingClubsCommand::COMMAND_DESCRIPTION
            ),
            sprintf(
                '%s - %s',
                ListUserUpcomingSpeakingClubsCommand::COMMAND_NAME,
                ListUserUpcomingSpeakingClubsCommand::COMMAND_DESCRIPTION
            )
        ];

        $text = match ($command->isAdmin) {
            true => implode(PHP_EOL, $adminCommands) . PHP_EOL,
            false => implode(PHP_EOL, $userCommands) . PHP_EOL,
        };

        $this->telegram->sendMessage(
            chatId: $command->chatId,
            text: $text,
        );
    }
}
