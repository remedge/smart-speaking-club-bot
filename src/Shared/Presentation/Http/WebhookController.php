<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http;

use App\BlockedUser\Application\Command\BlockUser\BlockUserCommand;
use App\Shared\Application\CallbackResolver;
use App\Shared\Application\Command\GenericText\AdminGenericTextCommand;
use App\Shared\Application\Command\Help\HelpCommand;
use App\Shared\Application\Command\Start\StartCommand;
use App\Shared\Domain\TelegramInterface;
use App\Shared\Domain\UserRolesProvider;
use App\SpeakingClub\Application\Command\Admin\AdminListUpcomingSpeakingClubs\AdminListUpcomingSpeakingClubsCommand;
use App\SpeakingClub\Application\Command\User\ListUpcomingSpeakingClubs\ListUpcomingSpeakingClubsCommand;
use App\SpeakingClub\Application\Command\User\ListUserUpcomingSpeakingClubs\ListUserUpcomingSpeakingClubsCommand;
use App\User\Application\Command\Admin\InitClubCreation\InitClubCreationCommand;
use App\User\Application\Command\Admin\Skip\SkipCommand;
use App\User\Application\Command\CreateUserIfNotExist\CreateUserIfNotExistCommand;
use App\User\Application\Command\User\UserGenericTextCommand;
use App\UserBan\Application\Command\ListBan\ListBanCommand;
use App\UserWarning\Application\Command\ListWarning\ListWarningCommand;
use Exception;
use Longman\TelegramBot\Entities\Update;
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
        private CallbackResolver $callbackResolver,
    ) {
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/webhook', methods: [Request::METHOD_POST])]
    public function __invoke(Request $request): Response
    {
        $this->telegram->parseUpdateFromRequest($request);

        if ($this->telegram->getUpdateType() !== Update::TYPE_MESSAGE &&
            $this->telegram->getUpdateType() !== Update::TYPE_CALLBACK_QUERY) {
            return new Response();
        }

        $chatId = $this->telegram->getChatId();
        $messageId = $this->telegram->getMessageId();
        $text = $this->telegram->getText();

        if ($text === null) {
            return new Response();
        }

        $firstName = $this->telegram->getFirstName();
        $lastName = $this->telegram->getLastName();
        $username = $this->telegram->getUsername();

        if ($username === null) {
            $this->telegram->sendMessage(
                $chatId,
                'Чтобы бот заработал и мы смогли записывать вас на разговорные клубы – вам нужно создать уникальное имя пользователя в Telegram.
                
К счастью, сделать создать его очень просто за одну минуту. 

Если у вас телефон на Android:

- Нажмите на три черточки в правом верхнем углу в телеграм;
- Выберите «Настройки» и коснитесь поля «Имя пользователя»;
- Введите уникальное имя и нажмите на кнопку в правом углу для сохранения;

Если у вас iPhone:  

- Зайти в раздел «Настройки» и перейдите в редактирование профиля через кнопку изменить;
- Найдите строку имя пользователя и нажать на нее;
- Установите свой ник и нажать “Готово”.

После этого бот @SmartLAB_NS_bot должен заработать и вы сможете быстро и легко записываться и выписываться с разговорных клубов 👌',
            );
            return new Response();
        }

        $isAdmin = $this->userRolesProvider->isUserAdmin($username);
        $this->telegram->setCommandsMenu();

        if ($this->telegram->isCallbackQuery() === true) {
            $callbackData = explode(':', $text);

            $action = $callbackData[0];
            $objectId = $callbackData[1] ?? null;
            $additionalObjectId = $callbackData[2] ?? null;

            $this->callbackResolver->resolve($action, $chatId, $objectId, $additionalObjectId, $messageId, $isAdmin);

            return new Response();
        }

        $this->commandBus->dispatch(
            new CreateUserIfNotExistCommand(
                chatId: $chatId,
                firstName: $firstName,
                lastName: $lastName,
                userName: $username,
            )
        );

        # main commands
        if ($text === '/start') {
            $this->commandBus->dispatch(new StartCommand($chatId, $isAdmin));
            return new Response();
        }

        if ($text === '/help') {
            $this->commandBus->dispatch(new HelpCommand($chatId, $isAdmin));
            return new Response();
        }

        if ($text === '/skip') {
            $this->commandBus->dispatch(new SkipCommand($chatId, $isAdmin));
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
                default => $this->commandBus->dispatch(new UserGenericTextCommand($chatId, $text)),
            };

            return new Response();
        }

        match ($text) {
            AdminListUpcomingSpeakingClubsCommand::COMMAND_NAME => $this->commandBus->dispatch(
                new AdminListUpcomingSpeakingClubsCommand($chatId)
            ),
            InitClubCreationCommand::COMMAND_NAME => $this->commandBus->dispatch(
                new InitClubCreationCommand($chatId)
            ),
            ListBanCommand::COMMAND_NAME => $this->commandBus->dispatch(
                new ListBanCommand($chatId)
            ),
            ListWarningCommand::COMMAND_NAME => $this->commandBus->dispatch(
                new ListWarningCommand($chatId)
            ),
            BlockUserCommand::COMMAND_NAME => $this->commandBus->dispatch(
                new BlockUserCommand($chatId)
            ),
            default => $this->commandBus->dispatch(new AdminGenericTextCommand($chatId, $text)),
        };

        return new Response();
    }
}
