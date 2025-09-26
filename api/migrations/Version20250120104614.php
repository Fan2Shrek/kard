<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250120104614 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add game status';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_mode CHANGE active active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE room ADD status VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE room DROP status');
        $this->addSql('ALTER TABLE game_mode CHANGE active active TINYINT(1) DEFAULT 0 NOT NULL');
    }
}
