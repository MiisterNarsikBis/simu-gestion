<?php

namespace App\Entity;

use App\Repository\ProjectAssignmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectAssignmentRepository::class)]
#[ORM\Table(name: 'project_assignment')]
#[ORM\UniqueConstraint(name: 'unique_project_employee_stage', columns: ['project_id', 'employee_id', 'stage'])]
class ProjectAssignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(name: 'project_id', referencedColumnName: 'id', nullable: false)]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(name: 'employee_id', referencedColumnName: 'id', nullable: false)]
    private ?Employee $employee = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $stage = '';

    #[ORM\Column(type: Types::INTEGER)]
    private int $allocation = 100; // Pourcentage (100 = 100%)

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;
        return $this;
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): static
    {
        $this->employee = $employee;
        return $this;
    }

    public function getStage(): string
    {
        return $this->stage;
    }

    public function setStage(string $stage): static
    {
        $this->stage = $stage;
        return $this;
    }

    public function getAllocation(): int
    {
        return $this->allocation;
    }

    public function setAllocation(int $allocation): static
    {
        $this->allocation = max(0, min(100, $allocation));
        return $this;
    }
}
