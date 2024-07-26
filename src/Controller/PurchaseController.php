<?php

namespace App\Controller;

use App\Entity\Purchase;
use App\Repository\PurchaseRepository;
use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/purchases')]
class PurchaseController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[OA\Post(
        summary: "Create a new purchase",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "user_id", type: "integer", example: 1),
                    new OA\Property(property: "product_id", type: "integer", example: 1),
                    new OA\Property(property: "quantity", type: "integer", example: 2),
                    new OA\Property(property: "purchase_date", type: "string", format: "date-time", example: "2024-07-25T10:00:00Z")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Purchase created successfully"),
            new OA\Response(response: 400, description: "Invalid input data")
        ]
    )]
    #[Route('', name: 'create_purchase', methods: ['POST'])]
    public function createPurchase(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ProductRepository $productRepository,
        ValidatorInterface $validator
    ): Response {
        $data = json_decode($request->getContent(), true);

        $purchase = new Purchase();
        
        $user = $userRepository->find($data['user_id']);
        if (!$user) {
            $this->logger->error('Invalid user ID');
            return $this->json(['error' => 'Invalid user ID'], Response::HTTP_BAD_REQUEST);
        }
        $purchase->setUser($user);

        $product = $productRepository->find($data['product_id']);
        if (!$product) {
            $this->logger->error('Invalid product ID');
            return $this->json(['error' => 'Invalid product ID'], Response::HTTP_BAD_REQUEST);
        }
        $purchase->setProduct($product);

        $purchase->setQuantity($data['quantity']);
        $purchase->setPurchaseDate(new \DateTime($data['purchase_date']));

        $errors = $validator->validate($purchase);
        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            $this->logger->error('Purchase attempt failed', ['errors' => $errorsString]);
            return $this->json(['errors' => $errorsString], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($purchase);
        $entityManager->flush();

        return $this->json(['message' => 'Purchase created successfully'], Response::HTTP_CREATED);
    }

    #[OA\Get(
        summary: "List all purchases",
        responses: [
            new OA\Response(response: 200, description: "Successful response")
        ]
    )]
    #[Route('', name: 'list_purchases', methods: ['GET'])]
    public function listPurchases(PurchaseRepository $purchaseRepository): Response
    {
        $purchases = $purchaseRepository->findAll();
        return $this->json($purchases, 200, [], ['groups' => ['purchase']]);
    }

    #[OA\Get(
        summary: "Get a purchase by ID",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful response"),
            new OA\Response(response: 404, description: "Purchase not found")
        ]
    )]
    #[Route('/{id}', name: 'get_purchase', methods: ['GET'])]
    public function getPurchase(PurchaseRepository $purchaseRepository, int $id): Response
    {
        $purchase = $purchaseRepository->find($id);

        if (!$purchase) {
            $this->logger->error('Purchase not found');
            return $this->json(['error' => 'Purchase not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($purchase, 200, [], ['groups' => ['purchase']]);
    }
}