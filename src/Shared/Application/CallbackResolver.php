<?php

declare(strict_types=1);

namespace App\Shared\Application;

use App\BlockedUser\Application\Command\BlockUser\BlockUserCommand;
use App\BlockedUser\Application\Command\RemoveBlock\RemoveBlockCommand;
use App\SpeakingClub\Application\Command\Admin\AdminAddParticipant\AdminAddParticipantCommand;
use App\SpeakingClub\Application\Command\Admin\AdminAddPlusOneToParticipant\AdminAddPlusOneToParticipantCommand;
use App\SpeakingClub\Application\Command\Admin\AdminListUpcomingSpeakingClubs\AdminListUpcomingSpeakingClubsCommand;
use App\SpeakingClub\Application\Command\Admin\AdminRemoveParticipant\AdminRemoveParticipantCommand;
use App\SpeakingClub\Application\Command\Admin\AdminRemovePlusOneToParticipant\AdminRemovePlusOneToParticipantCommand;
use App\SpeakingClub\Application\Command\Admin\AdminShowParticipants\AdminShowParticipantsCommand;
use App\SpeakingClub\Application\Command\Admin\AdminShowSpeakingClub\AdminShowSpeakingClubCommand;
use App\SpeakingClub\Application\Command\User\AddPlusOne\AddPlusOneCommand;
use App\SpeakingClub\Application\Command\User\ListUpcomingSpeakingClubs\ListUpcomingSpeakingClubsCommand;
use App\SpeakingClub\Application\Command\User\ListUserUpcomingSpeakingClubs\ListUserUpcomingSpeakingClubsCommand;
use App\SpeakingClub\Application\Command\User\RateSpeakingClub\RateSpeakingClubCommand;
use App\SpeakingClub\Application\Command\User\RemovePlusOne\RemovePlusOneCommand;
use App\SpeakingClub\Application\Command\User\ShowSpeakingClub\ShowSpeakingClubCommand;
use App\SpeakingClub\Application\Command\User\SignIn\SignInCommand;
use App\SpeakingClub\Application\Command\User\SignInPlusOne\SignInPlusOneCommand;
use App\SpeakingClub\Application\Command\User\SignOut\SignOutCommand;
use App\SpeakingClub\Application\Command\User\SignOutApply\SignOutApplyCommand;
use App\User\Application\Command\Admin\InitClubCancellation\InitClubCancellationCommand;
use App\User\Application\Command\Admin\InitClubCreation\InitClubCreationCommand;
use App\User\Application\Command\Admin\InitClubEdition\InitClubEditionCommand;
use App\User\Application\Command\Admin\InitSendMessageEveryone\InitSendMessageEveryoneCommand;
use App\User\Application\Command\Admin\InitSendMessageToParticipants\InitSendMessageToParticipantsCommand;
use App\UserBan\Application\Command\AddBan\AddBanCommand;
use App\UserBan\Application\Command\RemoveBan\RemoveBanCommand;
use App\UserWarning\Application\Command\AddWarning\AddWarningCommand;
use App\UserWarning\Application\Command\RemoveWarning\RemoveWarningCommand;
use App\WaitList\Application\Command\JoinWaitingList\JoinWaitingListCommand;
use App\WaitList\Application\Command\LeaveWaitingList\LeaveWaitingListCommand;
use Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\MessageBusInterface;

class CallbackResolver
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function resolve(
        string $action,
        int $chatId,
        ?string $objectId,
        ?string $additionalObjectId,
        int $messageId,
        bool $isAdmin
    ): void {
        if ($isAdmin === true) {
            match ($action) {
                'admin_upcoming_clubs' => $this->commandBus->dispatch(new AdminListUpcomingSpeakingClubsCommand(
                    chatId: $chatId,
                )),
                'admin_create_club' => $this->commandBus->dispatch(new InitClubCreationCommand(
                    chatId: $chatId,
                )),
                'admin_send_message' => $this->commandBus->dispatch(new InitSendMessageEveryoneCommand(
                    chatId: $chatId,
                )),
                AdminShowSpeakingClubCommand::CALLBACK_NAME => $this->commandBus->dispatch(new AdminShowSpeakingClubCommand(
                    chatId: $chatId,
                    speakingClubId: Uuid::fromString($objectId),
                    messageId: $messageId,
                )),
                'back_to_admin_list' => $this->commandBus->dispatch(new AdminListUpcomingSpeakingClubsCommand(
                    chatId: $chatId,
                    messageId: $messageId,
                )),
                'edit_club' => $this->commandBus->dispatch(new InitClubEditionCommand(
                    chatId: $chatId,
                    speakingClubId: Uuid::fromString($objectId),
                    messageId: $messageId,
                )),
                'cancel_club' => $this->commandBus->dispatch(new InitClubCancellationCommand(
                    speakingClubId: Uuid::fromString($objectId),
                    chatId: $chatId,
                    messageId: $messageId,
                )),
                'show_participants' => $this->commandBus->dispatch(new AdminShowParticipantsCommand(
                    chatId: $chatId,
                    speakingClubId: Uuid::fromString($objectId),
                    messageId: $messageId,
                )),
                'admin_add_plus_one' => $this->commandBus->dispatch(new AdminAddPlusOneToParticipantCommand(
                    chatId: $chatId,
                    messageId: $messageId,
                    participationId: Uuid::fromString($objectId),
                )),
                'admin_remove_plus_one' => $this->commandBus->dispatch(new AdminRemovePlusOneToParticipantCommand(
                    chatId: $chatId,
                    messageId: $messageId,
                    participationId: Uuid::fromString($objectId),
                )),
                'remove_participant' => $this->commandBus->dispatch(new AdminRemoveParticipantCommand(
                    chatId: $chatId,
                    messageId: $messageId,
                    participationId: Uuid::fromString($objectId),
                )),
                'admin_add_participant' => $this->commandBus->dispatch(new AdminAddParticipantCommand(
                    chatId: $chatId,
                    messageId: $messageId,
                    speakingClubId: Uuid::fromString($objectId),
                )),
                'notify_participants' => $this->commandBus->dispatch(new InitSendMessageToParticipantsCommand(
                    chatId: $chatId,
                    speakingClubId: Uuid::fromString($objectId),
                )),
                'add_ban' => $this->commandBus->dispatch(new AddBanCommand(
                    chatId: $chatId,
                    messageId: $messageId,
                )),
                'remove_ban' => $this->commandBus->dispatch(new RemoveBanCommand(
                    chatId: $chatId,
                    messageId: $messageId,
                    banId: Uuid::fromString($objectId),
                )),
                'add_warning' => $this->commandBus->dispatch(new AddWarningCommand(
                    chatId: $chatId,
                    messageId: $messageId,
                )),
                'remove_warning' => $this->commandBus->dispatch(new RemoveWarningCommand(
                    chatId: $chatId,
                    messageId: $messageId,
                    warningId: Uuid::fromString($objectId),
                )),
                'block_user' => $this->commandBus->dispatch(new BlockUserCommand(
                    chatId: $chatId,
                    messageId: $messageId,
                )),
                'remove_block' => $this->commandBus->dispatch(new RemoveBlockCommand(
                    chatId: $chatId,
                    messageId: $messageId,
                    blockId: Uuid::fromString($objectId),
                )),
                default => throw new Exception(sprintf('Unknown admin callback "%s', $action)),
            };
            return;
        }

        match ($action) {
            'upcoming_clubs' => $this->commandBus->dispatch(new ListUpcomingSpeakingClubsCommand(
                chatId: $chatId,
            )),
            'my_upcoming_clubs' => $this->commandBus->dispatch(new ListUserUpcomingSpeakingClubsCommand(
                chatId: $chatId,
            )),
            'show_speaking_club' => $this->commandBus->dispatch(new ShowSpeakingClubCommand(
                chatId: $chatId,
                speakingClubId: Uuid::fromString($objectId),
                backCallback: 'back_to_list',
                messageId: $messageId,
            )),
            'show_club_separated' => $this->commandBus->dispatch(new ShowSpeakingClubCommand(
                chatId: $chatId,
                speakingClubId: Uuid::fromString($objectId),
                backCallback: 'back_to_list',
            )),
            'show_my_speaking_club' => $this->commandBus->dispatch(new ShowSpeakingClubCommand(
                chatId: $chatId,
                speakingClubId: Uuid::fromString($objectId),
                backCallback: 'back_to_my_list',
                messageId: $messageId,
            )),
            SignInCommand::CALLBACK_NAME => $this->commandBus->dispatch(new SignInCommand(
                chatId: $chatId,
                speakingClubId: Uuid::fromString($objectId),
                messageId: $messageId,
            )),
            SignInPlusOneCommand::CALLBACK_NAME => $this->commandBus->dispatch(new SignInPlusOneCommand(
                chatId: $chatId,
                speakingClubId: Uuid::fromString($objectId),
                messageId: $messageId,
            )),
            SignOutCommand::CALLBACK_NAME => $this->commandBus->dispatch(new SignOutCommand(
                chatId: $chatId,
                speakingClubId: Uuid::fromString($objectId),
                messageId: $messageId,
            )),
            SignOutApplyCommand::CALLBACK_NAME => $this->commandBus->dispatch(new SignOutApplyCommand(
                chatId: $chatId,
                speakingClubId: Uuid::fromString($objectId),
                messageId: $messageId,
            )),
            AddPlusOneCommand::CALLBACK_NAME => $this->commandBus->dispatch(new AddPlusOneCommand(
                chatId: $chatId,
                speakingClubId: Uuid::fromString($objectId),
                messageId: $messageId,
            )),
            RemovePlusOneCommand::CALLBACK_NAME => $this->commandBus->dispatch(new RemovePlusOneCommand(
                chatId: $chatId,
                speakingClubId: Uuid::fromString($objectId),
                messageId: $messageId,
            )),
            'join_waiting_list' => $this->commandBus->dispatch(new JoinWaitingListCommand(
                chatId: $chatId,
                speakingClubId: Uuid::fromString($objectId),
                messageId: $messageId,
            )),
            'leave_waiting_list' => $this->commandBus->dispatch(new LeaveWaitingListCommand(
                chatId: $chatId,
                speakingClubId: Uuid::fromString($objectId),
                messageId: $messageId,
            )),
            'rate' => $this->commandBus->dispatch(new RateSpeakingClubCommand(
                speakingClubId: Uuid::fromString($objectId),
                chatId: $chatId,
                messageId: $messageId,
                rating: (int) $additionalObjectId,
            )),
            'back_to_list' => $this->commandBus->dispatch(new ListUpcomingSpeakingClubsCommand(
                chatId: $chatId,
                messageId: $messageId,
            )),
            'back_to_my_list' => $this->commandBus->dispatch(new ListUserUpcomingSpeakingClubsCommand(
                chatId: $chatId,
                messageId: $messageId,
            )),
            default => throw new Exception(sprintf('Unknown user callback "%s"', $action)),
        };
    }
}
