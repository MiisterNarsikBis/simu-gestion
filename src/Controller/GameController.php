<?php

namespace App\Controller;

use App\Entity\GameState;
use App\Repository\GameStateRepository;
use App\Service\TickEngine;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GameController extends AbstractController
{
    #[Route('/game/tick', name: 'app_game_tick', methods: ['POST'])]
    public function tick(Request $request, TickEngine $tickEngine): JsonResponse
    {
        try {
            // Pour l'instant, on utilise l'ID 1 par défaut
            // Plus tard, on récupérera l'entreprise de l'utilisateur connecté
            $companyId = $request->request->getInt('companyId', 1);

            $result = $tickEngine->tick($companyId);

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
    public function getState(GameStateRepository $gameStateRepository): JsonResponse
    {
        // Pour l'instant, on récupère le premier GameState
        // Plus tard, on récupérera celui de l'utilisateur connecté
        $gameState = $gameStateRepository->findOneBy([]);

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
