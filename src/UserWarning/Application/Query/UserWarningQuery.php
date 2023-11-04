<?php

declare(strict_types=1);

namespace App\UserWarning\Application\Query;

use App\UserWarning\Application\DTO\UserWarningDTO;
use App\UserWarning\Domain\UserWarningRepository;

class UserWarningQuery
{
    public function __construct(
        private UserWarningRepository $userWarningRepository,
    ) {
    }

    /**
     * @return array<UserWarningDTO>
     */
    public function findAllWarning(): array
    {
        $userWarnings = $this->userWarningRepository->findAllWarning();
        $userWarningDTOs = [];
        foreach ($userWarnings as $userWarning) {
            $userWarningDTOs[] = new UserWarningDTO(
                id: $userWarning['id'],
                username: $userWarning['username'],
                firstName: $userWarning['firstName'],
                lastName: $userWarning['lastName'],
                chatId: (int) $userWarning['chatId'],
            );
        }

        return $userWarningDTOs;
    }
}
