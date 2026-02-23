<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223200412 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE loan (id INT AUTO_INCREMENT NOT NULL, principal NUMERIC(15, 2) NOT NULL, annual_rate NUMERIC(5, 4) NOT NULL, duration_months INT NOT NULL, start_sim_day INT NOT NULL, remaining_principal NUMERIC(15, 2) NOT NULL, monthly_payment NUMERIC(15, 2) NOT NULL, status VARCHAR(20) NOT NULL, last_payment_sim_day INT DEFAULT NULL, company_id INT NOT NULL, INDEX IDX_C5D30D03979B1AD6 (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE loan ADD CONSTRAINT FK_C5D30D03979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE loan DROP FOREIGN KEY FK_C5D30D03979B1AD6');
        $this->addSql('DROP TABLE loan');
    }
}
