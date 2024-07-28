<?php

namespace App\Test\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProductControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $productRepository;
    private EntityRepository $categoryRepository;
    private string $path = '/api/products/';
    private string $jwtToken;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects(true);
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->productRepository = $this->manager->getRepository(Product::class);
        $this->categoryRepository = $this->manager->getRepository(Category::class);

        $this->createTestUserAndAuthenticate();
    }

    private function createTestUserAndAuthenticate(): void
    {
        $faker = Factory::create();

        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setName($faker->name);
        $user->setEmail($faker->email);
        $user->setPassword($hasher->hashPassword($user, 'password'));

        $this->manager->persist($user);
        $this->manager->flush();

        // Obtain JWT token
        $this->client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $user->getEmail(),
            'password' => 'password'
        ]));

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->jwtToken = $data['token'];
    }

    public function testIndex(): void
    {
        $this->client->request('GET', $this->path, [], [], ['HTTP_Authorization' => 'Bearer ' . $this->jwtToken]);

        self::assertResponseStatusCodeSame(200);
    }

    public function testShow(): void
    {
        $this->client->request('GET', sprintf('%s%s', $this->path, 1), [], [], ['HTTP_Authorization' => 'Bearer ' . $this->jwtToken]);

        self::assertResponseStatusCodeSame(200);
    }

    public function testNew(): void
    {
        $faker = Factory::create();
        $category = $this->categoryRepository->findOneBy(['name'=> 'Electronics']);

        $this->client->request('POST', $this->path . 'new', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $this->jwtToken
        ], json_encode([
            'name' => $faker->name,
            'description' => $faker->sentence,
            'price' => $faker->randomFloat(2, 10, 100),
            'brand' => $faker->company,
            'size' => $faker->randomElement(['S', 'M', 'L', 'XL']),
            'color' => $faker->safeColorName,
            'category_id' => $category->getId()
        ]));

        self::assertResponseStatusCodeSame(201);
    }

    public function testEdit(): void
    {
        $faker = Factory::create();
        $category = $this->categoryRepository->findOneBy(['name'=> 'Electronics']);

        $description = $faker->sentence;

        $this->client->request('PUT', sprintf('%s%s', $this->path, 1), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $this->jwtToken
        ], json_encode([
            'name' => $faker->name,
            'description' => $description,
            'price' => $faker->randomFloat(2, 10, 100),
            'brand' => $faker->company,
            'size' => $faker->randomElement(['S', 'M', 'L', 'XL']),
            'color' => $faker->safeColorName,
            'category_id' => $category->getId()
        ]));

        self::assertResponseStatusCodeSame(200);

        $updatedProduct = $this->productRepository->find(1);

        self::assertSame($description, $updatedProduct->getDescription());
    }

    public function testRemove(): void
    {
        $faker = Factory::create();
        $category = $this->categoryRepository->findOneBy(['name'=> 'Electronics']);

        $fixture = new Product();
        $fixture->setName($faker->word);
        $fixture->setDescription($faker->sentence);
        $fixture->setPrice($faker->randomFloat(2, 10, 100));
        $fixture->setBrand($faker->company);
        $fixture->setSize($faker->randomElement(['S', 'M', 'L', 'XL']));
        $fixture->setColor($faker->safeColorName);
        $fixture->setCategory($category);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('DELETE', sprintf('%s%s', $this->path, $fixture->getId()), [], [], [
            'HTTP_Authorization' => 'Bearer ' . $this->jwtToken
        ]);

        self::assertResponseStatusCodeSame(200);
    }

    protected function restoreExceptionHandler(): void
    {
        while (true) {
            $previousHandler = set_exception_handler(static fn() => null);
            restore_exception_handler();

            if ($previousHandler === null) {
                break;
            }

            restore_exception_handler();
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->restoreExceptionHandler();
    }
}
