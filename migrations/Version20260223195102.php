<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223195102 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE finance_state (id INT AUTO_INCREMENT NOT NULL, cash_available NUMERIC(15, 2) NOT NULL, share_capital NUMERIC(15, 2) NOT NULL, monthly_rent NUMERIC(10, 2) NOT NULL, daily_electricity_cost NUMERIC(10, 2) NOT NULL, tax_rate NUMERIC(5, 4) NOT NULL, company_id INT NOT NULL, INDEX IDX_A33CC600979B1AD6 (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ledger_entry (id INT AUTO_INCREMENT NOT NULL, sim_day INT NOT NULL, type VARCHAR(20) NOT NULL, category VARCHAR(50) NOT NULL, amount NUMERIC(15, 2) NOT NULL, label VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, company_id INT NOT NULL, INDEX IDX_64272A69979B1AD6 (company_id), INDEX idx_company_simday (company_id, sim_day), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE finance_state ADD CONSTRAINT FK_A33CC600979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE ledger_entry ADD CONSTRAINT FK_64272A69979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE finance_state DROP FOREIGN KEY FK_A33CC600979B1AD6');
        $this->addSql('ALTER TABLE ledger_entry DROP FOREIGN KEY FK_64272A69979B1AD6');
        $this->addSql('DROP TABLE finance_state');
        $this->addSql('DROP TABLE ledger_entry');
    }
}
