<?php

declare(strict_types=1);

namespace App\UserWarning\Infrastructure\Doctrine\Mapping;

use App\Shared\Infrastructure\Doctrine\Mapping\DoctrineMapping;
use App\UserWarning\Domain\UserWarning;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

class UserWarningDoctrineMapping extends DoctrineMapping
{
    public function __construct()
    {
        parent::__construct(UserWarning::class);
    }

    public function configure(ClassMetadataBuilder $builder): void
    {
        $builder->setTable('user_warnings');

        $builder->createField('id', 'uuid')
            ->unique()
            ->makePrimaryKey()
            ->build();
        $builder->addField('userId', 'uuid');
        $builder->addField('createdAt', 'datetime_immutable');
    }
}
