<?php

declare(strict_types=1);

namespace App\SpeakingClub\Presentation\Cli;

use App\Shared\Application\Clock;
use App\Shared\Domain\TelegramInterface;
use App\Shared\Domain\UserRolesProvider;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\User\Domain\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:notify-users-canceled', description: 'Every hour speaking club canceled check')]
class NotifyUsersAboutCanceledClubsCommand extends Command
{
    public function __construct(
        private SpeakingClubRepository $speakingClubRepository,
        private ParticipationRepository $participationRepository,
        private UserRepository $userRepository,
        private UserRolesProvider $userRolesProvider,
        private Clock $clock,
        private TelegramInterface $telegram,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startDate = $this->clock->now()->modify('+24 hours');
        $startDate = $startDate->setTime((int) $startDate->format('H'), 0, 0);

        $endDate = $this->clock->now()->modify('+24 hours');
        $endDate = $endDate->setTime((int) $endDate->format('H'), 59, 0);

        $speakingClubs = $this->speakingClubRepository->findBetweenDates($startDate, $endDate);
        $adminNames = $this->userRolesProvider->getAdminUsernames();

        foreach ($speakingClubs as $speakingClub) {
            $participations = $this->participationRepository->findBySpeakingClubId($speakingClub->getId());
            if ($speakingClub->getMinParticipantsCount() > $this->participationRepository->countByClubId($speakingClub->getId())) {
                $exceptUsers = array_column($participations, 'username');
                $recipients = $this->userRepository->findAllExceptUsernames($adminNames + $exceptUsers);

                foreach ($recipients as $recipient) {
                    $this->telegram->sendMessage(
                        chatId: $recipient->getChatId(),
                        text: sprintf('Разговорный клуб "%s" начнется через 24 часов. Описание клуба: %s. Вы еще можете записаться.', $speakingClub->getName(), $speakingClub->getDescription()),
                    );
                }
            }
        }

        $startDate = $this->clock->now()->modify('+3 hours');
        $startDate = $startDate->setTime((int) $startDate->format('H'), 0, 0);

        $endDate = $this->clock->now()->modify('+3 hours');
        $endDate = $endDate->setTime((int) $endDate->format('H'), 59, 0);

        $speakingClubs = $this->speakingClubRepository->findBetweenDates($startDate, $endDate);

        foreach ($speakingClubs as $speakingClub) {
            $participations = $this->participationRepository->findBySpeakingClubId($speakingClub->getId());

            if ($speakingClub->getMinParticipantsCount() > $this->participationRepository->countByClubId($speakingClub->getId())) {
                $speakingClub->cancel();
                $this->speakingClubRepository->save($speakingClub);
                foreach ($participations as $participation) {
                    $this->telegram->sendMessage(
                        (int) $participation['chatId'],
                        sprintf('Разговорный клуб "%s" отменен в связи с недостаточным количеством участников.', $speakingClub->getName())
                    );
                }

                $admins = $this->userRepository->findAllIncludeUsernames($adminNames);
                foreach ($admins as $admin) {
                    $this->telegram->sendMessage(
                        chatId: $admin->getChatId(),
                        text: sprintf('Разговорный клуб "%s" отменен в связи с недостаточным количеством участников.', $speakingClub->getName()),
                    );
                }
            }
        }

        return Command::SUCCESS;
    }
}
