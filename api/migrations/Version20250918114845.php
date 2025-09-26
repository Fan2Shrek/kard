<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250918114845 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE result ADD room_id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)', CHANGE game_mode_id game_mode_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE result ADD CONSTRAINT FK_136AC11354177093 FOREIGN KEY (room_id) REFERENCES room (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_136AC11354177093 ON result (room_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE result DROP FOREIGN KEY FK_136AC11354177093
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_136AC11354177093 ON result
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE result DROP room_id, CHANGE game_mode_id game_mode_id INT NOT NULL
        SQL);
    }
}
