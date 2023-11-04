<?php

declare(strict_types=1);

namespace App\SpeakingClub\Infrastructure\Doctrine\Mapping;

use App\Shared\Infrastructure\Doctrine\Mapping\DoctrineMapping;
use App\SpeakingClub\Domain\SpeakingClub;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

class SpeakingClubDoctrineMapping extends DoctrineMapping
{
    public function __construct()
    {
        parent::__construct(SpeakingClub::class);
    }

    public function configure(ClassMetadataBuilder $builder): void
    {
        $builder->setTable('speaking_clubs');

        $builder->createField('id', 'uuid')
            ->unique()
            ->makePrimaryKey()
            ->build();

        $builder->addField('name', 'string');
        $builder->addField('description', 'text');
        $builder->addField('minParticipantsCount', 'integer');
        $builder->addField('maxParticipantsCount', 'integer');
        $builder->addField('date', 'datetime_immutable');
        $builder->addField('isCancelled', 'boolean', [
            'default' => false,
        ]);
        $builder->addField('isArchived', 'boolean', [
            'default' => false,
        ]);
        $builder->addField('isRatingAsked', 'boolean', [
            'default' => false,
        ]);
    }
}
