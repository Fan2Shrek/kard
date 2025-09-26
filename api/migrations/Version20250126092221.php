<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250126092221 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add purchases';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE purchase (id INT AUTO_INCREMENT NOT NULL, price INT NOT NULL, name VARCHAR(255) NOT NULL, discr VARCHAR(255) NOT NULL, duration INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE purchase');
    }
}
