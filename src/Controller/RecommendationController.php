<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\RecommendationService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/recommendations')]
class RecommendationController extends AbstractController
{
    private $userRepository;
    private $recommendationService;

    public function __construct(UserRepository $userRepository, RecommendationService $recommendationService)
    {
        $this->userRepository = $userRepository;
        $this->recommendationService = $recommendationService;
    }

    #[OA\Get(
        summary: "Get product recommendations for a user",
        parameters: [
            new OA\Parameter(name: "userId", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful response"),
            new OA\Response(response: 404, description: "User not found")
        ]
    )]
    #[Route('/{userId}', name: 'get_recommendations', methods: ['GET'])]
    public function getRecommendations(int $userId): Response
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            $this->logger->error('User not found');
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $recommendedProducts = $this->recommendationService->getRecommendationsForUser($user);

        return $this->json($recommendedProducts, 200, [], ['groups' => ['recommendation']]);
    }
}