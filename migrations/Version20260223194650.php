<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223194650 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE company (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, owner_user_id INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE game_state (id INT AUTO_INCREMENT NOT NULL, sim_day INT NOT NULL, last_midnight_processed_at DATETIME DEFAULT NULL, days_available INT NOT NULL, days_consumed_today INT DEFAULT NULL, additional_days INT NOT NULL, last_recharge_date DATE DEFAULT NULL, timezone VARCHAR(50) NOT NULL, global_quality_rating INT NOT NULL, global_satisfaction INT NOT NULL, company_id INT NOT NULL, INDEX IDX_91A0AB74979B1AD6 (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE game_state ADD CONSTRAINT FK_91A0AB74979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_state DROP FOREIGN KEY FK_91A0AB74979B1AD6');
        $this->addSql('DROP TABLE company');
        $this->addSql('DROP TABLE game_state');
    }
}
