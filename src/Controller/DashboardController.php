<?php

namespace App\Controller;

use App\Repository\FinanceStateRepository;
use App\Repository\GameStateRepository;
use App\Repository\LedgerEntryRepository;
use App\Repository\ProjectRepository;
use App\Service\CompanyProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        GameStateRepository $gameStateRepository,
        FinanceStateRepository $financeStateRepository,
        LedgerEntryRepository $ledgerEntryRepository,
        CompanyProvider $companyProvider,
        ProjectRepository $projectRepository
    ): Response {
        // Vérifier si une entreprise existe en session
        $company = $companyProvider->getCompany();
        if (!$company) {
            return $this->redirectToRoute('app_onboarding');
        }

        $gameState = $gameStateRepository->findOneBy(['company' => $company]);
        $financeState = $financeStateRepository->findOneBy(['company' => $company]);
        
        // Récupérer les projets en cours
        $projects = $projectRepository->findInProgressByCompany($company->getId());
        
        // Récupérer les dernières entrées du journal (10 dernières)
        $ledgerEntries = [];
        if ($financeState && $financeState->getCompany()) {
            $ledgerEntries = $ledgerEntryRepository->findByCompany(
                $financeState->getCompany()->getId(),
                10
            );
        }

        return $this->render('dashboard/index.html.twig', [
            'title' => 'Dashboard',
            'company' => $company,
            'gameState' => $gameState,
            'financeState' => $financeState,
            'ledgerEntries' => $ledgerEntries,
            'projects' => $projects,
        ]);
    }
}
