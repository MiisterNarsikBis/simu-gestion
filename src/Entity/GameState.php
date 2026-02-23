<?php

namespace App\Entity;

use App\Repository\GameStateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameStateRepository::class)]
#[ORM\Table(name: 'game_state')]
class GameState
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: false)]
    private ?Company $company = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $simDay = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastMidnightProcessedAt = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $daysAvailable = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $daysConsumedToday = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $additionalDays = 0;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastRechargeDate = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $timezone = 'Europe/Paris';

    #[ORM\Column(type: Types::INTEGER)]
    private int $globalQualityRating = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $globalSatisfaction = 0;

    public function __construct()
    {
        $this->simDay = 0;
        $this->daysAvailable = 0;
        $this->additionalDays = 0;
        $this->globalQualityRating = 0;
        $this->globalSatisfaction = 0;
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

    public function incrementSimDay(): static
    {
        $this->simDay++;
        return $this;
    }

    public function getLastMidnightProcessedAt(): ?\DateTimeInterface
    {
        return $this->lastMidnightProcessedAt;
    }

    public function setLastMidnightProcessedAt(?\DateTimeInterface $lastMidnightProcessedAt): static
    {
        $this->lastMidnightProcessedAt = $lastMidnightProcessedAt;
        return $this;
    }

    public function getDaysAvailable(): int
    {
        return $this->daysAvailable;
    }

    public function setDaysAvailable(int $daysAvailable): static
    {
        $this->daysAvailable = $daysAvailable;
        return $this;
    }

    public function consumeDay(): bool
    {
        if ($this->daysAvailable > 0) {
            $this->daysAvailable--;
            return true;
        } elseif ($this->additionalDays > 0) {
            $this->additionalDays--;
            return true;
        }
        return false;
    }

    public function getDaysConsumedToday(): ?int
    {
        return $this->daysConsumedToday;
    }

    public function setDaysConsumedToday(?int $daysConsumedToday): static
    {
        $this->daysConsumedToday = $daysConsumedToday;
        return $this;
    }

    public function getAdditionalDays(): int
    {
        return $this->additionalDays;
    }

    public function setAdditionalDays(int $additionalDays): static
    {
        $this->additionalDays = $additionalDays;
        return $this;
    }

    public function addAdditionalDays(int $days): static
    {
        $this->additionalDays += $days;
        return $this;
    }

    public function getLastRechargeDate(): ?\DateTimeInterface
    {
        return $this->lastRechargeDate;
    }

    public function setLastRechargeDate(?\DateTimeInterface $lastRechargeDate): static
    {
        $this->lastRechargeDate = $lastRechargeDate;
        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): static
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function getGlobalQualityRating(): int
    {
        return $this->globalQualityRating;
    }

    public function setGlobalQualityRating(int $globalQualityRating): static
    {
        $this->globalQualityRating = max(0, min(100, $globalQualityRating));
        return $this;
    }

    public function getGlobalSatisfaction(): int
    {
        return $this->globalSatisfaction;
    }

    public function setGlobalSatisfaction(int $globalSatisfaction): static
    {
        $this->globalSatisfaction = max(0, min(100, $globalSatisfaction));
        return $this;
    }
}
