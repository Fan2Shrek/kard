<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250109202059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add basics entities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE game_mode (id INT AUTO_INCREMENT NOT NULL, value VARCHAR(255) NOT NULL, active TINYINT(1) DEFAULT 0 NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE room (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', owner_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', game_mode_id INT NOT NULL, INDEX IDX_729F519B7E3C61F9 (owner_id), INDEX IDX_729F519BE227FA65 (game_mode_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE room_user (room_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_EE973C2D54177093 (room_id), INDEX IDX_EE973C2DA76ED395 (user_id), PRIMARY KEY(room_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', username VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE room ADD CONSTRAINT FK_729F519B7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE room ADD CONSTRAINT FK_729F519BE227FA65 FOREIGN KEY (game_mode_id) REFERENCES game_mode (id)');
        $this->addSql('ALTER TABLE room_user ADD CONSTRAINT FK_EE973C2D54177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE room_user ADD CONSTRAINT FK_EE973C2DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE room DROP FOREIGN KEY FK_729F519B7E3C61F9');
        $this->addSql('ALTER TABLE room DROP FOREIGN KEY FK_729F519BE227FA65');
        $this->addSql('ALTER TABLE room_user DROP FOREIGN KEY FK_EE973C2D54177093');
        $this->addSql('ALTER TABLE room_user DROP FOREIGN KEY FK_EE973C2DA76ED395');
        $this->addSql('DROP TABLE game_mode');
        $this->addSql('DROP TABLE room');
        $this->addSql('DROP TABLE room_user');
        $this->addSql('DROP TABLE `user`');
    }
}
