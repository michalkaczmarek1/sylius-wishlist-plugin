<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250729162757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sylius_academy_wishlist_wishlist (id INT AUTO_INCREMENT NOT NULL, customer_id INT DEFAULT NULL, wishlist_token VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_ED888A2B9395C3F3 (customer_id), UNIQUE INDEX UNIQ_ED888A2B9395C3F3B92C9C3 (customer_id, wishlist_token), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_academy_wishlist_wishlist_product (id INT AUTO_INCREMENT NOT NULL, wishlist_id INT NOT NULL, product_variant_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_DDFBBB7FB8E54CD (wishlist_id), INDEX IDX_DDFBBB7A80EF684 (product_variant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sylius_academy_wishlist_wishlist ADD CONSTRAINT FK_ED888A2B9395C3F3 FOREIGN KEY (customer_id) REFERENCES sylius_shop_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_academy_wishlist_wishlist_product ADD CONSTRAINT FK_DDFBBB7FB8E54CD FOREIGN KEY (wishlist_id) REFERENCES sylius_academy_wishlist_wishlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_academy_wishlist_wishlist_product ADD CONSTRAINT FK_DDFBBB7A80EF684 FOREIGN KEY (product_variant_id) REFERENCES sylius_product_variant (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sylius_academy_wishlist_wishlist DROP FOREIGN KEY FK_ED888A2B9395C3F3');
        $this->addSql('ALTER TABLE sylius_academy_wishlist_wishlist_product DROP FOREIGN KEY FK_DDFBBB7FB8E54CD');
        $this->addSql('ALTER TABLE sylius_academy_wishlist_wishlist_product DROP FOREIGN KEY FK_DDFBBB7A80EF684');
        $this->addSql('DROP TABLE sylius_academy_wishlist_wishlist');
        $this->addSql('DROP TABLE sylius_academy_wishlist_wishlist_product');
    }
}
