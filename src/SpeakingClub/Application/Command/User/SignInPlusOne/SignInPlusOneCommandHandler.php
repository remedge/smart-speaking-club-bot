<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\SignInPlusOne;

use App\SpeakingClub\Application\Command\User\AddPlusOneName\AddPlusOneNameCommand;
use App\SpeakingClub\Application\Command\User\SignInHandler;
use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\User\Application\Exception\UserNotFoundException;
use App\User\Application\Query\UserQuery;
use App\User\Domain\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SignInPlusOneCommandHandler
{
    public function __construct(
        private readonly UserQuery $userQuery,
        private readonly SpeakingClubRepository $speakingClubRepository,
        private SignInHandler $signInHandler
    ) {
    }

    /**
     * @throws UserNotFoundException
     */
    public function __invoke(SignInPlusOneCommand $command): void
    {
        $user = $this->userQuery->getByChatId($command->chatId);
        $speakingClub = $this->speakingClubRepository->findById($command->speakingClubId);

        $successMessage = $this->getSuccessMessage();
        $replyMarkup = $this->getReplyMarkup($speakingClub);

        $this->signInHandler->handleSignIn(
            user: $user,
            chatId: $command->chatId,
            messageId: $command->messageId,
            successMessage: $successMessage,
            replyMarkup: $replyMarkup,
            speakingClub: $speakingClub,
        );
    }

    private function getSuccessMessage(): string
    {
        return '📝 Почти готово! Для завершения записи введите данные гостя'
            . PHP_EOL . PHP_EOL
            . 'Место для второго участника будет забронировано только после ввода его данных.'
            . PHP_EOL . PHP_EOL
            . 'Пожалуйста, укажите Имя Фамилию или @username вашего друга.'
            . PHP_EOL . PHP_EOL
            . 'Это нужно, чтобы мы могли внести его в списки участников.';
    }

    private function getReplyMarkup(?SpeakingClub $speakingClub): array
    {
        if (null === $speakingClub) {
            return [];
        }

        return [
            [
                [
                    'text'          => 'Добавить имя участника',
                    'callback_data' => sprintf(
                        '%s:%s',
                        AddPlusOneNameCommand::CALLBACK_NAME,
                        $speakingClub->getId()->toString()
                    ),
                ],
            ],
            [
                [
                    'text'          => '<< Перейти к списку ваших клубов',
                    'callback_data' => 'back_to_my_list',
                ],
            ],
        ];
    }
}
