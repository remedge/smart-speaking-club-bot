<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\User\Application\Dto\UserDTO;
use App\User\Application\Exception\UserNotFoundException;
use App\User\Domain\UserRepository;
use Ramsey\Uuid\UuidInterface;

class UserQuery
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function getByChatId(int $chatId): UserDTO
    {
        $user = $this->userRepository->findByChatId($chatId);

        if ($user === null) {
            throw new UserNotFoundException($chatId);
        }

        return new UserDTO(
            id: $user->getId(),
            chatId: $user->getChatId(),
            firstName: $user->getFirstName(),
            lastName: $user->getLastName(),
            username: $user->getUsername(),
        );
    }

    public function findById(UuidInterface $id): ?UserDTO
    {
        $user = $this->userRepository->findById($id);

        if ($user === null) {
            return null;
        }

        return new UserDTO(
            id: $user->getId(),
            chatId: $user->getChatId(),
            firstName: $user->getFirstName(),
            lastName: $user->getLastName(),
            username: $user->getUsername(),
        );
    }
}
