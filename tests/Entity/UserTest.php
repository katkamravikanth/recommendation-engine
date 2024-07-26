<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $user = new User();
        $user->setName('John Doe');
        $user->setEmail('john.doe@example.com');
        $user->setPassword('securepassword123');

        $this->assertEquals('John Doe', (string) $user->getName());
        $this->assertEquals('john.doe@example.com', (string) $user->getEmail());
        $this->assertEquals('securepassword123', (string) $user->getPassword());
    }
}