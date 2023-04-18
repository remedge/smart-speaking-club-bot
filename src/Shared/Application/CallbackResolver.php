<?php

declare(strict_types=1);

namespace App\Shared\Application;

use App\SpeakingClub\Application\Command\User\AddPlusOne\AddPlusOneCommand;
use App\SpeakingClub\Application\Command\User\ListUpcomingSpeakingClubs\ListUpcomingSpeakingClubsCommand;
use App\SpeakingClub\Application\Command\User\ListUserUpcomingSpeakingClubs\ListUserUpcomingSpeakingClubsCommand;
use App\SpeakingClub\Application\Command\User\RemovePlusOne\RemovePlusOneCommand;
use App\SpeakingClub\Application\Command\User\ShowSpeakingClub\ShowSpeakingClubCommand;
use App\SpeakingClub\Application\Command\User\SignIn\SignInCommand;
use App\SpeakingClub\Application\Command\User\SignInPlusOne\SignInPlusOneCommand;
use App\SpeakingClub\Application\Command\User\SignOut\SignOutCommand;
use Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\MessageBusInterface;

class CallbackResolver
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function resolve(string $action, int $chatId, ?string $objectId, int $messageId, bool $isAdmin): void
    {
        if ($isAdmin === true) {
            //            match ($action) {
            //                ShowSpeakingClubCommand::CALLBACK_NAME => $this->commandBus->dispatch(new ShowSpeakingClubCommand(
            //                    chatId: $chatId,
            //                    speakingClubId: Uuid::fromString($objectId),
            //                    messageId: $messageId,
            //                )),
            //                default => throw new Exception('Unknown action'),
            //            };
            //            return;
        }

        match ($action) {
            'show_speaking_club' => $this->commandBus->dispatch(new ShowSpeakingClubCommand(
                chatId: $chatId,
                speakingClubId: Uuid::fromString($objectId),
                messageId: $messageId,
                backCallback: 'back_to_list',
            )),
            'show_my_speaking_club' => $this->commandBus->dispatch(new ShowSpeakingClubCommand(
                chatId: $chatId,
                speakingClubId: Uuid::fromString($objectId),
                messageId: $messageId,
                backCallback: 'back_to_my_list',
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
            'back_to_list' => $this->commandBus->dispatch(new ListUpcomingSpeakingClubsCommand(
                chatId: $chatId,
                messageId: $messageId,
            )),
            'back_to_my_list' => $this->commandBus->dispatch(new ListUserUpcomingSpeakingClubsCommand(
                chatId: $chatId,
                messageId: $messageId,
            )),
            default => throw new Exception('Unknown action'),
        };
    }
}
