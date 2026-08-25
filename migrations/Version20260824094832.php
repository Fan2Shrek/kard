<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260824094832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE game_event_log (
              id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)',
              room_id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)',
              type VARCHAR(255) NOT NULL,
              payload JSON NOT NULL COMMENT '(DC2Type:json)',
              created_at DATETIME NOT NULL,
              INDEX IDX_4C566C1454177093 (room_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              game_event_log
            ADD
              CONSTRAINT FK_4C566C1454177093 FOREIGN KEY (room_id) REFERENCES room (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_event_log DROP FOREIGN KEY FK_4C566C1454177093');
        $this->addSql('DROP TABLE game_event_log');
    }
}
