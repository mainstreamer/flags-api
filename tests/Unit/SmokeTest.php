<?php

namespace Unit;

use Doctrine\ORM\EntityManagerInterface;
use Rteeom\FlagsGenerator\FlagsGenerator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SmokeTest extends KernelTestCase
{
    private const COUNTRY_FILES = [
        'capitals-africa.json',
        'capitals-americas.json',
        'capitals-asia.json',
        'capitals-europe.json',
        'capitals-oceania.json',
    ];

    protected EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testSomething()
    {
        $flagsGenerator = new FlagsGenerator();
        foreach (self::COUNTRY_FILES as $fileName) {
            if (file_exists($fileName)) {
                ['countries' => $countries] = json_decode(file_get_contents($fileName), true);
                foreach ($countries ?? [] as $country) {
                    $this->assertNotNull($flagsGenerator->getEmojiFlagOrNull($country['isoCode']), 'Error with '.$country['isoCode']);
                }
            }
        }

        $this->assertTrue(true);
    }
}
