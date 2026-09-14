<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260906094437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Record how a game was won: number of moves and duration (solitaire)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE result ADD moves INT DEFAULT NULL, ADD duration_seconds INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE result DROP moves, DROP duration_seconds');
    }
}
