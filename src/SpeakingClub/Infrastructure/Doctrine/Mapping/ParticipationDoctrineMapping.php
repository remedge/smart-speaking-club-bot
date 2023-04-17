<?php

declare(strict_types=1);

namespace App\SpeakingClub\Infrastructure\Doctrine\Mapping;

use App\Shared\Infrastructure\Doctrine\Mapping\DoctrineMapping;
use App\SpeakingClub\Domain\Participation;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

class ParticipationDoctrineMapping extends DoctrineMapping
{
    public function __construct()
    {
        parent::__construct(Participation::class);
    }

    public function configure(ClassMetadataBuilder $builder): void
    {
        $builder->setTable('participations');

        $builder->createField('id', 'uuid')
            ->unique()
            ->makePrimaryKey()
            ->build();

        $builder->addField('userId', 'uuid');

        $builder->addField('speakingClubId', 'uuid');

        $builder->addField('isPlusOne', 'boolean');

        $builder->addIndex(['user_id', 'speaking_club_id'], 'participation_user_id_speaking_club_id');
    }
}
