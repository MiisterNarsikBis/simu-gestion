<?php

namespace App\Service;

use App\Entity\GameState;
use App\Repository\GameStateRepository;
use Doctrine\ORM\EntityManagerInterface;

class MidnightRechargeService
{
    public function __construct(
        private readonly GameStateRepository $gameStateRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Recharge les jours disponibles pour toutes les entreprises
     * Règle : +40 si tous les jours de la veille ont été utilisés, sinon +30
     */
    public function rechargeDays(): int
    {
        $gameStates = $this->gameStateRepository->findAll();
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $midnight = clone $now;
        $midnight->setTime(0, 0, 0);
        $processed = 0;

        foreach ($gameStates as $gameState) {
            $lastProcessed = $gameState->getLastMidnightProcessedAt();
            
            // Vérifier si minuit est passé depuis la dernière recharge
            if ($lastProcessed === null || $lastProcessed < $midnight) {
                $this->processRechargeForGameState($gameState, $midnight);
                $processed++;
            }
        }

        $this->entityManager->flush();
        return $processed;
    }

    /**
     * Reset les jours disponibles non utilisés chaque dimanche
     */
    public function resetSunday(): int
    {
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        
        // Vérifier si c'est dimanche (0 = dimanche)
        if ((int)$now->format('w') !== 0) {
            return 0;
        }

        $gameStates = $this->gameStateRepository->findAll();
        $reset = 0;

        foreach ($gameStates as $gameState) {
            // Si des jours disponibles ne sont toujours pas utilisés, reset à 0
            if ($gameState->getDaysAvailable() > 0) {
                $gameState->setDaysAvailable(0);
                $reset++;
            }
        }

        $this->entityManager->flush();
        return $reset;
    }

    /**
     * Traite la recharge pour un GameState spécifique
     */
    private function processRechargeForGameState(GameState $gameState, \DateTime $midnight): void
    {
        $lastRechargeDate = $gameState->getLastRechargeDate();
        $daysAvailable = $gameState->getDaysAvailable();
        
        // Déterminer si tous les jours de la veille ont été utilisés
        // Si lastRechargeDate est null ou si daysAvailable était à 0 hier
        $allDaysUsed = $lastRechargeDate === null || $daysAvailable === 0;
        
        // Appliquer la recharge
        if ($allDaysUsed) {
            $gameState->setDaysAvailable(40);
        } else {
            $gameState->setDaysAvailable(30);
        }
        
        // Mettre à jour les dates
        $gameState->setLastMidnightProcessedAt($midnight);
        $gameState->setLastRechargeDate($midnight);
        $gameState->setDaysConsumedToday(0);
    }

    /**
     * Vérifie et applique la recharge si nécessaire pour un GameState
     * Utilisé lors du tick pour vérifier avant de consommer un jour
     */
    public function checkAndRechargeIfNeeded(GameState $gameState): bool
    {
        $now = new \DateTime('now', new \DateTimeZone($gameState->getTimezone()));
        $midnight = clone $now;
        $midnight->setTime(0, 0, 0);
        $lastProcessed = $gameState->getLastMidnightProcessedAt();
        
        if ($lastProcessed === null || $lastProcessed < $midnight) {
            $this->processRechargeForGameState($gameState, $midnight);
            $this->entityManager->flush();
            return true;
        }
        
        return false;
    }
}
