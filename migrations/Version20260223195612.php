<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223195612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE employee (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, role VARCHAR(50) NOT NULL, training_stars INT NOT NULL, availability_status VARCHAR(50) NOT NULL, salary_daily NUMERIC(10, 2) NOT NULL, skill_multiplier NUMERIC(5, 2) NOT NULL, company_id INT NOT NULL, INDEX IDX_5D9F75A1979B1AD6 (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE training (id INT AUTO_INCREMENT NOT NULL, target_stars INT NOT NULL, days_total INT NOT NULL, days_remaining INT NOT NULL, cost NUMERIC(10, 2) NOT NULL, status VARCHAR(20) NOT NULL, started_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, employee_id INT NOT NULL, INDEX IDX_D5128A8F8C03F15C (employee_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE employee ADD CONSTRAINT FK_5D9F75A1979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE training ADD CONSTRAINT FK_D5128A8F8C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employee DROP FOREIGN KEY FK_5D9F75A1979B1AD6');
        $this->addSql('ALTER TABLE training DROP FOREIGN KEY FK_D5128A8F8C03F15C');
        $this->addSql('DROP TABLE employee');
        $this->addSql('DROP TABLE training');
    }
}
