<?php

namespace App\DataFixtures;

use App\Flags\Entity\Capital;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CapitalsFixtures extends Fixture
{
    private const array COUNTRY_FILES = [
        'capitals-africa.json',
        'capitals-americas.json',
        'capitals-asia.json',
        'capitals-europe.json',
        'capitals-oceania.json',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::COUNTRY_FILES as $fileName) {
            $this->loadFileContent($manager, $fileName);
        }
    }

    private function loadFileContent(ObjectManager $manager, string $fileName): void
    {
        if (file_exists($fileName)) {
            ['countries' => $countries] = json_decode(file_get_contents($fileName), true);
        }

        foreach ($countries ?? [] as $country) {
            $capital = new Capital($country['capital'], $country['name'], $country['isoCode'], $country['region']);
            $manager->persist($capital);
        }

        $manager->flush();
    }
}
