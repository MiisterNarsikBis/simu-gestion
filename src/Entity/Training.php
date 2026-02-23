<?php

namespace App\Entity;

use App\Repository\TrainingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrainingRepository::class)]
#[ORM\Table(name: 'training')]
class Training
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_DONE = 'DONE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(name: 'employee_id', referencedColumnName: 'id', nullable: false)]
    private ?Employee $employee = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $targetStars = 2;

    #[ORM\Column(type: Types::INTEGER)]
    private int $daysTotal = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $daysRemaining = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $cost = '0.00';

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    public function __construct()
    {
        $this->startedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTargetStars(): int
    {
        return $this->targetStars;
    }

    public function setTargetStars(int $targetStars): static
    {
        $this->targetStars = max(1, min(5, $targetStars));
        return $this;
    }

    public function getDaysTotal(): int
    {
        return $this->daysTotal;
    }

    public function setDaysTotal(int $daysTotal): static
    {
        $this->daysTotal = $daysTotal;
        return $this;
    }

    public function getDaysRemaining(): int
    {
        return $this->daysRemaining;
    }

    public function setDaysRemaining(int $daysRemaining): static
    {
        $this->daysRemaining = $daysRemaining;
        return $this;
    }

    public function decrementDaysRemaining(): bool
    {
        if ($this->daysRemaining > 0) {
            $this->daysRemaining--;
            return true;
        }
        return false;
    }

    public function getCost(): string
    {
        return $this->cost;
    }

    public function setCost(string $cost): static
    {
        $this->cost = $cost;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeInterface $startedAt): static
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): static
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isDone(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

    public function isCompleted(): bool
    {
        return $this->daysRemaining <= 0;
    }
}
