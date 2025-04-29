<?php

declare(strict_types=1);

namespace App\WaitList\Application\EventListener;

use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Application\Event\SpeakingClubFreeSpaceAvailableEvent;
use App\SpeakingClub\Application\Exception\SpeakingClubNotFoundException;
use App\SpeakingClub\Application\Query\SpeakingClubQuery;
use App\User\Application\Query\UserQuery;
use App\WaitList\Domain\WaitingUserRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class SpeakingClubFreeSpaceAvailableEventListener
{
    public function __construct(
        private WaitingUserRepository $waitingUserRepository,
        private SpeakingClubQuery $speakingClubQuery,
        private UserQuery $userQuery,
        private TelegramInterface $telegram,
    ) {
    }

    /**
     * @throws SpeakingClubNotFoundException
     */
    public function __invoke(SpeakingClubFreeSpaceAvailableEvent $event): void
    {
        $speakingClub = $this->speakingClubQuery->getById($event->speakingClubId);

        $waitingUsers = $this->waitingUserRepository->findBySpeakingClubId($event->speakingClubId);

        foreach ($waitingUsers as $waitingUser) {
            $user = $this->userQuery->findById($waitingUser['userId']); // TODO: rewrite it

            if ($user === null) {
                continue;
            }

            $this->telegram->sendMessage(
                chatId: $user->chatId,
                text: sprintf(
                    'В клубе "%s" %s появилось свободное место. Перейдите к описанию клуба, чтобы записаться',
                    $speakingClub->name,
                    $speakingClub->date->format('d.m.Y H:i'),
                ),
                replyMarkup: [[
                    [
                        'text' => 'Перейти к описанию клуба',
                        'callback_data' => sprintf('show_speaking_club:%s', $speakingClub->id->toString()),
                    ],
                ]]
            );
        }
    }
}
