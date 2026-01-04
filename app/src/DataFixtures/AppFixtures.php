<?php

namespace App\DataFixtures;

use App\Flags\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $repo = $manager->getRepository(User::class);
        $user = $repo->findOneBy(['sub' => '1']);

        if (!$user) {
            $user = new User();
            $user->setSub('1');
            $user->setFirstName('Test');
            $user->setLastName('User');
            $manager->persist($user);
            $manager->flush();
        }
    }
}
