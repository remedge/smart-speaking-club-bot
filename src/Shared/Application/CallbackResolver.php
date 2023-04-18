<?php

declare(strict_types=1);

namespace App\Shared\Application;

use App\SpeakingClub\Application\Command\User\AddPlusOne\AddPlusOneCommand;
use App\SpeakingClub\Application\Command\User\ListUpcomingSpeakingClubs\ListUpcomingSpeakingClubsCommand;
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
            match ($action) {
                ShowSpeakingClubCommand::CALLBACK_NAME => $this->commandBus->dispatch(new ShowSpeakingClubCommand(
                    chatId: $chatId,
                    speakingClubId: Uuid::fromString($objectId),
                    messageId: $messageId,
                )),
                default => throw new Exception('Unknown action'),
            };
            return;
        }

        match ($action) {
            ShowSpeakingClubCommand::CALLBACK_NAME => $this->commandBus->dispatch(new ShowSpeakingClubCommand(
                chatId: $chatId,
                speakingClubId: Uuid::fromString($objectId),
                messageId: $messageId,
            )),
            SignInCommand::CALLBACK_NAME => $this->commandBus->dispatch(new SignInCommand(
                chatId: $chatId,
                speakingClubId: Uuid::fromString($objectId),
            )),
            SignInPlusOneCommand::CALLBACK_NAME => $this->commandBus->dispatch(new SignInPlusOneCommand(
                chatId: $chatId,
                speakingClubId: Uuid::fromString($objectId),
            )),
            SignOutCommand::CALLBACK_NAME => $this->commandBus->dispatch(new SignOutCommand(
                chatId: $chatId,
                speakingClubId: Uuid::fromString($objectId),
            )),
            AddPlusOneCommand::CALLBACK_NAME => $this->commandBus->dispatch(new AddPlusOneCommand(
                chatId: $chatId,
                speakingClubId: Uuid::fromString($objectId),
            )),
            RemovePlusOneCommand::CALLBACK_NAME => $this->commandBus->dispatch(new RemovePlusOneCommand(
                chatId: $chatId,
                speakingClubId: Uuid::fromString($objectId),
            )),
            'back_to_list' => $this->commandBus->dispatch(new ListUpcomingSpeakingClubsCommand(
                chatId: $chatId,
                messageId: $messageId,
            )),
            default => throw new Exception('Unknown action'),
        };
    }
}
