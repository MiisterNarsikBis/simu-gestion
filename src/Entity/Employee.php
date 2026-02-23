<?php

namespace App\Entity;

use App\Repository\EmployeeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
#[ORM\Table(name: 'employee')]
class Employee
{
    public const ROLE_MANAGER = 'MANAGER';
    public const ROLE_RH = 'RH';
    public const ROLE_DEV = 'DEV';
    public const ROLE_DESIGNER = 'DESIGNER';
    public const ROLE_GRAPHISTE = 'GRAPHISTE';
    public const ROLE_INTEGRATEUR = 'INTEGRATEUR';

    public const STATUS_DISPO = 'DISPO';
    public const STATUS_ARRET = 'ARRET';
    public const STATUS_FORMATION = 'FORMATION';
    public const STATUS_SUR_POSTE = 'SUR_POSTE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: false)]
    private ?Company $company = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name = '';

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $role = self::ROLE_DEV;

    #[ORM\Column(type: Types::INTEGER)]
    private int $trainingStars = 1;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $availabilityStatus = self::STATUS_DISPO;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $salaryDaily = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $skillMultiplier = '1.00';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getTrainingStars(): int
    {
        return $this->trainingStars;
    }

    public function setTrainingStars(int $trainingStars): static
    {
        $this->trainingStars = max(1, min(5, $trainingStars));
        $this->updateSkillMultiplier();
        return $this;
    }

    public function getAvailabilityStatus(): string
    {
        return $this->availabilityStatus;
    }

    public function setAvailabilityStatus(string $availabilityStatus): static
    {
        $this->availabilityStatus = $availabilityStatus;
        return $this;
    }

    public function getSalaryDaily(): string
    {
        return $this->salaryDaily;
    }

    public function setSalaryDaily(string $salaryDaily): static
    {
        $this->salaryDaily = $salaryDaily;
        return $this;
    }

    public function getSkillMultiplier(): string
    {
        return $this->skillMultiplier;
    }

    public function setSkillMultiplier(string $skillMultiplier): static
    {
        $this->skillMultiplier = $skillMultiplier;
        return $this;
    }

    /**
     * Calcule le multiplicateur de compétence basé sur les étoiles
     * étoiles 1 → 1.0
     * étoiles 2 → 1.35
     * étoiles 3 → 1.70
     * étoiles 4 → 2.05
     * étoiles 5 → 2.40
     */
    private function updateSkillMultiplier(): void
    {
        $multipliers = [
            1 => '1.00',
            2 => '1.35',
            3 => '1.70',
            4 => '2.05',
            5 => '2.40',
        ];

        $this->skillMultiplier = $multipliers[$this->trainingStars] ?? '1.00';
    }

    public function isAvailable(): bool
    {
        return $this->availabilityStatus === self::STATUS_DISPO;
    }

    public function isInTraining(): bool
    {
        return $this->availabilityStatus === self::STATUS_FORMATION;
    }

    public function isOnProject(): bool
    {
        return $this->availabilityStatus === self::STATUS_SUR_POSTE;
    }
}
