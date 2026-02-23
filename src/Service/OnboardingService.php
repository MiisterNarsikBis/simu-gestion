<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\FinanceState;
use App\Entity\GameState;
use Doctrine\ORM\EntityManagerInterface;

class OnboardingService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Crée une nouvelle entreprise avec ses états initiaux
     */
    public function createCompany(string $name, int $ownerUserId, array $options = []): Company
    {
        // Valeurs par défaut
        $initialCash = $options['initialCash'] ?? '10000.00';
        $shareCapital = $options['shareCapital'] ?? '10000.00';
        $monthlyRent = $options['monthlyRent'] ?? '500.00';
        $dailyElectricityCost = $options['dailyElectricityCost'] ?? '10.00';
        $taxRate = $options['taxRate'] ?? '0.2000'; // 20%
        $initialDays = $options['initialDays'] ?? 30;

        // 1. Créer la Company
        $company = new Company();
        $company->setName($name);
        $company->setOwnerUserId($ownerUserId);
        $this->entityManager->persist($company);
        $this->entityManager->flush(); // Flush pour avoir l'ID

        // 2. Créer le GameState
        $gameState = new GameState();
        $gameState->setCompany($company);
        $gameState->setDaysAvailable($initialDays);
        $gameState->setAdditionalDays(0);
        $gameState->setSimDay(0);
        $gameState->setGlobalQualityRating(50); // Démarrage à 50
        $gameState->setGlobalSatisfaction(50); // Démarrage à 50
        $this->entityManager->persist($gameState);

        // 3. Créer le FinanceState
        $financeState = new FinanceState();
        $financeState->setCompany($company);
        $financeState->setCashAvailable($initialCash);
        $financeState->setShareCapital($shareCapital);
        $financeState->setMonthlyRent($monthlyRent);
        $financeState->setDailyElectricityCost($dailyElectricityCost);
        $financeState->setTaxRate($taxRate);
        $this->entityManager->persist($financeState);

        $this->entityManager->flush();

        return $company;
    }
}
