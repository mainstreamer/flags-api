<?php

namespace App\DataFixtures;

use App\Flags\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
         $user = new User();
         $user->setTelegramId('994310081');

         $manager->persist($user);

         $manager->flush();
    }
}
