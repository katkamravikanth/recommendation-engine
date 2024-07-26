<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Service\RecommendationService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    private $recommendationService;
    private $logger;

    public function __construct(RecommendationService $recommendationService, LoggerInterface $logger)
    {
        $this->recommendationService = $recommendationService;
        $this->logger = $logger;
    }

    #[OA\Post(
        summary: "Create a new product",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Product name"),
                    new OA\Property(property: "description", type: "string", example: "Product description"),
                    new OA\Property(property: "price", type: "number", format: "float", example: 19.99),
                    new OA\Property(property: "brand", type: "string", example: "Brand name"),
                    new OA\Property(property: "size", type: "string", example: "M"),
                    new OA\Property(property: "color", type: "string", example: "Red"),
                    new OA\Property(property: "category_id", type: "integer", example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Product created successfully"),
            new OA\Response(response: 400, description: "Invalid input data")
        ]
    )]
    #[Route('', name: 'create_product', methods: ['POST'])]
    public function createProduct(
        Request $request,
        EntityManagerInterface $entityManager,
        CategoryRepository $categoryRepository,
        ValidatorInterface $validator
    ): Response {
        $data = json_decode($request->getContent(), true);

        $product = new Product();
        $product->setName($data['name']);
        $product->setDescription($data['description'] ?? null);
        $product->setPrice($data['price']);
        $product->setBrand($data['brand']);
        $product->setSize($data['size'] ?? null);
        $product->setColor($data['color'] ?? null);

        $category = $categoryRepository->find($data['category_id']);
        if (!$category) {
            $this->logger->error('Invalid category ID ' . $data['category_id']);
            return $this->json(['error' => 'Invalid category ID'], Response::HTTP_BAD_REQUEST);
        }
        $product->setCategory($category);

        $errors = $validator->validate($product);
        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            $this->logger->error('Creating product failed', ['errors' => $errorsString]);
            return $this->json(['errors' => $errorsString], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($product);
        $entityManager->flush();

        return $this->json(['message' => 'Product created successfully'], Response::HTTP_CREATED);
    }

    #[OA\Get(
        summary: "List all products",
        responses: [
            new OA\Response(response: 200, description: "Successful response")
        ]
    )]
    #[Route('', name: 'list_products', methods: ['GET'])]
    public function listProducts(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

        return $this->json($products, 200, [], ['groups' => ['product']]);
    }

    #[OA\Get(
        summary: "Get a product by ID",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful response"),
            new OA\Response(response: 404, description: "Product not found")
        ]
    )]
    #[Route('/{id}', name: 'get_product', methods: ['GET'])]
    public function getProduct(ProductRepository $productRepository, int $id): Response
    {
        $product = $productRepository->find($id);

        if (!$product) {
            $this->logger->error('Product not found');
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $recommendations = $this->recommendationService->getRecommendationsForProduct($product);

        return $this->json(['product' => $product, 'recommendations' => $recommendations], 200, [], ['groups' => ['product']]);
    }

    #[OA\Put(
        summary: "Update a product by ID",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Product name"),
                    new OA\Property(property: "description", type: "string", example: "Product description"),
                    new OA\Property(property: "price", type: "number", format: "float", example: 19.99),
                    new OA\Property(property: "brand", type: "string", example: "Brand name"),
                    new OA\Property(property: "size", type: "string", example: "M"),
                    new OA\Property(property: "color", type: "string", example: "Red"),
                    new OA\Property(property: "category_id", type: "integer", example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Product updated successfully"),
            new OA\Response(response: 400, description: "Invalid input data"),
            new OA\Response(response: 404, description: "Product not found")
        ]
    )]
    #[Route('/{id}', name: 'update_product', methods: ['PUT'])]
    public function updateProduct(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        int $id
    ): Response {
        $product = $productRepository->find($id);

        if (!$product) {
            $this->logger->error('Product not found');
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        $product->setName($data['name'] ?? $product->getName());
        $product->setDescription($data['description'] ?? $product->getDescription());
        $product->setPrice($data['price'] ?? $product->getPrice());
        $product->setBrand($data['brand'] ?? $product->getBrand());
        $product->setSize($data['size'] ?? $product->getSize());
        $product->setColor($data['color'] ?? $product->getColor());

        if (isset($data['category_id'])) {
            $category = $categoryRepository->find($data['category_id']);
            if (!$category) {
                $this->logger->error('Invalid category ID ' . $data['category_id']);
                return $this->json(['error' => 'Invalid category ID'], Response::HTTP_BAD_REQUEST);
            }
            $product->setCategory($category);
        }

        $errors = $validator->validate($product);
        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            $this->logger->error('Updating product failed', ['errors' => $errorsString]);
            return $this->json(['errors' => $errorsString], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();

        return $this->json(['message' => 'Product updated successfully'], Response::HTTP_OK);
    }

    #[OA\Delete(
        summary: "Delete a product by ID",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: "Product deleted successfully"),
            new OA\Response(response: 404, description: "Product not found")
        ]
    )]
    #[Route('/{id}', name: 'delete_product', methods: ['DELETE'])]
    public function deleteProduct(
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        int $id
    ): Response {
        $product = $productRepository->find($id);

        if (!$product) {
            $this->logger->error('Product not found');
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($product);
        $entityManager->flush();

        return $this->json(['message' => 'Product deleted successfully'], Response::HTTP_OK);
    }
}