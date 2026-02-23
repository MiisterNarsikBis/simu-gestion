<?php

namespace App\Controller;

use App\Repository\CompanyRepository;
use App\Service\OnboardingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OnboardingController extends AbstractController
{
    #[Route('/onboarding', name: 'app_onboarding')]
    public function index(CompanyRepository $companyRepository): Response
    {
        // Si une entreprise existe déjà, rediriger vers le dashboard
        $existingCompany = $companyRepository->findOneBy([]);
        if ($existingCompany) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('onboarding/index.html.twig');
    }

    #[Route('/onboarding/create', name: 'app_onboarding_create', methods: ['POST'])]
    public function create(Request $request, OnboardingService $onboardingService): Response
    {
        $name = $request->request->get('name', '');
        $ownerUserId = $request->request->getInt('ownerUserId', 1); // Pour l'instant, on utilise 1 par défaut

        if (empty($name)) {
            $this->addFlash('error', 'Le nom de l\'entreprise est requis.');
            return $this->redirectToRoute('app_onboarding');
        }

        try {
            $company = $onboardingService->createCompany($name, $ownerUserId);
            $this->addFlash('success', 'Entreprise créée avec succès !');
            return $this->redirectToRoute('app_dashboard');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la création de l\'entreprise : ' . $e->getMessage());
            return $this->redirectToRoute('app_onboarding');
        }
    }
}
