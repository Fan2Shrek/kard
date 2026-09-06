<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826153721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the bots column to room (AI players).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE room ADD bots JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        // existing rows get '' from the ALTER, which json_decode() chokes on
        $this->addSql("UPDATE room SET bots = '[]'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE room DROP bots');
    }
}
