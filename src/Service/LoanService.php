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
        $maxLoan = bcmul($financeState->getShareCapital(), '10', 2);

        if (bccomp($principal, $maxLoan, 2) > 0) {
            throw new \RuntimeException(sprintf(
                'Le montant demandé (%s €) dépasse le plafond autorisé (%s € = 10 × capital social)',
                number_format((float)$principal, 2, ',', ' '),
                number_format((float)$maxLoan, 2, ',', ' ')
            ));
        }

        // Vérifier les crédits existants
        $existingLoans = $this->loanRepository->findActiveByCompany($company->getId());
        $totalExistingPrincipal = '0.00';
        foreach ($existingLoans as $loan) {
            $totalExistingPrincipal = bcadd($totalExistingPrincipal, $loan->getRemainingPrincipal(), 2);
        }

        if (bccomp(bcadd($totalExistingPrincipal, $principal, 2), $maxLoan, 2) > 0) {
            throw new \RuntimeException(sprintf(
                'Le montant demandé dépasse le plafond disponible. Crédits existants : %s €, plafond : %s €',
                number_format((float)$totalExistingPrincipal, 2, ',', ' '),
                number_format((float)$maxLoan, 2, ',', ' ')
            ));
        }

        // Calculer la mensualité (amortissement standard — formule complexe, on reste en float)
        $annualRate = 0.04; // 4%
        $monthlyRate = $annualRate / 12;
        $principalFloat = (float)$principal;
        $monthlyPayment = $principalFloat * ($monthlyRate * pow(1 + $monthlyRate, $durationMonths)) / (pow(1 + $monthlyRate, $durationMonths) - 1);

        // Créer le crédit
        $loan = new Loan();
        $loan->setCompany($company);
        $loan->setPrincipal($principal);
        $loan->setAnnualRate('0.0400');
        $loan->setDurationMonths($durationMonths);
        $loan->setStartSimDay($simDay);
        $loan->setRemainingPrincipal($principal);
        $loan->setMonthlyPayment(number_format($monthlyPayment, 2, '.', ''));
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

            $monthlyPayment = $loan->getMonthlyPayment();
            $remainingPrincipal = $loan->getRemainingPrincipal();

            // Calculer les intérêts (intérêts = remaining_principal * annual_rate / 12)
            $interest = bcdiv(bcmul($remainingPrincipal, $loan->getAnnualRate(), 10), '12', 2);
            $principalPayment = bcsub($monthlyPayment, $interest, 2);

            // Vérifier si on a assez de trésorerie
            if (bccomp($financeState->getCashAvailable(), $monthlyPayment, 2) < 0) {
                // Pas assez de trésorerie, on continue quand même
            }

            // Débiter la trésorerie
            $financeState->subtractCash($monthlyPayment);

            // Mettre à jour le principal restant
            $newRemainingPrincipal = bcsub($remainingPrincipal, $principalPayment, 2);
            if (bccomp($newRemainingPrincipal, '0', 2) < 0) {
                $newRemainingPrincipal = '0.00';
            }
            $loan->setRemainingPrincipal($newRemainingPrincipal);
            $loan->setLastPaymentSimDay($simDay);

            // Créer les entrées de journal
            $this->createLedgerEntry(
                $company,
                $simDay,
                LedgerEntry::TYPE_EXPENSE,
                LedgerEntry::CATEGORY_LOAN_PAYMENT,
                $interest,
                'Intérêts crédit #' . $loan->getId()
            );

            $this->createLedgerEntry(
                $company,
                $simDay,
                LedgerEntry::TYPE_EXPENSE,
                LedgerEntry::CATEGORY_LOAN_PAYMENT,
                $principalPayment,
                'Remboursement principal crédit #' . $loan->getId()
            );

            // Vérifier si le crédit est remboursé
            if (bccomp($newRemainingPrincipal, '0.01', 2) <= 0) {
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

        $maxLoan = bcmul($financeState->getShareCapital(), '10', 2);
        $existingLoans = $this->loanRepository->findActiveByCompany($company->getId());

        $usedLoan = '0.00';
        foreach ($existingLoans as $loan) {
            $usedLoan = bcadd($usedLoan, $loan->getRemainingPrincipal(), 2);
        }

        $availableLoan = bcsub($maxLoan, $usedLoan, 2);
        if (bccomp($availableLoan, '0', 2) < 0) {
            $availableLoan = '0.00';
        }

        return [
            'maxLoan' => $maxLoan,
            'usedLoan' => $usedLoan,
            'availableLoan' => $availableLoan,
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
