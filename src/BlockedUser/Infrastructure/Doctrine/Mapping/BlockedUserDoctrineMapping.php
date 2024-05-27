<?php

declare(strict_types=1);

namespace App\BlockedUser\Infrastructure\Doctrine\Mapping;

use App\BlockedUser\Domain\BlockedUser;
use App\Shared\Infrastructure\Doctrine\Mapping\DoctrineMapping;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

class BlockedUserDoctrineMapping extends DoctrineMapping
{
    public function __construct()
    {
        parent::__construct(BlockedUser::class);
    }

    public function configure(ClassMetadataBuilder $builder): void
    {
        $builder->setTable('blocked_users');

        $builder->createField('id', 'uuid')
            ->unique()
            ->makePrimaryKey()
            ->build();
        $builder->addField('userId', 'uuid');
        $builder->addField('createdAt', 'datetime_immutable');
    }
}
