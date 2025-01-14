<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250114102948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add leaderboard and result tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE leaderboard (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', player_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', wins_number INT NOT NULL, INDEX IDX_182E525399E6F5DF (player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE result (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', winner_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', game_mode_id INT NOT NULL, date DATETIME NOT NULL, INDEX IDX_136AC1135DFCD4B8 (winner_id), INDEX IDX_136AC113E227FA65 (game_mode_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE leaderboard ADD CONSTRAINT FK_182E525399E6F5DF FOREIGN KEY (player_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC1135DFCD4B8 FOREIGN KEY (winner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC113E227FA65 FOREIGN KEY (game_mode_id) REFERENCES game_mode (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE leaderboard DROP FOREIGN KEY FK_182E525399E6F5DF');
        $this->addSql('ALTER TABLE result DROP FOREIGN KEY FK_136AC1135DFCD4B8');
        $this->addSql('ALTER TABLE result DROP FOREIGN KEY FK_136AC113E227FA65');
        $this->addSql('DROP TABLE leaderboard');
        $this->addSql('DROP TABLE result');
    }
}
