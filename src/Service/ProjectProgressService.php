<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\Employee;
use App\Entity\Project;
use App\Entity\ProjectAssignment;
use App\Repository\EmployeeRepository;
use App\Repository\ProjectAssignmentRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProjectProgressService
{
    private const BASE_PROGRESS_GAIN = 10; // Progression de base par jour
    private const BASE_QUALITY_GAIN = 2; // Gain de qualité de base par jour

    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly ProjectAssignmentRepository $projectAssignmentRepository,
        private readonly EmployeeRepository $employeeRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Progression de tous les projets en cours pour une entreprise
     */
    public function progressProjects(Company $company, int $simDay): array
    {
        try {
            $projects = $this->projectRepository->findInProgressByCompany($company->getId());
        } catch (\Exception $e) {
            return [];
        }
        
        $delivered = [];

        foreach ($projects as $project) {
            try {
                // Auto-assigner si nécessaire
                $this->autoAssignEmployee($project);
                
                // Vérifier si le projet peut progresser
                if ($this->canProgress($project)) {
                    $this->progressProject($project, $simDay);
                    
                    // Vérifier si le projet est terminé
                    if ($project->getStatus() === Project::STATUS_DONE) {
                        $delivered[] = $project;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // Erreur lors du flush
        }
        
        return $delivered;
    }

    /**
     * Vérifie si un projet peut progresser
     */
    private function canProgress(Project $project): bool
    {
        // Si le projet n'est pas en cours, il ne peut pas progresser
        if ($project->getStatus() !== Project::STATUS_IN_PROGRESS && $project->getStatus() !== Project::STATUS_NEW) {
            return false;
        }

        // Si le projet est NEW, le passer en IN_PROGRESS
        if ($project->getStatus() === Project::STATUS_NEW) {
            $project->setStatus(Project::STATUS_IN_PROGRESS);
        }

        // Vérifier si l'étape nécessite un rôle
        $requiredRole = $project->getRequiredRole();
        
        // Si pas de rôle requis (BRIEF, WIREFRAME, QA, DELIVERY), progression automatique
        if ($requiredRole === null) {
            return true;
        }

        // Vérifier s'il y a un employé assigné avec le bon rôle (peut être SUR_POSTE, c'est OK)
        $assignments = $this->projectAssignmentRepository->findByProjectAndStage(
            $project->getId(),
            $project->getPipelineStage()
        );

        foreach ($assignments as $assignment) {
            $employee = $assignment->getEmployee();
            // L'employé peut travailler s'il n'est pas en formation
            if ($employee->getRole() === $requiredRole && !$employee->isInTraining()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fait progresser un projet
     */
    private function progressProject(Project $project, int $simDay): void
    {
        $requiredRole = $project->getRequiredRole();
        $totalProgressGain = 0;
        $totalQualityGain = 0;

        // Si un rôle est requis, calculer la progression basée sur les employés assignés
        if ($requiredRole !== null) {
            $assignments = $this->projectAssignmentRepository->findByProjectAndStage(
                $project->getId(),
                $project->getPipelineStage()
            );

            foreach ($assignments as $assignment) {
                $employee = $assignment->getEmployee();
                
                // Vérifier que l'employé a le bon rôle et n'est pas en formation
                // Les employés assignés au projet peuvent travailler même s'ils sont SUR_POSTE
                if ($employee->getRole() === $requiredRole && !$employee->isInTraining()) {
                    // Remettre l'employé disponible au début du tick (s'il était SUR_POSTE)
                    if ($employee->isOnProject()) {
                        $employee->setAvailabilityStatus(Employee::STATUS_DISPO);
                    }
                    
                    $multiplier = (float)$employee->getSkillMultiplier();
                    $progressGain = self::BASE_PROGRESS_GAIN * $multiplier;
                    $qualityGain = self::BASE_QUALITY_GAIN * $multiplier;
                    
                    $totalProgressGain += $progressGain;
                    $totalQualityGain += $qualityGain;
                    
                    // Mettre l'employé sur poste pour ce jour
                    $employee->setAvailabilityStatus(Employee::STATUS_SUR_POSTE);
                }
            }
        } else {
            // Pas de rôle requis, progression automatique
            $totalProgressGain = self::BASE_PROGRESS_GAIN;
            $totalQualityGain = self::BASE_QUALITY_GAIN;
        }

        // Appliquer la progression
        if ($totalProgressGain > 0) {
            $project->addStageProgress((int)$totalProgressGain);
            $project->setQuality(min(100, $project->getQuality() + (int)$totalQualityGain));
        }

        // Vérifier si l'étape est terminée
        if ($project->getStageProgress() >= 100) {
            $this->advanceToNextStage($project);
        }
    }

    /**
     * Passe à l'étape suivante du pipeline
     */
    private function advanceToNextStage(Project $project): void
    {
        $currentStage = $project->getPipelineStage();
        
        // Remettre les employés de l'étape actuelle disponibles
        $assignments = $this->projectAssignmentRepository->findByProjectAndStage(
            $project->getId(),
            $currentStage
        );

        foreach ($assignments as $assignment) {
            $employee = $assignment->getEmployee();
            if ($employee->isOnProject()) {
                $employee->setAvailabilityStatus(Employee::STATUS_DISPO);
            }
        }

        $nextStage = $project->getNextStage();
        
        if ($nextStage === null) {
            // Dernière étape terminée, livrer le projet
            $project->setStatus(Project::STATUS_DONE);
            $project->setPipelineStage(Project::STAGE_DELIVERY);
        } else {
            // Passer à l'étape suivante
            $project->setPipelineStage($nextStage);
            $project->setStageProgress(0);
            
            // Auto-assigner les employés pour la nouvelle étape
            $this->autoAssignEmployee($project);
        }
    }

    /**
     * Auto-assigne tous les employés disponibles au projet
     */
    public function autoAssignEmployee(Project $project): bool
    {
        $requiredRole = $project->getRequiredRole();
        
        if ($requiredRole === null) {
            return true; // Pas besoin d'assignation
        }

        // Récupérer les assignations existantes pour cette étape
        $existingAssignments = $this->projectAssignmentRepository->findByProjectAndStage(
            $project->getId(),
            $project->getPipelineStage()
        );
        
        // Créer un tableau des IDs d'employés déjà assignés
        $assignedEmployeeIds = [];
        foreach ($existingAssignments as $assignment) {
            $assignedEmployeeIds[] = $assignment->getEmployee()->getId();
        }

        // Trouver tous les employés disponibles (DISPO) avec le bon rôle
        $allEmployees = $this->employeeRepository->findByCompany($project->getCompany()->getId());
        $assignedCount = 0;

        foreach ($allEmployees as $employee) {
            // Vérifier que l'employé a le bon rôle, est disponible (ni en formation, ni sur un projet), et n'est pas déjà assigné
            if ($employee->getRole() === $requiredRole
                && !$employee->isInTraining()
                && !$employee->isOnProject()
                && !in_array($employee->getId(), $assignedEmployeeIds)) {
                // Créer l'assignation
                $assignment = new ProjectAssignment();
                $assignment->setProject($project);
                $assignment->setEmployee($employee);
                $assignment->setStage($project->getPipelineStage());
                $assignment->setAllocation(100);

                $employee->setAvailabilityStatus(Employee::STATUS_SUR_POSTE);

                $this->entityManager->persist($assignment);
                $assignedEmployeeIds[] = $employee->getId();
                $assignedCount++;
            }
        }

        if ($assignedCount > 0) {
            $this->entityManager->flush();
            return true;
        }

        return false; // Aucun nouvel employé disponible
    }
}
