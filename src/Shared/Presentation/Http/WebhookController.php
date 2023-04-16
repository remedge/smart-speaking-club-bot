<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http;

use App\Shared\Application\Command\GenericText\GenericTextCommand;
use App\Shared\Application\Command\Start\StartCommand;
use App\SpeakingClub\Application\Command\ListUpcomingSpeakingClubs\ListUpcomingSpeakingClubsCommand;
use App\User\Application\Command\CreateUserIfNotExist\CreateUserIfNotExistCommand;
use App\User\Application\Command\InitClubCreation\InitClubCreationCommand;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Telegram;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private Telegram $telegram,
        private string $botUsername,
    )
    {
    }

    #[Route(path: '/webhook', methods: [Request::METHOD_POST])]
    public function __invoke(Request $request): Response
    {
        $input = \Longman\TelegramBot\Request::getInput();
        if ($input === '') {
            throw new BadRequestException('No input provided');
        }
        $update = new Update(json_decode($input, true), $this->botUsername);

        $chatId = $update->getMessage()->getChat()->getId();
        $firstName = $update->getMessage()->getChat()->getFirstName();
        $lastName = $update->getMessage()->getChat()->getLastName();
        $username = $update->getMessage()->getChat()->getUsername();
        $text = $update->getMessage()->getText();

        $this->commandBus->dispatch(new CreateUserIfNotExistCommand(
            chatId: $chatId,
            firstName: $firstName,
            lastName: $lastName,
            userName: $username,
        ));

        match ($text) {
            '/start' => $this->commandBus->dispatch(new StartCommand($chatId)),
            '/create_speaking_club' => $this->commandBus->dispatch(new InitClubCreationCommand($chatId)),
            '/list_upcoming_speaking_clubs' => $this->commandBus->dispatch(new ListUpcomingSpeakingClubsCommand($chatId)),
            default => $this->commandBus->dispatch(new GenericTextCommand($chatId, $text)),
        };

        return new Response();
    }
}
