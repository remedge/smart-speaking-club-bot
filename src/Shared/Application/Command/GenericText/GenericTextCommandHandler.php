<?php

declare(strict_types=1);

namespace App\Shared\Application\Command\GenericText;

use App\Shared\Application\Clock;
use App\Shared\Application\UuidProvider;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GenericTextCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private SpeakingClubRepository $speakingClubRepository,
        private TelegramInterface $telegram,
        private UuidProvider $uuidProvider,
        private Clock $clock,
    )
    {
    }

    public function __invoke(GenericTextCommand $command): void
    {
        $user = $this->userRepository->getByChatId($command->chatId);

        if ($user->getState() === UserStateEnum::RECEIVING_NAME_FOR_CREATING) {
            $data = [];
            $data['name'] = $command->text;

            $user->setState(UserStateEnum::RECEIVING_DESCRIPTION_FOR_CREATION);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Введите описание клуба'
            );
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_DESCRIPTION_FOR_CREATION) {
            $data = $user->getActualSpeakingClubData();
            $data['description'] = $command->text;

            $user->setState(UserStateEnum::RECEIVING_MAX_PARTICIPANTS_COUNT_FOR_CREATION);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Введите максимальное количество участников'
            );
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_MAX_PARTICIPANTS_COUNT_FOR_CREATION) {
            if (!is_int((int)$command->text)) {
                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Введите число'
                );
            }

            $data = $user->getActualSpeakingClubData();
            $data['max_participants_count'] = (int)$command->text;

            $user->setState(UserStateEnum::RECEIVING_DATE_FOR_CREATION);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Введите дату проведения в формате: 15-10-2023 10:00'
            );
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_DATE_FOR_CREATION) {
            $date = DateTimeImmutable::createFromFormat('d-m-Y H:i', $command->text);

            if ($date < $this->clock->now()) {
                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Дата не может быть в прошлом, попробуйте еще раз'
                );
                return;
            }

            if ($date === false) {
                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Введите дату в формате: 15-10-2023 10:00'
                );
                return;
            }

            $data = $user->getActualSpeakingClubData();
            $speakingClub = new SpeakingClub(
                id: $this->uuidProvider->provide(),
                name: $data['name'],
                description: $data['description'],
                maxParticipantsCount: (int)$data['max_participants_count'],
                date: $date,
            );
            $this->speakingClubRepository->save($speakingClub);

            $user->setState(UserStateEnum::IDLE);
            $user->setActualSpeakingClubData([]);
            $this->userRepository->save($user);

            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Клуб успешно создан'
            );
        }
    }
}