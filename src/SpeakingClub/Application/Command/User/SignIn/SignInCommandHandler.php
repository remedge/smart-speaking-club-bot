<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\SignIn;

use App\Shared\Application\Clock;
use App\Shared\Application\UuidProvider;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\Participation;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\System\DateHelper;
use App\User\Application\Exception\UserNotFoundException;
use App\User\Application\Query\UserQuery;
use App\UserBan\Domain\UserBanRepository;
use App\WaitList\Domain\WaitingUserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SignInCommandHandler
{
    public function __construct(
        private UserQuery $userQuery,
        private ParticipationRepository $participationRepository,
        private SpeakingClubRepository $speakingClubRepository,
        private WaitingUserRepository $waitingUserRepository,
        private UserBanRepository $userBanRepository,
        private TelegramInterface $telegram,
        private UuidProvider $uuidProvider,
        private Clock $clock,
    ) {
    }

    /**
     * @throws UserNotFoundException
     */
    public function __invoke(SignInCommand $command): void
    {
        $user = $this->userQuery->getByChatId($command->chatId);
        $speakingClub = $this->speakingClubRepository->findById($command->speakingClubId);

        if ($speakingClub === null) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: '🤔 Разговорный клуб не найден',
                replyMarkup: [
                    [
                        [
                            'text'          => '<< Перейти к списку ближайших клубов',
                            'callback_data' => 'back_to_list',
                        ],
                    ]
                ]
            );
            return;
        }

        if ($this->clock->now() > $speakingClub->getDate()) {
            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: '🤔 К сожалению, этот разговорный клуб уже прошел',
                replyMarkup: [
                    [
                        [
                            'text'          => '<< Перейти к списку ближайших клубов',
                            'callback_data' => 'back_to_list',
                        ],
                    ]
                ]
            );
            return;
        }

        $participation = $this->participationRepository->findByUserIdAndSpeakingClubId(
            $user->id,
            $command->speakingClubId
        );
        if ($participation !== null) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: '🤔 Вы уже записаны на этот разговорный клуб',
                replyMarkup: [
                    [
                        [
                            'text'          => '<< Перейти к списку ваших клубов',
                            'callback_data' => 'back_to_my_list',
                        ],
                    ]
                ]
            );
            return;
        }

        $participationCount = $this->participationRepository->countByClubId($command->speakingClubId);
        if ($participationCount >= $speakingClub->getMaxParticipantsCount()) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: '😔 К сожалению, все свободные места на данный клуб заняты',
                replyMarkup: [
                    [
                        [
                            'text'          => 'Встать в лист ожидания',
                            'callback_data' => sprintf('join_waiting_list:%s', $command->speakingClubId->toString()),
                        ]
                    ],
                    [
                        [
                            'text'          => '<< Перейти к списку ближайших клубов',
                            'callback_data' => 'back_to_list',
                        ]
                    ],
                ]
            );
            return;
        }

        $userBan = $this->userBanRepository->findByUserId($user->id, $this->clock->now());

        if ($userBan !== null) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: sprintf(
                    'Здравствуйте! Мы заметили, что недавно вы дважды отменили участие в нашем разговорном клубе менее чем за 24 часа до начала. 

Чтобы гарантировать комфортное общение и планирование для всех участников, мы временно ограничиваем вашу возможность записываться на новые сессии. Это ограничение будет действовать до %s',
                    $userBan->getEndDate()->format('d.m.Y H:i')
                )
            );
            return;
        }

        $userClubs = $this->speakingClubRepository->findUserUpcoming($user->id, $this->clock->now());
        if (count($userClubs) >= 5) {

            $buttons = [];
            foreach ($userClubs as $club) {
                $buttons[] = [
                    [
                        'text'          => sprintf(
                            '%s - %s',
                            $club->getDate()->format('d.m H:i') . ' ' . DateHelper::getDayOfTheWeek(
                                $club->getDate()->format('d.m.Y')
                            ),
                            $club->getName()
                        ),
                        'callback_data' => sprintf('show_my_speaking_club:%s', $club->getId()->toString()),
                    ],
                ];
            }

            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: "Кажется, ваш календарь переполнен! 📅\n\nВы записаны сразу на 5 клубов вперед. Чтобы добавить шестой, нужно завершить одно из занятий или отменить менее важную бронь.\n\nТак мы даем шанс попасть на практику всем желающим. Спасибо за понимание! ❤️\n\nКакую запись отменим?",
                replyMarkup: $buttons
            );
            return;
        }

        $this->participationRepository->save(
            new Participation(
                id: $this->uuidProvider->provide(),
                userId: $user->id,
                speakingClubId: $command->speakingClubId,
                isPlusOne: false,
            )
        );

        $this->telegram->editMessageText(
            chatId: $command->chatId,
            messageId: $command->messageId,
            text: sprintf(
                '👌 Вы успешно записаны на разговорный клуб "%s", который состоится %s в %s',
                $speakingClub->getName(),
                $speakingClub->getDate()->format('d.m.Y'),
                $speakingClub->getDate()->format('H:i') . ' ' . DateHelper::getDayOfTheWeek(
                    $speakingClub->getDate()->format('d.m.Y')
                ),
            ),
            replyMarkup: [
                [
                    [
                        'text'          => '<< Перейти к списку ваших клубов',
                        'callback_data' => 'back_to_my_list',
                    ],
                ]
            ]
        );

        $waitUserArray = $this->waitingUserRepository->findOneByUserIdAndSpeakingClubId(
            userId: $user->id,
            speakingClubId: $command->speakingClubId,
        );
        if ($waitUserArray !== null) {
            $waitUser = $this->waitingUserRepository->findById($waitUserArray['id']);

            if ($waitUser !== null) {
                $this->waitingUserRepository->remove($waitUser);
            }
        }
    }
}
