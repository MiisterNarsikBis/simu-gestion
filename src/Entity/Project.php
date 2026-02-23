<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'project')]
class Project
{
    public const TYPE_VITRINE = 'VITRINE';
    public const TYPE_ECOMMERCE = 'ECOMMERCE';
    public const TYPE_LANDING = 'LANDING';

    public const STATUS_NEW = 'NEW';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_DONE = 'DONE';
    public const STATUS_FAILED = 'FAILED';

    public const STAGE_BRIEF = 'BRIEF';
    public const STAGE_WIREFRAME = 'WIREFRAME';
    public const STAGE_DESIGN = 'DESIGN';
    public const STAGE_GRAPHISM = 'GRAPHISM';
    public const STAGE_INTEGRATION = 'INTEGRATION';
    public const STAGE_DEV = 'DEV';
    public const STAGE_QA = 'QA';
    public const STAGE_DELIVERY = 'DELIVERY';

    // Mapping étape -> rôle requis
    public const STAGE_ROLE_MAP = [
        self::STAGE_BRIEF => null, // Pas de rôle spécifique
        self::STAGE_WIREFRAME => null,
        self::STAGE_DESIGN => 'DESIGNER',
        self::STAGE_GRAPHISM => 'GRAPHISTE',
        self::STAGE_INTEGRATION => 'INTEGRATEUR',
        self::STAGE_DEV => 'DEV',
        self::STAGE_QA => null,
        self::STAGE_DELIVERY => null,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: false)]
    private ?Company $company = null;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', nullable: false)]
    private ?Client $client = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $type = self::TYPE_VITRINE;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $budget = '0.00';

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $deadlineSimDay = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $status = self::STATUS_NEW;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $pipelineStage = self::STAGE_BRIEF;

    #[ORM\Column(type: Types::INTEGER)]
    private int $stageProgress = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $quality = 50;

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

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getBudget(): string
    {
        return $this->budget;
    }

    public function setBudget(string $budget): static
    {
        $this->budget = $budget;
        return $this;
    }

    public function getDeadlineSimDay(): ?int
    {
        return $this->deadlineSimDay;
    }

    public function setDeadlineSimDay(?int $deadlineSimDay): static
    {
        $this->deadlineSimDay = $deadlineSimDay;
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

    public function getPipelineStage(): string
    {
        return $this->pipelineStage;
    }

    public function setPipelineStage(string $pipelineStage): static
    {
        $this->pipelineStage = $pipelineStage;
        return $this;
    }

    public function getStageProgress(): int
    {
        return $this->stageProgress;
    }

    public function setStageProgress(int $stageProgress): static
    {
        $this->stageProgress = max(0, min(100, $stageProgress));
        return $this;
    }

    public function addStageProgress(int $progress): static
    {
        $this->stageProgress = min(100, $this->stageProgress + $progress);
        return $this;
    }

    public function getQuality(): int
    {
        return $this->quality;
    }

    public function setQuality(int $quality): static
    {
        $this->quality = max(0, min(100, $quality));
        return $this;
    }

    public function getRequiredRole(): ?string
    {
        return self::STAGE_ROLE_MAP[$this->pipelineStage] ?? null;
    }

    public function getNextStage(): ?string
    {
        $stages = [
            self::STAGE_BRIEF,
            self::STAGE_WIREFRAME,
            self::STAGE_DESIGN,
            self::STAGE_GRAPHISM,
            self::STAGE_INTEGRATION,
            self::STAGE_DEV,
            self::STAGE_QA,
            self::STAGE_DELIVERY,
        ];

        $currentIndex = array_search($this->pipelineStage, $stages);
        if ($currentIndex !== false && $currentIndex < count($stages) - 1) {
            return $stages[$currentIndex + 1];
        }

        return null;
    }

    public function isLastStage(): bool
    {
        return $this->pipelineStage === self::STAGE_DELIVERY;
    }
}
