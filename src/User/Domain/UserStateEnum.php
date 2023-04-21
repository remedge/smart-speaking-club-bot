<?php

declare(strict_types=1);

namespace App\User\Domain;

enum UserStateEnum: string
{
    case IDLE = 'IDLE';
    case RECEIVING_NAME_FOR_CREATING = 'RECEIVING_NAME_FOR_CREATING';
    case RECEIVING_DESCRIPTION_FOR_CREATION = 'RECEIVING_DESCRIPTION_FOR_CREATION';
    case RECEIVING_MAX_PARTICIPANTS_COUNT_FOR_CREATION = 'RECEIVING_MAX_PARTICIPANTS_COUNT_FOR_CREATION';
    case RECEIVING_DATE_FOR_CREATION = 'RECEIVING_DATE_FOR_CREATION';

    case RECEIVING_NAME_FOR_EDITING = 'RECEIVING_NAME_FOR_EDITING';
    case RECEIVING_DESCRIPTION_FOR_EDITING = 'RECEIVING_DESCRIPTION_FOR_EDITING';
    case RECEIVING_MAX_PARTICIPANTS_COUNT_FOR_EDITING = 'RECEIVING_MAX_PARTICIPANTS_COUNT_FOR_EDITING';
    case RECEIVING_DATE_FOR_EDITING = 'RECEIVING_DATE_FOR_EDITING';

    case RECEIVING_PARTICIPANT = 'RECEIVING_PARTICIPANT';

    /**
     * @return array<string, string>
     */
    public static function getAsArray(): array
    {
        return array_reduce(
            self::cases(),
            static fn (array $choices, UserStateEnum $type) => $choices + [
                $type->name => $type->value,
            ],
            [],
        );
    }
}
