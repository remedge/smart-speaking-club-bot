<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Doctrine\Mapping;

use App\Shared\Infrastructure\Doctrine\Mapping\DoctrineMapping;
use App\User\Domain\User;
use App\User\Domain\UserStateEnum;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

class UserDoctrineMapping extends DoctrineMapping
{
    public function __construct()
    {
        parent::__construct(User::class);
    }

    public function configure(ClassMetadataBuilder $builder): void
    {
        $builder->setTable('users');

        $builder->createField('id', 'uuid')
            ->unique()
            ->makePrimaryKey()
            ->build();

        $builder->addField('chatId', 'bigint');
        $builder->addField('firstName', 'string', [
            'nullable' => true,
        ]);
        $builder->addField('lastName', 'string', [
            'nullable' => true,
        ]);
        $builder->addField('username', 'string');
        $builder->addField('state', 'string', [
            'enumType' => UserStateEnum::class,
        ]);
        $builder->addField('actualSpeakingClubData', 'array');
        $builder->addField('createdAt', 'datetime_immutable');
    }
}
