<?php

namespace App\Entity;

use App\Repository\LoanRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LoanRepository::class)]
#[ORM\Table(name: 'loan')]
class Loan
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_PAID = 'PAID';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: false)]
    private ?Company $company = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $principal = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 4)]
    private string $annualRate = '0.0400';

    #[ORM\Column(type: Types::INTEGER)]
    private int $durationMonths = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $startSimDay = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $remainingPrincipal = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $monthlyPayment = '0.00';

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $lastPaymentSimDay = null;

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

    public function getPrincipal(): string
    {
        return $this->principal;
    }

    public function setPrincipal(string $principal): static
    {
        $this->principal = $principal;
        return $this;
    }

    public function getAnnualRate(): string
    {
        return $this->annualRate;
    }

    public function setAnnualRate(string $annualRate): static
    {
        $this->annualRate = $annualRate;
        return $this;
    }

    public function getDurationMonths(): int
    {
        return $this->durationMonths;
    }

    public function setDurationMonths(int $durationMonths): static
    {
        $this->durationMonths = $durationMonths;
        return $this;
    }

    public function getStartSimDay(): int
    {
        return $this->startSimDay;
    }

    public function setStartSimDay(int $startSimDay): static
    {
        $this->startSimDay = $startSimDay;
        return $this;
    }

    public function getRemainingPrincipal(): string
    {
        return $this->remainingPrincipal;
    }

    public function setRemainingPrincipal(string $remainingPrincipal): static
    {
        $this->remainingPrincipal = $remainingPrincipal;
        return $this;
    }

    public function getMonthlyPayment(): string
    {
        return $this->monthlyPayment;
    }

    public function setMonthlyPayment(string $monthlyPayment): static
    {
        $this->monthlyPayment = $monthlyPayment;
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

    public function getLastPaymentSimDay(): ?int
    {
        return $this->lastPaymentSimDay;
    }

    public function setLastPaymentSimDay(?int $lastPaymentSimDay): static
    {
        $this->lastPaymentSimDay = $lastPaymentSimDay;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }
}
