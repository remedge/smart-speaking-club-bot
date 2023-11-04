<?php

declare(strict_types=1);

namespace App\UserBan\Infrastructure\Doctrine\Mapping;

use App\Shared\Infrastructure\Doctrine\Mapping\DoctrineMapping;
use App\UserBan\Domain\UserBan;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

class UserBanDoctrineMapping extends DoctrineMapping
{
    public function __construct()
    {
        parent::__construct(UserBan::class);
    }

    public function configure(ClassMetadataBuilder $builder): void
    {
        $builder->setTable('user_bans');

        $builder->createField('id', 'uuid')
            ->unique()
            ->makePrimaryKey()
            ->build();
        $builder->addField('userId', 'uuid');
        $builder->addField('endDate', 'datetime_immutable');
        $builder->addField('createdAt', 'datetime_immutable');
    }
}
