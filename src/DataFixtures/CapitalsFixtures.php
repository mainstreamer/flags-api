<?php

namespace App\DataFixtures;

use App\Flags\Entity\Capital;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CapitalsFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        if (file_exists('capitals.json')) {
            ['countries' => $countries] = json_decode(file_get_contents('capitals.json'), true);
        }

        foreach ($countries ?? [] as $country) {
            $capital = new Capital($country['capital'], $country['name'], $country['isoCode'], $country['region']);
            $manager->persist($capital);
        }

        $manager->flush();
    }
}
