<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\FinanceState;
use App\Entity\LedgerEntry;
use App\Repository\FinanceStateRepository;
use Doctrine\ORM\EntityManagerInterface;

class CapitalService
{
    public function __construct(
        private readonly FinanceStateRepository $financeStateRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Augmente le capital social en débitant la trésorerie
     */
    public function increaseCapital(Company $company, string $amount, int $simDay): void
    {
        $financeState = $this->financeStateRepository->findOneBy(['company' => $company]);
        if (!$financeState) {
            throw new \RuntimeException('FinanceState introuvable');
        }

        $amountFloat = (float)$amount;
        $cashAvailable = (float)$financeState->getCashAvailable();

        // Vérifier qu'on a assez de trésorerie
        if ($cashAvailable < $amountFloat) {
            throw new \RuntimeException(sprintf(
                'Trésorerie insuffisante. Disponible : %s €, demandé : %s €',
                number_format($cashAvailable, 2, ',', ' '),
                number_format($amountFloat, 2, ',', ' ')
            ));
        }

        // Débiter la trésorerie
        $financeState->subtractCash($amount);

        // Augmenter le capital social
        $currentCapital = (float)$financeState->getShareCapital();
        $newCapital = $currentCapital + $amountFloat;
        $financeState->setShareCapital((string)round($newCapital, 2));

        // Créer l'entrée de journal
        $this->createLedgerEntry(
            $company,
            $simDay,
            LedgerEntry::TYPE_EXPENSE,
            LedgerEntry::CATEGORY_CAPITAL_INCREASE,
            $amount,
            'Augmentation capital social de ' . number_format($amountFloat, 2, ',', ' ') . ' €'
        );

        $this->entityManager->flush();
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
