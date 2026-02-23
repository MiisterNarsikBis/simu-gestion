<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\Employee;
use App\Entity\FinanceState;
use App\Entity\LedgerEntry;
use App\Entity\Training;
use App\Repository\EmployeeRepository;
use App\Repository\FinanceStateRepository;
use App\Repository\TrainingRepository;
use Doctrine\ORM\EntityManagerInterface;

class TrainingService
{
    public function __construct(
        private readonly TrainingRepository $trainingRepository,
        private readonly EmployeeRepository $employeeRepository,
        private readonly FinanceStateRepository $financeStateRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Démarre une formation pour un employé
     */
    public function startTraining(Employee $employee, int $targetStars, int $daysTotal, string $cost): Training
    {
        // Vérifier que l'employé n'est pas déjà en formation
        if ($employee->isInTraining()) {
            throw new \RuntimeException('L\'employé est déjà en formation');
        }

        // Vérifier que l'entreprise a assez de trésorerie
        $financeState = $this->financeStateRepository->findOneBy(['company' => $employee->getCompany()]);
        if (!$financeState) {
            throw new \RuntimeException('FinanceState introuvable');
        }

        $costFloat = (float)$cost;
        if ((float)$financeState->getCashAvailable() < $costFloat) {
            throw new \RuntimeException('Trésorerie insuffisante pour cette formation');
        }

        // Créer la formation
        $training = new Training();
        $training->setEmployee($employee);
        $training->setTargetStars($targetStars);
        $training->setDaysTotal($daysTotal);
        $training->setDaysRemaining($daysTotal);
        $training->setCost($cost);
        $training->setStatus(Training::STATUS_ACTIVE);

        // Débiter la trésorerie
        $financeState->subtractCash($cost);

        // Créer l'entrée de journal
        $this->createLedgerEntry(
            $employee->getCompany(),
            0, // simDay sera mis à jour lors du tick
            LedgerEntry::TYPE_EXPENSE,
            LedgerEntry::CATEGORY_TRAINING_COST,
            $cost,
            'Formation ' . $employee->getName() . ' → ' . $targetStars . ' étoiles'
        );

        // Mettre l'employé en formation
        $employee->setAvailabilityStatus(Employee::STATUS_FORMATION);

        $this->entityManager->persist($training);
        $this->entityManager->flush();

        return $training;
    }

    /**
     * Progression des formations actives (appelé lors du tick)
     */
    public function progressTrainings(Company $company, int $simDay): array
    {
        try {
            $trainings = $this->trainingRepository->findActiveByCompany($company->getId());
        } catch (\Exception $e) {
            return [];
        }
        
        $completed = [];

        foreach ($trainings as $training) {
            try {
                $employee = $training->getEmployee();
                
                // Décrémenter les jours restants
                if ($training->decrementDaysRemaining()) {
                    // Si la formation est terminée
                    if ($training->isCompleted()) {
                        $this->completeTraining($training, $simDay);
                        $completed[] = $training;
                    }
                }
            } catch (\Exception $e) {
                // Ignorer les erreurs sur une formation et continuer
                continue;
            }
        }

        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // Erreur lors du flush
        }
        
        return $completed;
    }

    /**
     * Termine une formation et met à jour l'employé
     */
    private function completeTraining(Training $training, int $simDay): void
    {
        $employee = $training->getEmployee();
        
        // Calculer les étoiles gagnées
        $oldStars = $employee->getTrainingStars();
        $newStars = $training->getTargetStars();
        $starsGained = max(0, $newStars - $oldStars);
        
        // Mettre à jour les étoiles de l'employé
        $employee->setTrainingStars($newStars);
        
        // Augmenter le salaire selon les étoiles gagnées
        // Chaque étoile supplémentaire augmente le salaire de 15%
        if ($starsGained > 0) {
            $currentSalary = (float)$employee->getSalaryDaily();
            $salaryIncrease = $currentSalary * 0.15 * $starsGained;
            $newSalary = $currentSalary + $salaryIncrease;
            $employee->setSalaryDaily((string)round($newSalary, 2));
        }
        
        // Remettre l'employé disponible
        $employee->setAvailabilityStatus(Employee::STATUS_DISPO);
        
        // Marquer la formation comme terminée
        $training->setStatus(Training::STATUS_DONE);
        $training->setCompletedAt(new \DateTime());
    }

    /**
     * Crée une entrée dans le journal comptable
     */
    private function createLedgerEntry(
        Company $company,
        int $simDay,
        string $type,
        string $category,
        string $amount,
        string $label
    ): LedgerEntry {
        $entry = new LedgerEntry();
        $entry->setCompany($company);
        $entry->setSimDay($simDay);
        $entry->setType($type);
        $entry->setCategory($category);
        $entry->setAmount($amount);
        $entry->setLabel($label);

        $this->entityManager->persist($entry);
        return $entry;
    }
}
