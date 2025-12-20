<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\SignIn;

use App\SpeakingClub\Application\Command\User\SignInHandler;
use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\System\DateHelper;
use App\User\Application\Exception\UserNotFoundException;
use App\User\Application\Query\UserQuery;
use App\User\Domain\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SignInCommandHandler
{
    public function __construct(
        private readonly UserQuery $userQuery,
        private readonly SpeakingClubRepository $speakingClubRepository,
        private readonly SignInHandler $signInHandler
    ) {
    }

    /**
     * @throws UserNotFoundException
     */
    public function __invoke(SignInCommand $command): void
    {
        $user = $this->userQuery->getByChatId($command->chatId);
        $speakingClub = $this->speakingClubRepository->findById($command->speakingClubId);

        $successMessage = $this->getSuccessMessage($speakingClub);
        $replyMarkup = $this->getReplyMarkup();

        $this->signInHandler->handleSignIn(
            user: $user,
            chatId: $command->chatId,
            messageId: $command->messageId,
            successMessage: $successMessage,
            replyMarkup: $replyMarkup,
            speakingClub: $speakingClub,
        );
    }

    private function getSuccessMessage(?SpeakingClub $speakingClub): string
    {
        if (null === $speakingClub) {
            return '';
        }

        return sprintf(
            '👌 Вы успешно записаны на разговорный клуб "%s", который состоится %s в %s',
            $speakingClub->getName(),
            $speakingClub->getDate()->format('d.m.Y'),
            $speakingClub->getDate()->format('H:i') . ' ' . DateHelper::getDayOfTheWeek(
                $speakingClub->getDate()->format('d.m.Y')
            ),
        );
    }

    private function getReplyMarkup(): array
    {
        return [
            [
                [
                    'text'          => '<< Перейти к списку ваших клубов',
                    'callback_data' => 'back_to_my_list',
                ],
            ]
        ];
    }
}
