<?php

namespace App\Controller;

use App\Repository\GameStateRepository;
use App\Service\CompanyProvider;
use App\Service\TickEngine;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GameController extends AbstractController
{
    #[Route('/game/tick', name: 'app_game_tick', methods: ['POST'])]
    public function tick(Request $request, TickEngine $tickEngine, CompanyProvider $companyProvider): JsonResponse
    {
        if (!$this->isCsrfTokenValid('game_tick', $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'error' => 'Token CSRF invalide'], Response::HTTP_FORBIDDEN);
        }

        try {
            $company = $companyProvider->getCompany();
            if (!$company) {
                return new JsonResponse(['success' => false, 'error' => 'Entreprise introuvable'], Response::HTTP_BAD_REQUEST);
            }

            $result = $tickEngine->tick($company->getId());

            if (!$result['success']) {
                return new JsonResponse($result, Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors du passage du jour : ' . $e->getMessage(),
                'trace' => $this->getParameter('kernel.debug') ? $e->getTraceAsString() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/game/state', name: 'app_game_state', methods: ['GET'])]
    public function getState(GameStateRepository $gameStateRepository, CompanyProvider $companyProvider): JsonResponse
    {
        $company = $companyProvider->getCompany();
        if (!$company) {
            return new JsonResponse(['error' => 'Entreprise introuvable'], Response::HTTP_NOT_FOUND);
        }

        $gameState = $gameStateRepository->findOneBy(['company' => $company]);

        if (!$gameState) {
            return new JsonResponse([
                'error' => 'Aucun état de jeu trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'simDay' => $gameState->getSimDay(),
            'daysAvailable' => $gameState->getDaysAvailable(),
            'additionalDays' => $gameState->getAdditionalDays(),
            'globalQualityRating' => $gameState->getGlobalQualityRating(),
            'globalSatisfaction' => $gameState->getGlobalSatisfaction(),
        ]);
    }
}
