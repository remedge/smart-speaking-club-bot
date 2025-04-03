<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250403153025 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('speaking_clubs');
        $table->addColumn('link', 'text', ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('speaking_clubs');
        $table->dropColumn('link');
    }
}
