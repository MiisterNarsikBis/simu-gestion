<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\GameState;
use App\Entity\LedgerEntry;
use App\Repository\CompanyRepository;
use App\Repository\GameStateRepository;
use App\Service\DailyCostService;
use App\Service\LoanService;
use App\Service\ProjectProgressService;
use App\Service\TrainingService;
use Doctrine\ORM\EntityManagerInterface;

class TickEngine
{
    public function __construct(
        private readonly GameStateRepository $gameStateRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MidnightRechargeService $midnightRechargeService,
        private readonly DailyCostService $dailyCostService,
        private readonly TrainingService $trainingService,
        private readonly ProjectProgressService $projectProgressService,
        private readonly LoanService $loanService
    ) {
    }

    /**
     * Passe un jour pour une entreprise
     * Retourne true si le tick a réussi, false sinon
     */
    public function tick(int $companyId): array
    {
        // Récupérer la Company
        $company = $this->companyRepository->find($companyId);
        if (!$company) {
            return [
                'success' => false,
                'error' => 'Entreprise introuvable'
            ];
        }

        // Récupérer le GameState
        $gameState = $this->gameStateRepository->findOneBy(['company' => $company]);
        
        if (!$gameState) {
            return [
                'success' => false,
                'error' => 'GameState introuvable pour cette entreprise'
            ];
        }

        // Vérifier et appliquer la recharge si nécessaire
        $this->midnightRechargeService->checkAndRechargeIfNeeded($gameState);

        // Consommer un jour
        if (!$gameState->consumeDay()) {
            return [
                'success' => false,
                'error' => 'Plus de jours disponibles'
            ];
        }

        // Incrémenter le jour simulé
        $gameState->incrementSimDay();
        $newSimDay = $gameState->getSimDay();

        // Mettre à jour les jours consommés aujourd'hui
        $daysConsumed = $gameState->getDaysConsumedToday() ?? 0;
        $gameState->setDaysConsumedToday($daysConsumed + 1);

        // Appliquer les coûts journaliers
        $costsResult = $this->dailyCostService->applyDailyCosts($company, $newSimDay);
        if (isset($costsResult['success']) && !$costsResult['success']) {
            return [
                'success' => false,
                'error' => $costsResult['error'] ?? 'Erreur lors de l\'application des coûts'
            ];
        }

        // Progression des formations
        $completedTrainings = [];
        try {
            $completedTrainings = $this->trainingService->progressTrainings($company, $newSimDay);
        } catch (\Exception $e) {
            // Log l'erreur mais continue
        }

        // Progression des projets
        $deliveredProjects = [];
        try {
            $deliveredProjects = $this->projectProgressService->progressProjects($company, $newSimDay);
        } catch (\Exception $e) {
            // Log l'erreur mais continue
        }
        
        // Traiter les livraisons et revenus
        $totalIncome = 0.0;
        foreach ($deliveredProjects as $project) {
            try {
                $income = (float)$project->getBudget();
                $totalIncome += $income;
                $this->dailyCostService->recordIncome(
                    $company,
                    $newSimDay,
                    (string)$income,
                    LedgerEntry::CATEGORY_CLIENT_PAYMENT,
                    'Livraison projet : ' . $project->getClient()->getName()
                );
            } catch (\Exception $e) {
                // Log l'erreur mais continue
            }
        }

        // Traiter les mensualités de crédit (tous les 30 jours)
        $loanPayments = [];
        try {
            $loanPayments = $this->loanService->processMonthlyPayments($company, $newSimDay);
        } catch (\Exception $e) {
            // Log l'erreur mais continue
        }

        $this->entityManager->flush();

        return [
            'success' => true,
            'simDay' => $gameState->getSimDay(),
            'daysAvailable' => $gameState->getDaysAvailable(),
            'additionalDays' => $gameState->getAdditionalDays(),
            'costs' => $costsResult['costs'] ?? [],
            'totalCosts' => $costsResult['total'] ?? 0,
            'cashAfter' => $costsResult['cashAfter'] ?? '0.00',
            'trainingsCompleted' => count($completedTrainings),
            'projectsDelivered' => count($deliveredProjects),
            'totalIncome' => $totalIncome,
            'loanPayments' => count($loanPayments),
        ];
    }
}
