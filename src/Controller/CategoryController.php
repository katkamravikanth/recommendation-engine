<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/categories')]
class CategoryController extends AbstractController
{
    private $entityManager;
    private $validator;
    private $categoryRepository;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        CategoryRepository $categoryRepository,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->categoryRepository = $categoryRepository;
        $this->logger = $logger;
    }

    #[OA\Post(
        summary: "Create a new Category",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "name", type: "string", example: "new category name"),
                    new OA\Property(property: "description", type: "string", example: "this is a category")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Category created successfully"),
            new OA\Response(response: 400, description: "Invalid input data")
        ]
    )]
    #[Route('/new', name: 'create_category', methods: ['POST'])]
    public function createCategory(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $category = new Category();
        $category->setName($data['name']);
        $category->setDescription($data['description']);

        $errors = $this->validator->validate($category);
        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            $this->logger->error('Creating category failed', ['errors' => $errorsString]);
            return $this->json(['errors' => $errorsString], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $this->json(['message' => 'Category created successfully'], Response::HTTP_CREATED);
    }

    #[OA\Get(
        summary: "List all categories",
        responses: [
            new OA\Response(response: 200, description: "Successful response")
        ]
    )]
    #[Route('', name: 'list_categories', methods: ['GET'])]
    public function listCategories(): Response
    {
        $categories = $this->categoryRepository->findAll();
        return $this->json($categories, Response::HTTP_OK, [], ['groups' => 'category']);
    }

    #[OA\Get(
        summary: "Get a category by ID",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful response"),
            new OA\Response(response: 404, description: "Category not found")
        ]
    )]
    #[Route('/{id}', name: 'get_category', methods: ['GET'])]
    public function getCategoryById(int $id): Response
    {
        $category = $this->categoryRepository->find($id);

        if (!$category) {
            $this->logger->error('Category not found');
            return $this->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($category, Response::HTTP_OK, [], ['groups' => 'category']);
    }

    #[OA\Put(
        summary: "Update a category by ID",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "name", type: "string", example: "update category name"),
                    new OA\Property(property: "description", type: "string", example: "this is a category")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Category updated successfully"),
            new OA\Response(response: 400, description: "Invalid input data"),
            new OA\Response(response: 404, description: "Category not found")
        ]
    )]
    #[Route('/{id}', name: 'update_category', methods: ['PUT'])]
    public function updateCategory(Request $request, int $id): Response {
        $category = $this->categoryRepository->find($id);

        if (!$category) {
            $this->logger->error('Category not found');
            return $this->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        $category->setName($data['name'] ?? $category->getName());
        $category->setDescription($data['description'] ?? $category->getDescription());

        $errors = $this->validator->validate($category);
        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            $this->logger->error('Updating category failed', ['errors' => $errorsString]);
            return $this->json(['errors' => $errorsString], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'Category updated successfully'], Response::HTTP_OK);
    }

    #[OA\Delete(
        summary: "Delete a category by ID",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: "Category deleted successfully"),
            new OA\Response(response: 404, description: "Category not found")
        ]
    )]
    #[Route('/{id}', name: 'delete_category', methods: ['DELETE'])]
    public function deleteCategory(int $id): Response
    {
        $category = $this->categoryRepository->find($id);

        if (!$category) {
            $this->logger->error('Category not found');
            return $this->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($category);
        $this->entityManager->flush();

        return $this->json(['message' => 'Category deleted successfully'], Response::HTTP_OK);
    }
}