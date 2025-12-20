<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251213203817 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add plus_one_name field to participations table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participations ADD plus_one_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participations DROP COLUMN plus_one_name');
    }
}
