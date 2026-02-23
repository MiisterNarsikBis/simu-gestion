<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223195829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, satisfaction INT NOT NULL, company_id INT NOT NULL, INDEX IDX_C7440455979B1AD6 (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, budget NUMERIC(15, 2) NOT NULL, deadline_sim_day INT DEFAULT NULL, status VARCHAR(20) NOT NULL, pipeline_stage VARCHAR(50) NOT NULL, stage_progress INT NOT NULL, quality INT NOT NULL, company_id INT NOT NULL, client_id INT NOT NULL, INDEX IDX_2FB3D0EE979B1AD6 (company_id), INDEX IDX_2FB3D0EE19EB6921 (client_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE project_assignment (id INT AUTO_INCREMENT NOT NULL, stage VARCHAR(50) NOT NULL, allocation INT NOT NULL, project_id INT NOT NULL, employee_id INT NOT NULL, INDEX IDX_28633F88166D1F9C (project_id), INDEX IDX_28633F888C03F15C (employee_id), UNIQUE INDEX unique_project_employee_stage (project_id, employee_id, stage), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE project_assignment ADD CONSTRAINT FK_28633F88166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_assignment ADD CONSTRAINT FK_28633F888C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455979B1AD6');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE979B1AD6');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE19EB6921');
        $this->addSql('ALTER TABLE project_assignment DROP FOREIGN KEY FK_28633F88166D1F9C');
        $this->addSql('ALTER TABLE project_assignment DROP FOREIGN KEY FK_28633F888C03F15C');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE project_assignment');
    }
}
