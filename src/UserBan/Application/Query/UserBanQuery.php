<?php

declare(strict_types=1);

namespace App\UserBan\Application\Query;

use App\UserBan\Application\DTO\UserBanDTO;
use App\UserBan\Domain\UserBanRepository;

class UserBanQuery
{
    public function __construct(
        private UserBanRepository $userBanRepository,
    ) {
    }

    /**
     * @return array<UserBanDTO>
     */
    public function findAllBan(): array
    {
        $userBans = $this->userBanRepository->findAllBan();
        $userBanDTOs = [];
        foreach ($userBans as $userBan) {
            $userBanDTOs[] = new UserBanDTO(
                id: $userBan['id'],
                username: $userBan['username'],
                firstName: $userBan['firstName'],
                lastName: $userBan['lastName'],
                chatId: (int) $userBan['chatId'],
            );
        }

        return $userBanDTOs;
    }
}
