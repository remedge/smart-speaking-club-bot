<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230618170256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ratings (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', speaking_club_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', rating INT NOT NULL, comment LONGTEXT DEFAULT NULL, INDEX rating_speaking_club_id (speaking_club_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE speaking_clubs ADD is_rating_asked TINYINT(1) DEFAULT 0 NOT NULL');
    }


    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE ratings');
        $this->addSql('ALTER TABLE speaking_clubs DROP is_rating_asked');
    }
}
