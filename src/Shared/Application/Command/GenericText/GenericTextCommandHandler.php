<?php

declare(strict_types=1);

namespace App\Shared\Application\Command\GenericText;

use App\Shared\Application\Clock;
use App\Shared\Application\UuidProvider;
use App\Shared\Domain\TelegramInterface;
use App\Shared\Domain\UserRolesProvider;
use App\SpeakingClub\Application\Event\SpeakingClubFreeSpaceAvailableEvent;
use App\SpeakingClub\Application\Event\SpeakingClubScheduleChangedEvent;
use App\SpeakingClub\Domain\Participation;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use App\WaitList\Domain\WaitingUserRepository;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GenericTextCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private SpeakingClubRepository $speakingClubRepository,
        private TelegramInterface $telegram,
        private UuidProvider $uuidProvider,
        private ParticipationRepository $participationRepository,
        private Clock $clock,
        private EventDispatcherInterface $eventDispatcher,
        private UserRolesProvider $userRolesProvider,
        private WaitingUserRepository $waitingUserRepository,
    ) {
    }

    public function __invoke(GenericTextCommand $command): void
    {
        $user = $this->userRepository->findByChatId($command->chatId);
        if ($user === null) {
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_NAME_FOR_CREATING) {
            $data = [];
            $data['name'] = $command->text;

            $user->setState(UserStateEnum::RECEIVING_DESCRIPTION_FOR_CREATION);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage($command->chatId, 'Введите описание клуба');
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_DESCRIPTION_FOR_CREATION) {
            $data = $user->getActualSpeakingClubData();
            $data['description'] = $command->text;

            $user->setState(UserStateEnum::RECEIVING_MAX_PARTICIPANTS_COUNT_FOR_CREATION);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage($command->chatId, 'Введите максимальное количество участников');
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_MAX_PARTICIPANTS_COUNT_FOR_CREATION) {
            if (!is_int((int) $command->text) || (int) $command->text <= 0) {
                $this->telegram->sendMessage($command->chatId, 'Введите целое число больше 0');
                return;
            }

            $data = $user->getActualSpeakingClubData();
            $data['max_participants_count'] = (int) $command->text;

            $user->setState(UserStateEnum::RECEIVING_DATE_FOR_CREATION);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage($command->chatId, 'Введите дату проведения в формате: 15.10.2023 10:00');
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_DATE_FOR_CREATION) {
            $date = DateTimeImmutable::createFromFormat('d.m.Y H:i', $command->text);

            if ($date < $this->clock->now()) {
                $this->telegram->sendMessage($command->chatId, 'Дата не может быть в прошлом, попробуйте еще раз');
                return;
            }

            if ($date === false) {
                $this->telegram->sendMessage($command->chatId, 'Некорректная дата, попробуйте еще раз');
                return;
            }

            $data = $user->getActualSpeakingClubData();
            $speakingClub = new SpeakingClub(
                id: $this->uuidProvider->provide(),
                name: $data['name'],
                description: $data['description'],
                maxParticipantsCount: $data['max_participants_count'],
                date: $date,
            );
            $this->speakingClubRepository->save($speakingClub);

            $user->setState(UserStateEnum::IDLE);
            $user->setActualSpeakingClubData([]);
            $this->userRepository->save($user);

            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Клуб успешно создан',
                replyMarkup: [[
                    [
                        'text' => 'Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_admin_list',
                    ],
                ]],
            );
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_NAME_FOR_EDITING) {
            $data = $user->getActualSpeakingClubData();
            $data['name'] = $command->text;

            $user->setState(UserStateEnum::RECEIVING_DESCRIPTION_FOR_EDITING);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage($command->chatId, 'Введите новое описание клуба');
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_DESCRIPTION_FOR_EDITING) {
            $data = $user->getActualSpeakingClubData();
            $data['description'] = $command->text;

            $user->setState(UserStateEnum::RECEIVING_MAX_PARTICIPANTS_COUNT_FOR_EDITING);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage($command->chatId, 'Введите новое максимальное количество участников');
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_MAX_PARTICIPANTS_COUNT_FOR_EDITING) {
            if (!is_int((int) $command->text) || (int) $command->text <= 0) {
                $this->telegram->sendMessage($command->chatId, 'Введите целое число больше 0');
                return;
            }

            $currentParticipationsCount = $this->participationRepository->countByClubId(
                Uuid::fromString($user->getActualSpeakingClubData()['id'])
            );

            if ($currentParticipationsCount > (int) $command->text) {
                $this->telegram->sendMessage($command->chatId, 'На текущий момент в клубе уже есть участники, ' .
                    'поэтому максимальное количество участников не может быть меньше текущего. Попробуйте еще раз');
                return;
            }

            $data = $user->getActualSpeakingClubData();
            $data['max_participants_count'] = (int) $command->text;

            $user->setState(UserStateEnum::RECEIVING_DATE_FOR_EDITING);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage($command->chatId, 'Введите новую дату проведения в формате: 15.10.2023 10:00');
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_DATE_FOR_EDITING) {
            $date = DateTimeImmutable::createFromFormat('d.m.Y H:i', $command->text);

            if ($date < $this->clock->now()) {
                $this->telegram->sendMessage($command->chatId, 'Дата не может быть в прошлом, попробуйте еще раз');
                return;
            }

            if ($date === false) {
                $this->telegram->sendMessage($command->chatId, 'Некорректная дата, попробуйте еще раз');
                return;
            }

            $data = $user->getActualSpeakingClubData();
            $speakingClub = $this->speakingClubRepository->findById(Uuid::fromString($data['id']));
            if ($speakingClub === null) {
                $this->telegram->sendMessage($command->chatId, 'Редактируемый Клуб не найден');
                return;
            }
            $speakingClub->setName($data['name']);
            $speakingClub->setDescription($data['description']);

            if ($speakingClub->getMaxParticipantsCount() < $data['max_participants_count']) {
                $this->eventDispatcher->dispatch(new SpeakingClubFreeSpaceAvailableEvent($speakingClub->getId()));
            }

            $speakingClub->setMaxParticipantsCount((int) $data['max_participants_count']);

            if ($speakingClub->getDate() !== $date) {
                $this->eventDispatcher->dispatch(new SpeakingClubScheduleChangedEvent($speakingClub->getId()));
            }
            $speakingClub->setDate($date);

            $this->speakingClubRepository->save($speakingClub);

            $user->setState(UserStateEnum::IDLE);
            $user->setActualSpeakingClubData([]);
            $this->userRepository->save($user);

            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Клуб успешно изменен',
                replyMarkup: [[
                    [
                        'text' => 'Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_admin_list',
                    ],
                ]],
            );
        }

        if ($user->getState() === UserStateEnum::RECEIVING_PARTICIPANT) {
            $speakingClubId = Uuid::fromString($user->getActualSpeakingClubData()['participantSpeakingClubId']);

            $speakingClub = $this->speakingClubRepository->findById($speakingClubId);
            if ($speakingClub === null) {
                $this->telegram->sendMessage($command->chatId, 'Клуб не найден');
                return;
            }

            $availableSpaceCount = $speakingClub->getMaxParticipantsCount() -
                $this->participationRepository->countByClubId($speakingClubId);
            if ($availableSpaceCount <= 0) {
                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'В клубе нет свободных мест',
                    replyMarkup: [
                        [[
                            'text' => '<< Вернуться к списку участников',
                            'callback_data' => sprintf('show_participants:%s', $speakingClubId->toString()),
                        ]],
                    ],
                );
                return;
            }

            $participantUser = $this->userRepository->findByUsername($command->text);
            if ($participantUser === null) {
                $user->setState(UserStateEnum::IDLE);
                $user->setActualSpeakingClubData([]);
                $this->userRepository->save($user);

                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Такого пользователя нет в базе бота',
                    replyMarkup: [
                        [[
                            'text' => '<< Вернуться к списку участников',
                            'callback_data' => sprintf('show_participants:%s', $speakingClubId->toString()),
                        ]],
                    ],
                );
                return;
            }

            if ($this->userRolesProvider->isUserAdmin($participantUser->getUsername())) {
                $user->setState(UserStateEnum::IDLE);
                $user->setActualSpeakingClubData([]);
                $this->userRepository->save($user);

                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Нельзя добавить администратора в клуб',
                    replyMarkup: [
                        [[
                            'text' => '<< Вернуться к списку участников',
                            'callback_data' => sprintf('show_participants:%s', $speakingClubId->toString()),
                        ]],
                    ],
                );
                return;
            }

            $participation = $this->participationRepository->findByUserIdAndSpeakingClubId(
                userId: $participantUser->getId(),
                speakingClubId: $speakingClubId
            );
            if ($participation !== null) {
                $user->setState(UserStateEnum::IDLE);
                $user->setActualSpeakingClubData([]);
                $this->userRepository->save($user);

                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Пользователь уже участвует в клубе',
                    replyMarkup: [
                        [[
                            'text' => '<< Вернуться к списку участников',
                            'callback_data' => sprintf('show_participants:%s', $speakingClubId->toString()),
                        ]],
                    ],
                );
                return;
            }

            $this->participationRepository->save(new Participation(
                id: $this->uuidProvider->provide(),
                userId: $participantUser->getId(),
                speakingClubId: $speakingClubId,
                isPlusOne: false,
            ));

            $user->setState(UserStateEnum::IDLE);
            $user->setActualSpeakingClubData([]);
            $this->userRepository->save($user);

            // Notify admin
            $this->telegram->sendMessage(
                chatId: $user->getChatId(),
                text: 'Пользователь успешно добавлен в клуб',
                replyMarkup: [
                    [[
                        'text' => 'Перейти к списку участников',
                        'callback_data' => sprintf('show_participants:%s', $speakingClubId->toString()),
                    ]],
                ],
            );

            // Notify user
            $this->telegram->sendMessage(
                chatId: $participantUser->getChatId(),
                text: sprintf(
                    'Администратор добавил вас в клуб "%s" %s',
                    $speakingClub->getName(),
                    $speakingClub->getDate()->format('d.m.Y H:i'),
                ),
                replyMarkup: [
                    [[
                        'text' => 'Перейти к описанию клуба',
                        'callback_data' => sprintf('show_speaking_club:%s', $speakingClubId->toString()),
                    ]],
                ],
            );

            // Remove user from waiting list
            $waitingUser = $this->waitingUserRepository->findOneByUserIdAndSpeakingClubId(
                userId: $participantUser->getId(),
                speakingClubId: $speakingClubId
            );

            if ($waitingUser !== null) {
                $waitingUserEntity = $this->waitingUserRepository->findById($waitingUser['id']); // TODO: rewrite it
                if ($waitingUserEntity !== null) {
                    $this->waitingUserRepository->remove($waitingUserEntity);
                }
            }
        }
    }
}
