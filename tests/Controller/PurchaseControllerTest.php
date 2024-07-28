<?php

namespace App\Test\Controller;

use App\Entity\Purchase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PurchaseControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = '/api/purchases/';
    private string $jwtToken;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects(true);
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->manager->getRepository(Purchase::class);

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

    public function testListPurchases(): void
    {
        $this->client->request('GET', $this->path, [], [], [
            'HTTP_Authorization' => 'Bearer ' . $this->jwtToken
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    public function testCreatePurchase(): void
    {
        $this->client->request('POST', $this->path . 'new', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $this->jwtToken
        ], json_encode([
            'user_id' => 1,
            'product_id' => 1,
            'quantity' => 2,
            'purchase_date' => '2024-07-25T10:00:00Z'
        ]));

        $response = $this->client->getResponse();

        if ($response->getStatusCode() === Response::HTTP_CREATED) {
            $this->assertJsonStringEqualsJsonString(json_encode(['message' => 'Purchase created successfully']), $response->getContent());
        } elseif ($response->getStatusCode() === Response::HTTP_BAD_REQUEST) {
            $this->assertJson($response->getContent());
        } else {
            $this->fail('Unexpected HTTP status code: ' . $response->getStatusCode());
        }
    }

    public function testGetPurchaseById(): void
    {
        $this->client->request('GET', '/api/purchases/1', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $this->jwtToken
        ]);

        $response = $this->client->getResponse();

        if ($response->getStatusCode() === Response::HTTP_OK) {
            $this->assertJson($response->getContent());
        } elseif ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
            $this->assertJsonStringEqualsJsonString(json_encode(['error' => 'Purchase not found']), $response->getContent());
        } else {
            $this->fail('Unexpected HTTP status code: ' . $response->getStatusCode());
        }
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
