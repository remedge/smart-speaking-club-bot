<?php

declare(strict_types=1);

namespace App\WaitList\Infrastructure\Doctrine\Mapping;

use App\Shared\Infrastructure\Doctrine\Mapping\DoctrineMapping;
use App\WaitList\Domain\WaitingUser;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

class WaitingUserDoctrineMapping extends DoctrineMapping
{
    public function __construct()
    {
        parent::__construct(WaitingUser::class);
    }

    public function configure(ClassMetadataBuilder $builder): void
    {
        $builder->setTable('waiting_users');

        $builder->createField('id', 'uuid')
            ->unique()
            ->makePrimaryKey()
            ->build();

        $builder->addField('userId', 'uuid');

        $builder->addField('speakingClubId', 'uuid');

        $builder->addUniqueConstraint(['user_id', 'speaking_club_id'], 'waiting_user_user_id_speaking_club_id');
    }
}
