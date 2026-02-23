<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\FinanceState;
use App\Entity\LedgerEntry;
use App\Repository\EmployeeRepository;
use App\Repository\FinanceStateRepository;
use Doctrine\ORM\EntityManagerInterface;

class DailyCostService
{
    public function __construct(
        private readonly FinanceStateRepository $financeStateRepository,
        private readonly EmployeeRepository $employeeRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Applique tous les coûts journaliers pour une entreprise
     * @return array Résumé des coûts appliqués
     */
    public function applyDailyCosts(Company $company, int $simDay): array
    {
        $financeState = $this->financeStateRepository->findOneBy(['company' => $company]);
        
        if (!$financeState) {
            // Si FinanceState n'existe pas, on retourne des coûts à 0
            return [
                'success' => true,
                'costs' => [
                    'rent' => 0.0,
                    'electricity' => 0.0,
                    'salaries' => 0.0,
                    'taxes' => 0.0,
                ],
                'total' => 0.0,
                'cashAfter' => '0.00',
            ];
        }

        $costs = [];
        $totalCosts = 0.0;

        // 1. Loyer (mensuel / 30)
        $dailyRent = (float)$financeState->getDailyRent();
        if ($dailyRent > 0) {
            $this->createLedgerEntry(
                $company,
                $simDay,
                LedgerEntry::TYPE_EXPENSE,
                LedgerEntry::CATEGORY_RENT,
                (string)$dailyRent,
                'Loyer journalier'
            );
            $costs['rent'] = $dailyRent;
            $totalCosts += $dailyRent;
        }

        // 2. Électricité
        $electricityCost = (float)$financeState->getDailyElectricityCost();
        if ($electricityCost > 0) {
            $this->createLedgerEntry(
                $company,
                $simDay,
                LedgerEntry::TYPE_EXPENSE,
                LedgerEntry::CATEGORY_ELECTRICITY,
                (string)$electricityCost,
                'Coût électricité journalier'
            );
            $costs['electricity'] = $electricityCost;
            $totalCosts += $electricityCost;
        }

        // 3. Salaires (somme des salaires journaliers de tous les employés actifs)
        $employees = $this->employeeRepository->findActiveByCompany($company->getId());
        $totalSalaries = 0.0;
        
        foreach ($employees as $employee) {
            // Ne pas compter les employés en formation (ils ne travaillent pas)
            if ($employee->isInTraining()) {
                continue;
            }
            $totalSalaries += (float)$employee->getSalaryDaily();
        }
        
        if ($totalSalaries > 0) {
            $this->createLedgerEntry(
                $company,
                $simDay,
                LedgerEntry::TYPE_EXPENSE,
                LedgerEntry::CATEGORY_SALARY,
                (string)$totalSalaries,
                'Salaires journaliers (' . count($employees) . ' employé(s))'
            );
            $costs['salaries'] = $totalSalaries;
            $totalCosts += $totalSalaries;
        } else {
            $costs['salaries'] = 0.0;
        }

        // 4. Taxes (simplifié : taxe fixe journalière basée sur le taux)
        // Pour l'instant, on utilise une taxe fixe de 0
        // Plus tard, on pourra calculer sur les revenus encaissés
        $costs['taxes'] = 0.0;

        // Débiter la trésorerie
        if ($totalCosts > 0) {
            $financeState->subtractCash((string)$totalCosts);
            $this->entityManager->flush();
        }

        return [
            'success' => true,
            'costs' => $costs,
            'total' => $totalCosts,
            'cashAfter' => $financeState->getCashAvailable(),
        ];
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

    /**
     * Enregistre un revenu (paiement client, etc.)
     */
    public function recordIncome(Company $company, int $simDay, string $amount, string $category, string $label): void
    {
        $financeState = $this->financeStateRepository->findOneBy(['company' => $company]);
        
        if (!$financeState) {
            return;
        }

        // Créer l'entrée de journal
        $this->createLedgerEntry(
            $company,
            $simDay,
            LedgerEntry::TYPE_INCOME,
            $category,
            $amount,
            $label
        );

        // Ajouter à la trésorerie
        $financeState->addCash($amount);
        $this->entityManager->flush();
    }
}
