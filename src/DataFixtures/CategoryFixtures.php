<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = ['Electronics', 'Books', 'Clothing', 'Home & Kitchen', 'Sports'];

        foreach ($categories as $i => $name) {
            $category = new Category();
            $category->setName($name);
            $category->setDescription("Description for $name");

            $manager->persist($category);

            $this->addReference("category{$i}", $category);
        }

        $manager->flush();
    }
}