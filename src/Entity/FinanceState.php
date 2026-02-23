<?php

namespace App\Entity;

use App\Repository\FinanceStateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FinanceStateRepository::class)]
#[ORM\Table(name: 'finance_state')]
class FinanceState
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: false)]
    private ?Company $company = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $cashAvailable = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $shareCapital = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $monthlyRent = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $dailyElectricityCost = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 4)]
    private string $taxRate = '0.0000';

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

    public function getCashAvailable(): string
    {
        return $this->cashAvailable;
    }

    public function setCashAvailable(string $cashAvailable): static
    {
        $this->cashAvailable = $cashAvailable;
        return $this;
    }

    public function addCash(string $amount): static
    {
        $this->cashAvailable = (string)((float)$this->cashAvailable + (float)$amount);
        return $this;
    }

    public function subtractCash(string $amount): static
    {
        $this->cashAvailable = (string)((float)$this->cashAvailable - (float)$amount);
        return $this;
    }

    public function getShareCapital(): string
    {
        return $this->shareCapital;
    }

    public function setShareCapital(string $shareCapital): static
    {
        $this->shareCapital = $shareCapital;
        return $this;
    }

    public function getMonthlyRent(): string
    {
        return $this->monthlyRent;
    }

    public function setMonthlyRent(string $monthlyRent): static
    {
        $this->monthlyRent = $monthlyRent;
        return $this;
    }

    public function getDailyRent(): string
    {
        // Loyer mensuel divisé par 30
        return (string)((float)$this->monthlyRent / 30);
    }

    public function getDailyElectricityCost(): string
    {
        return $this->dailyElectricityCost;
    }

    public function setDailyElectricityCost(string $dailyElectricityCost): static
    {
        $this->dailyElectricityCost = $dailyElectricityCost;
        return $this;
    }

    public function getTaxRate(): string
    {
        return $this->taxRate;
    }

    public function setTaxRate(string $taxRate): static
    {
        $this->taxRate = $taxRate;
        return $this;
    }
}
