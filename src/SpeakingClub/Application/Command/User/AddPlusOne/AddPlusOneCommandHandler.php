<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\AddPlusOne;

use App\Shared\Application\Clock;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Application\Command\User\AddPlusOneName\AddPlusOneNameCommand;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\User\Application\Query\UserQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AddPlusOneCommandHandler
{
    public function __construct(
        private UserQuery $userQuery,
        private ParticipationRepository $participationRepository,
        private SpeakingClubRepository $speakingClubRepository,
        private TelegramInterface $telegram,
        private Clock $clock,
    ) {
    }

    public function __invoke(AddPlusOneCommand $command): void
    {
        $user = $this->userQuery->getByChatId($command->chatId);
        $speakingClub = $this->speakingClubRepository->findById($command->speakingClubId);

        if ($speakingClub === null) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: '🤔 Разговорный клуб не найден',
                replyMarkup: [[
                    [
                        'text' => '<< Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ],
                ]]
            );
            return;
        }

        if ($this->clock->now() > $speakingClub->getDate()) {
            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: '🤔 К сожалению, этот разговорный клуб уже прошел',
                replyMarkup: [[
                    [
                        'text' => '<< Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ],
                ]]
            );
            return;
        }

        $participation = $this->participationRepository->findByUserIdAndSpeakingClubId($user->id, $command->speakingClubId);
        if ($participation === null) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: '🤔 Вы не записаны на этот клуб',
                replyMarkup: [[
                    [
                        'text' => 'Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ],
                ]]
            );
            return;
        }

        if ($participation->isPlusOne() === true) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: '🤔 Вы уже добавили +1 с собой на этот клуб',
                replyMarkup: [[
                    [
                        'text' => 'Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ],
                ]]
            );
            return;
        }

        $participationCount = $this->participationRepository->countByClubId($command->speakingClubId);
        if ($participationCount >= $speakingClub->getMaxParticipantsCount()) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: '😔 К сожалению, все свободные места на данный клуб заняты и вы не можете добавить +1',
                replyMarkup: [
                    [[
                        'text' => 'Перейти к списку ваших клубов',
                        'callback_data' => 'back_to_my_list',
                    ]],
                ]
            );
            return;
        }

        // Обновляем существующее участие, устанавливая isPlusOne: true
        $participation->setIsPlusOne(true);
        $participation->setPlusOneName(null);
        $this->participationRepository->save($participation);

        $this->telegram->editMessageText(
            chatId: $command->chatId,
            messageId: $command->messageId,
            text: '👌 Вы успешно добавили +1 человека с собой'
                . PHP_EOL . PHP_EOL
                . 'Мы будем рады, если вы укажете имя второго участника. Это поможет нам лучше организовать мероприятие.',
            replyMarkup: [
                [
                    [
                        'text'          => 'Добавить имя участника',
                        'callback_data' => sprintf(
                            '%s:%s',
                            AddPlusOneNameCommand::CALLBACK_NAME,
                            $command->speakingClubId->toString()
                        ),
                    ],
                ],
                [
                    [
                        'text'          => '<< Перейти к списку ваших клубов',
                        'callback_data' => 'back_to_my_list',
                    ],
                ],
            ]
        );
    }
}
