<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Company;
use App\Entity\GameState;
use App\Entity\Project;
use App\Repository\GameStateRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProjectSeedService
{
    private const CLIENT_NAMES = [
        'TechCorp', 'Digital Solutions', 'Web Agency Pro', 'Creative Studio', 'Innovation Labs',
        'StartupHub', 'Business Plus', 'Marketing Experts', 'Design Co', 'Dev Team'
    ];

    private const PROJECT_TYPES = [
        Project::TYPE_VITRINE,
        Project::TYPE_ECOMMERCE,
        Project::TYPE_LANDING,
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameStateRepository $gameStateRepository
    ) {
    }

    /**
     * Génère un client aléatoire
     */
    public function generateClient(Company $company): Client
    {
        $name = self::CLIENT_NAMES[array_rand(self::CLIENT_NAMES)] . ' ' . rand(1, 999);
        $satisfaction = rand(40, 60); // Satisfaction initiale entre 40 et 60

        $client = new Client();
        $client->setCompany($company);
        $client->setName($name);
        $client->setSatisfaction($satisfaction);

        $this->entityManager->persist($client);
        return $client;
    }

    /**
     * Génère un projet aléatoire pour un client
     */
    public function generateProject(Company $company, Client $client, int $simDay): Project
    {
        $type = self::PROJECT_TYPES[array_rand(self::PROJECT_TYPES)];
        
        // Budget selon le type
        $budgetRanges = [
            Project::TYPE_VITRINE => [2000, 5000],
            Project::TYPE_ECOMMERCE => [5000, 15000],
            Project::TYPE_LANDING => [1000, 3000],
        ];
        
        $range = $budgetRanges[$type];
        $budget = (string)rand($range[0], $range[1]) . '.00';
        
        // Deadline optionnelle (30-60 jours)
        $deadline = $simDay + rand(30, 60);

        $project = new Project();
        $project->setCompany($company);
        $project->setClient($client);
        $project->setType($type);
        $project->setBudget($budget);
        $project->setDeadlineSimDay($deadline);
        $project->setStatus(Project::STATUS_NEW);
        $project->setPipelineStage(Project::STAGE_BRIEF);
        $project->setStageProgress(0);
        $project->setQuality(50);

        $this->entityManager->persist($project);
        return $project;
    }

    /**
     * Génère un client avec un projet
     */
    public function generateClientWithProject(Company $company): array
    {
        // Récupérer le simDay actuel
        $gameState = $this->gameStateRepository->findOneBy(['company' => $company]);
        $simDay = $gameState ? $gameState->getSimDay() : 0;

        $client = $this->generateClient($company);
        $this->entityManager->flush(); // Flush pour avoir l'ID du client
        
        $project = $this->generateProject($company, $client, $simDay);
        $this->entityManager->flush();

        return [
            'client' => $client,
            'project' => $project,
        ];
    }
}
