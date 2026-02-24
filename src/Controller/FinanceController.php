<?php

namespace App\Controller;

use App\Repository\FinanceStateRepository;
use App\Repository\GameStateRepository;
use App\Repository\LoanRepository;
use App\Service\CapitalService;
use App\Service\CompanyProvider;
use App\Service\LoanService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FinanceController extends AbstractController
{
    #[Route('/finance', name: 'app_finance')]
    public function index(
        CompanyProvider $companyProvider,
        FinanceStateRepository $financeStateRepository,
        LoanRepository $loanRepository,
        LoanService $loanService,
        GameStateRepository $gameStateRepository
    ): Response {
        $company = $companyProvider->getCompany();
        if (!$company) {
            return $this->redirectToRoute('app_onboarding');
        }

        $gameState = $gameStateRepository->findOneBy(['company' => $company]);
        $financeState = $financeStateRepository->findOneBy(['company' => $company]);
        $loans = $loanRepository->findActiveByCompany($company->getId());
        $creditLimit = $loanService->getAvailableCreditLimit($company);

        return $this->render('finance/index.html.twig', [
            'company' => $company,
            'gameState' => $gameState,
            'financeState' => $financeState,
            'loans' => $loans,
            'creditLimit' => $creditLimit,
        ]);
    }

    #[Route('/finance/capital/increase', name: 'app_finance_capital_increase', methods: ['POST'])]
    public function increaseCapital(
        Request $request,
        CompanyProvider $companyProvider,
        GameStateRepository $gameStateRepository,
        CapitalService $capitalService
    ): Response {
        $company = $companyProvider->getCompany();
        if (!$company) {
            return $this->redirectToRoute('app_onboarding');
        }

        $gameState = $gameStateRepository->findOneBy(['company' => $company]);
        $amount = $request->request->get('amount', '');

        if (empty($amount) || !is_numeric($amount) || (float)$amount <= 0) {
            $this->addFlash('error', 'Montant invalide');
            return $this->redirectToRoute('app_finance');
        }

        try {
            $capitalService->increaseCapital($company, (string)$amount, $gameState->getSimDay());
            $this->addFlash('success', 'Capital social augmenté de ' . number_format((float)$amount, 2, ',', ' ') . ' €');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_finance');
    }

    #[Route('/finance/loan/create', name: 'app_finance_loan_create', methods: ['POST'])]
    public function createLoan(
        Request $request,
        CompanyProvider $companyProvider,
        GameStateRepository $gameStateRepository,
        LoanService $loanService
    ): Response {
        $company = $companyProvider->getCompany();
        if (!$company) {
            return $this->redirectToRoute('app_onboarding');
        }

        $gameState = $gameStateRepository->findOneBy(['company' => $company]);
        $principal = $request->request->get('principal', '');
        $durationMonths = $request->request->getInt('duration_months', 0);

        if (empty($principal) || !is_numeric($principal) || (float)$principal <= 0) {
            $this->addFlash('error', 'Montant invalide');
            return $this->redirectToRoute('app_finance');
        }

        if ($durationMonths <= 0 || $durationMonths > 120) {
            $this->addFlash('error', 'Durée invalide (entre 1 et 120 mois)');
            return $this->redirectToRoute('app_finance');
        }

        try {
            $loan = $loanService->createLoan($company, (string)$principal, $durationMonths, $gameState->getSimDay());
            $this->addFlash('success', 'Crédit de ' . number_format((float)$principal, 2, ',', ' ') . ' € créé avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_finance');
    }
}
