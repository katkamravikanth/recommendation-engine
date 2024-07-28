<?php

namespace App\Test\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = '/api/users/';
    private string $jwtToken;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects(true);
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->manager->getRepository(User::class);

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

        $this->client->request('POST', $this->path . 'new', [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_Authorization' => 'Bearer ' . $this->jwtToken], json_encode([
            'name' => $faker->name,
            'email' => $faker->email,
            'password' => 'password'
        ]));

        self::assertResponseStatusCodeSame(201);
    }

    public function testEdit(): void
    {
        $faker = Factory::create();

        $name = $faker->name;

        $this->client->request('PUT', sprintf('%s%s', $this->path, 1), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $this->jwtToken
        ], json_encode([
            'name' => $name,
            'roles' => ['ROLE_ADMIN'],
            'password' => 'newpassword'
        ]));

        self::assertResponseStatusCodeSame(200);

        $updatedUser = $this->repository->find(1);

        self::assertSame($name, $updatedUser->getName());
    }

    public function testRemove(): void
    {
        $this->client->request('DELETE', sprintf('%s%s', $this->path, 1), [], [], ['HTTP_Authorization' => 'Bearer ' . $this->jwtToken]);

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
