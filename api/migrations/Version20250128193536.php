<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250128193536 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE order_table (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75B7FBBBA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE order_purchase (order_id INT NOT NULL, purchase_id INT NOT NULL, INDEX IDX_80EF338A8D9F6D38 (order_id), INDEX IDX_80EF338A558FBEB9 (purchase_id), PRIMARY KEY(order_id, purchase_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE order_table ADD CONSTRAINT FK_75B7FBBBA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE order_purchase ADD CONSTRAINT FK_80EF338A8D9F6D38 FOREIGN KEY (order_id) REFERENCES order_table (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_purchase ADD CONSTRAINT FK_80EF338A558FBEB9 FOREIGN KEY (purchase_id) REFERENCES purchase (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_table DROP FOREIGN KEY FK_75B7FBBBA76ED395');
        $this->addSql('ALTER TABLE order_purchase DROP FOREIGN KEY FK_80EF338A8D9F6D38');
        $this->addSql('ALTER TABLE order_purchase DROP FOREIGN KEY FK_80EF338A558FBEB9');
        $this->addSql('DROP TABLE order_table');
        $this->addSql('DROP TABLE order_purchase');
    }
}
