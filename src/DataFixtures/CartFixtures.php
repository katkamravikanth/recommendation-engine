<?php

namespace App\DataFixtures;

use App\Entity\Cart;
use App\Entity\CartItem;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class CartFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 2; $i++) {
            $cart = new Cart();
            $cart->setUser($this->getReference("user{$i}"));

            for ($j = 1; $j <= 2; $j++) {
                $cartItem = new CartItem();
                $cartItem->setCart($cart);
                $cartItem->setProduct($this->getReference("product" . (($i - 1) * 10 + $j)));
                $cartItem->setQuantity(mt_rand(1, 3));

                $manager->persist($cartItem);
                $cart->addItem($cartItem);
            }

            $manager->persist($cart);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class,
            ProductFixtures::class,
        ];
    }
}