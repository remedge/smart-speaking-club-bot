<?php

declare(strict_types=1);

namespace App\User\Application\EventListener;

use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Application\Event\SpeakingClubScheduleChangedEvent;
use App\SpeakingClub\Application\Query\ParticipationQuery;
use App\SpeakingClub\Application\Query\SpeakingClubQuery;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class ScheduleChangedEventListener
{
    public function __construct(
        public ParticipationQuery $participationQuery,
        public SpeakingClubQuery $speakingClubQuery,
        public TelegramInterface $telegram,
    ) {
    }

    public function __invoke(SpeakingClubScheduleChangedEvent $event): void
    {
        $speakingClubDTO = $this->speakingClubQuery->getById($event->speakingClubId);

        $participations = $this->participationQuery->findBySpeakingClubId($event->speakingClubId);

        foreach ($participations as $participation) {
            $this->telegram->sendMessage(
                chatId: $participation->chatId,
                text: sprintf(
                    'Изменилось время проведения для клуба "%s", новое время: %s',
                    $speakingClubDTO->name,
                    $speakingClubDTO->date->format('d.m.Y H:i')
                ),
                replyMarkup: [[
                    [
                        'text' => 'Просмотреть информацию о клубе',
                        'callback_data' => sprintf('show_club_separated:%s', $speakingClubDTO->id->toString()),
                    ],
                ]]
            );
        }
    }
}
