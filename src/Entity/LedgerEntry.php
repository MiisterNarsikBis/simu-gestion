<?php

namespace App\Entity;

use App\Repository\LedgerEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LedgerEntryRepository::class)]
#[ORM\Table(name: 'ledger_entry')]
#[ORM\Index(name: 'idx_company_simday', columns: ['company_id', 'sim_day'])]
class LedgerEntry
{
    public const TYPE_INCOME = 'INCOME';
    public const TYPE_EXPENSE = 'EXPENSE';

    public const CATEGORY_SALARY = 'SALARY';
    public const CATEGORY_RENT = 'RENT';
    public const CATEGORY_ELECTRICITY = 'ELECTRICITY';
    public const CATEGORY_TAX = 'TAX';
    public const CATEGORY_CLIENT_PAYMENT = 'CLIENT_PAYMENT';
    public const CATEGORY_LOAN_PAYMENT = 'LOAN_PAYMENT';
    public const CATEGORY_LOAN_RECEIVED = 'LOAN_RECEIVED';
    public const CATEGORY_TRAINING_COST = 'TRAINING_COST';
    public const CATEGORY_CAPITAL_INCREASE = 'CAPITAL_INCREASE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: false)]
    private ?Company $company = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $simDay = 0;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $type = self::TYPE_EXPENSE;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $category = '';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $amount = '0.00';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

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

    public function getSimDay(): int
    {
        return $this->simDay;
    }

    public function setSimDay(int $simDay): static
    {
        $this->simDay = $simDay;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        if (!in_array($type, [self::TYPE_INCOME, self::TYPE_EXPENSE])) {
            throw new \InvalidArgumentException('Invalid type');
        }
        $this->type = $type;
        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function isIncome(): bool
    {
        return $this->type === self::TYPE_INCOME;
    }

    public function isExpense(): bool
    {
        return $this->type === self::TYPE_EXPENSE;
    }
}
