<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/cart')]
class CartController extends AbstractController
{
    private $cartRepository;
    private $productRepository;
    private $userRepository;
    private $entityManager;
    private $logger;

    public function __construct(
        CartRepository $cartRepository,
        ProductRepository $productRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    )
    {
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[OA\Post(
        summary: "Add an item to the cart",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "user_id", type: "integer", example: 1),
                    new OA\Property(property: "product_id", type: "integer", example: 1),
                    new OA\Property(property: "quantity", type: "integer", example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Item added to cart successfully"),
            new OA\Response(response: 400, description: "Invalid input data")
        ]
    )]
    #[Route('/add', name: 'add_to_cart', methods: ['POST'])]
    public function addToCart(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->userRepository->find($data['user_id']);
        $product = $this->productRepository->find($data['product_id']);

        if (!$user || !$product) {
            $this->logger->error('Invalid user or product ID');
            return $this->json(['error' => 'Invalid user or product ID'], Response::HTTP_BAD_REQUEST);
        }

        $cart = $this->cartRepository->findOneBy(['user' => $user]);
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->entityManager->persist($cart);
        }

        $cartItem = new CartItem();
        $cartItem->setCart($cart);
        $cartItem->setProduct($product);
        $cartItem->setQuantity($data['quantity']);

        $this->entityManager->persist($cartItem);
        $this->entityManager->flush();

        return $this->json(['message' => 'Item added to cart successfully'], Response::HTTP_CREATED);
    }

    #[OA\Get(
        summary: "Get the cart for a user",
        parameters: [
            new OA\Parameter(name: "userId", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful response"),
            new OA\Response(response: 404, description: "Cart not found")
        ]
    )]
    #[Route('/{userId}', name: 'get_cart', methods: ['GET'])]
    public function getCart(int $userId): Response
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            $this->logger->error('User not found');
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $cart = $this->cartRepository->findOneBy(['user' => $user]);
        if (!$cart) {
            $this->logger->error('Cart not found');
            return $this->json(['error' => 'Cart not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($cart, 200, [], ['groups' => ['cart']]);
    }

    #[OA\Post(
        summary: "Checkout the cart",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "user_id", type: "integer", example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Checkout successful"),
            new OA\Response(response: 400, description: "Invalid input data"),
            new OA\Response(response: 404, description: "Cart not found")
        ]
    )]
    #[Route('/checkout', name: 'checkout', methods: ['POST'])]
    public function checkout(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->userRepository->find($data['user_id']);

        if (!$user) {
            $this->logger->error('Invalid user ID');
            return $this->json(['error' => 'Invalid user ID'], Response::HTTP_BAD_REQUEST);
        }

        $cart = $this->cartRepository->findOneBy(['user' => $user]);
        if (!$cart) {
            $this->logger->error('Cart not found');
            return $this->json(['error' => 'Cart not found'], Response::HTTP_NOT_FOUND);
        }

        foreach ($cart->getItems() as $cartItem) {
            $purchase = new Purchase();
            $purchase->setUser($user);
            $purchase->setProduct($cartItem->getProduct());
            $purchase->setQuantity($cartItem->getQuantity());
            $purchase->setPurchaseDate(new \DateTime());

            $this->entityManager->persist($purchase);
            $this->entityManager->remove($cartItem);
        }

        $this->entityManager->remove($cart);
        $this->entityManager->flush();

        return $this->json(['message' => 'Checkout successful'], Response::HTTP_OK);
    }
}