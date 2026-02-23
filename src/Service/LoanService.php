<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\FinanceState;
use App\Entity\LedgerEntry;
use App\Entity\Loan;
use App\Repository\FinanceStateRepository;
use App\Repository\LoanRepository;
use Doctrine\ORM\EntityManagerInterface;

class LoanService
{
    public function __construct(
        private readonly LoanRepository $loanRepository,
        private readonly FinanceStateRepository $financeStateRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Crée un nouveau crédit pour une entreprise
     * Vérifie le plafond : max_loan_principal = 10 * share_capital
     */
    public function createLoan(Company $company, string $principal, int $durationMonths, int $simDay): Loan
    {
        $financeState = $this->financeStateRepository->findOneBy(['company' => $company]);
        if (!$financeState) {
            throw new \RuntimeException('FinanceState introuvable');
        }

        // Vérifier le plafond
        $maxLoan = (float)$financeState->getShareCapital() * 10;
        $principalFloat = (float)$principal;

        if ($principalFloat > $maxLoan) {
            throw new \RuntimeException(sprintf(
                'Le montant demandé (%s €) dépasse le plafond autorisé (%s € = 10 × capital social)',
                number_format($principalFloat, 2, ',', ' '),
                number_format($maxLoan, 2, ',', ' ')
            ));
        }

        // Vérifier les crédits existants
        $existingLoans = $this->loanRepository->findActiveByCompany($company->getId());
        $totalExistingPrincipal = 0.0;
        foreach ($existingLoans as $loan) {
            $totalExistingPrincipal += (float)$loan->getRemainingPrincipal();
        }

        if ($totalExistingPrincipal + $principalFloat > $maxLoan) {
            throw new \RuntimeException(sprintf(
                'Le montant demandé dépasse le plafond disponible. Crédits existants : %s €, plafond : %s €',
                number_format($totalExistingPrincipal, 2, ',', ' '),
                number_format($maxLoan, 2, ',', ' ')
            ));
        }

        // Calculer la mensualité (amortissement standard)
        $annualRate = 0.04; // 4%
        $monthlyRate = $annualRate / 12;
        $monthlyPayment = $principalFloat * ($monthlyRate * pow(1 + $monthlyRate, $durationMonths)) / (pow(1 + $monthlyRate, $durationMonths) - 1);

        // Créer le crédit
        $loan = new Loan();
        $loan->setCompany($company);
        $loan->setPrincipal($principal);
        $loan->setAnnualRate('0.0400');
        $loan->setDurationMonths($durationMonths);
        $loan->setStartSimDay($simDay);
        $loan->setRemainingPrincipal($principal);
        $loan->setMonthlyPayment((string)round($monthlyPayment, 2));
        $loan->setStatus(Loan::STATUS_ACTIVE);
        $loan->setLastPaymentSimDay(null);

        // Ajouter l'argent à la trésorerie
        $financeState->addCash($principal);

        // Créer l'entrée de journal
        $this->createLedgerEntry(
            $company,
            $simDay,
            LedgerEntry::TYPE_INCOME,
            LedgerEntry::CATEGORY_LOAN_RECEIVED,
            $principal,
            'Réception crédit de ' . number_format($principalFloat, 2, ',', ' ') . ' €'
        );

        $this->entityManager->persist($loan);
        $this->entityManager->flush();

        return $loan;
    }

    /**
     * Traite les mensualités des crédits actifs
     * Appelé lors du tick si sim_day % 30 == 0
     */
    public function processMonthlyPayments(Company $company, int $simDay): array
    {
        // Vérifier si c'est un jour de paiement (tous les 30 jours)
        if ($simDay % 30 !== 0) {
            return [];
        }

        $loans = $this->loanRepository->findActiveByCompany($company->getId());
        $financeState = $this->financeStateRepository->findOneBy(['company' => $company]);
        
        if (!$financeState) {
            return [];
        }

        $processed = [];

        foreach ($loans as $loan) {
            // Éviter de payer deux fois le même mois
            if ($loan->getLastPaymentSimDay() !== null && 
                ($simDay - $loan->getLastPaymentSimDay()) < 30) {
                continue;
            }

            $monthlyPayment = (float)$loan->getMonthlyPayment();
            $remainingPrincipal = (float)$loan->getRemainingPrincipal();
            $annualRate = (float)$loan->getAnnualRate();

            // Calculer les intérêts (intérêts = remaining_principal * annual_rate / 12)
            $interest = $remainingPrincipal * $annualRate / 12;
            $principalPayment = $monthlyPayment - $interest;

            // Vérifier si on a assez de trésorerie
            $cashAvailable = (float)$financeState->getCashAvailable();
            if ($cashAvailable < $monthlyPayment) {
                // Pas assez de trésorerie, on pourrait gérer ça différemment
                // Pour l'instant, on continue quand même
            }

            // Débiter la trésorerie
            $financeState->subtractCash((string)$monthlyPayment);

            // Mettre à jour le principal restant
            $newRemainingPrincipal = max(0, $remainingPrincipal - $principalPayment);
            $loan->setRemainingPrincipal((string)round($newRemainingPrincipal, 2));
            $loan->setLastPaymentSimDay($simDay);

            // Créer les entrées de journal
            $this->createLedgerEntry(
                $company,
                $simDay,
                LedgerEntry::TYPE_EXPENSE,
                LedgerEntry::CATEGORY_LOAN_PAYMENT,
                (string)round($interest, 2),
                'Intérêts crédit #' . $loan->getId()
            );

            $this->createLedgerEntry(
                $company,
                $simDay,
                LedgerEntry::TYPE_EXPENSE,
                LedgerEntry::CATEGORY_LOAN_PAYMENT,
                (string)round($principalPayment, 2),
                'Remboursement principal crédit #' . $loan->getId()
            );

            // Vérifier si le crédit est remboursé
            if ($newRemainingPrincipal <= 0.01) {
                $loan->setStatus(Loan::STATUS_PAID);
                $loan->setRemainingPrincipal('0.00');
            }

            $processed[] = $loan;
        }

        $this->entityManager->flush();
        return $processed;
    }

    /**
     * Calcule le plafond de crédit disponible
     */
    public function getAvailableCreditLimit(Company $company): array
    {
        $financeState = $this->financeStateRepository->findOneBy(['company' => $company]);
        if (!$financeState) {
            return [
                'maxLoan' => '0.00',
                'usedLoan' => '0.00',
                'availableLoan' => '0.00',
            ];
        }

        $maxLoan = (float)$financeState->getShareCapital() * 10;
        $existingLoans = $this->loanRepository->findActiveByCompany($company->getId());
        
        $usedLoan = 0.0;
        foreach ($existingLoans as $loan) {
            $usedLoan += (float)$loan->getRemainingPrincipal();
        }

        $availableLoan = max(0, $maxLoan - $usedLoan);

        return [
            'maxLoan' => (string)round($maxLoan, 2),
            'usedLoan' => (string)round($usedLoan, 2),
            'availableLoan' => (string)round($availableLoan, 2),
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
}
