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
        $user = $repo->findOneByTelegramId('994310081');

        if (!$user) {
            $user = new User();
            $user->setTelegramId('994310081');
            $user->setSub('1');
            $manager->persist($user);
            $manager->flush();
        }
    }
}
