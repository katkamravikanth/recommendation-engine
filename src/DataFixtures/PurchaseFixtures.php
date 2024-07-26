<?php

namespace App\DataFixtures;

use App\Entity\Purchase;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class PurchaseFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 5; $i++) {
            for ($j = 1; $j <= 2; $j++) {
                $purchase = new Purchase();
                $purchase->setUser($this->getReference("user{$i}"));
                $purchase->setProduct($this->getReference("product" . (($i - 1) * 4 + $j)));
                $purchase->setQuantity(mt_rand(1, 3));
                $purchase->setPurchaseDate(new \DateTime());

                $manager->persist($purchase);
            }
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