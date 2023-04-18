<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http;

use App\Shared\Application\CallbackResolver;
use App\Shared\Application\Command\GenericText\GenericTextCommand;
use App\Shared\Application\Command\Help\HelpCommand;
use App\Shared\Application\Command\Start\StartCommand;
use App\Shared\Domain\TelegramInterface;
use App\Shared\Domain\UserRolesProvider;
use App\SpeakingClub\Application\Command\Admin\AdminListUpcomingSpeakingClubs\AdminListUpcomingSpeakingClubsCommand;
use App\SpeakingClub\Application\Command\User\ListUpcomingSpeakingClubs\ListUpcomingSpeakingClubsCommand;
use App\SpeakingClub\Application\Command\User\ListUserUpcomingSpeakingClubs\ListUserUpcomingSpeakingClubsCommand;
use App\User\Application\Command\Admin\InitClubCreation\InitClubCreationCommand;
use App\User\Application\Command\CreateUserIfNotExist\CreateUserIfNotExistCommand;
use Longman\TelegramBot\Entities\Update;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private TelegramInterface $telegram,
        private UserRolesProvider $userRolesProvider,
        private string $botUsername,
        private CallbackResolver $callbackResolver,
    ) {
    }

    #[Route(path: '/webhook', methods: [Request::METHOD_POST])]
    public function __invoke(Request $request): Response
    {
        $input = $this->telegram->getInput();
        if ($input === '') {
            throw new BadRequestException('No input provided');
        }
        $update = new Update(json_decode($input, true), $this->botUsername);

        if (property_exists($update, 'callback_query') === true) {
            $chatId = $update->getCallbackQuery()->getMessage()->getChat()->getId();
        } else {
            $chatId = $update->getMessage()->getChat()->getId();
        }

        $isAdmin = $this->userRolesProvider->isUserAdmin($chatId);
        $this->telegram->setCommandsMenu();

        if (property_exists($update, 'callback_query') === true) {
            $callbackRawData = $update->getCallbackQuery()->getData();
            $callbackData = explode(':', $callbackRawData);
            $action = $callbackData[0];
            $objectId = $callbackData[1] ?? null;

            $chatId = $update->getCallbackQuery()->getMessage()->getChat()->getId();
            $messageId = $update->getCallbackQuery()->getMessage()->getMessageId();

            $this->callbackResolver->resolve($action, $chatId, $objectId, $messageId, $isAdmin);

            return new Response();
        }

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

        # main commands
        if ($text === '/start') {
            $this->commandBus->dispatch(new StartCommand($chatId, $isAdmin));
            return new Response();
        }

        if ($text === '/help') {
            $this->commandBus->dispatch(new HelpCommand($chatId, $isAdmin));
            return new Response();
        }

        if ($isAdmin === false) {
            match ($text) {
                ListUpcomingSpeakingClubsCommand::COMMAND_NAME => $this->commandBus->dispatch(
                    new ListUpcomingSpeakingClubsCommand($chatId)
                ),
                ListUserUpcomingSpeakingClubsCommand::COMMAND_NAME => $this->commandBus->dispatch(
                    new ListUserUpcomingSpeakingClubsCommand($chatId)
                ),
                default => '',
            };

            return new Response();
        } else {
            match ($text) {
                AdminListUpcomingSpeakingClubsCommand::COMMAND_NAME => $this->commandBus->dispatch(
                    new AdminListUpcomingSpeakingClubsCommand($chatId)
                ),
                InitClubCreationCommand::COMMAND_NAME => $this->commandBus->dispatch(
                    new InitClubCreationCommand($chatId)
                ),
                default => $this->commandBus->dispatch(new GenericTextCommand($chatId, $text)),
            };
        }

        return new Response();
    }
}
