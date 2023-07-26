<?php

declare(strict_types=1);

namespace App\SpeakingClub\Infrastructure\Doctrine\Mapping;

use App\Shared\Infrastructure\Doctrine\Mapping\DoctrineMapping;
use App\SpeakingClub\Domain\Rating;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

class RatingDoctrineMapping extends DoctrineMapping
{
    public function __construct()
    {
        parent::__construct(Rating::class);
    }

    public function configure(ClassMetadataBuilder $builder): void
    {
        $builder->setTable('ratings');

        $builder->createField('id', 'uuid')
            ->unique()
            ->makePrimaryKey()
            ->build();

        $builder->addField('userId', 'uuid');

        $builder->addField('speakingClubId', 'uuid');

        $builder->addField('rating', 'integer');

        $builder->addField('comment', 'text', [
            'nullable' => true,
        ]);

        $builder->addField('isDumped', 'boolean', [
            'default' => false,
        ]);

        $builder->addIndex(['speaking_club_id'], 'rating_speaking_club_id');
    }
}
