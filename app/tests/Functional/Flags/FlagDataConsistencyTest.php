<?php

declare(strict_types=1);

namespace App\Tests\Functional\Flags;

use App\Flags\Entity\Flag;
use App\Tests\Functional\ApiTestCase;
use App\Tests\Support\DataProvider\FlagDataProvider;
use Rteeom\FlagsGenerator\FlagsGenerator;
use Symfony\Component\Intl\Countries;

final class FlagDataConsistencyTest extends ApiTestCase
{
    public function testAllJsonIsoCodesAreValidIso3166(): void
    {
        // XK (Kosovo) is a user-assigned code, not in official ISO 3166-1
        // but widely used and recognized
        $allowedNonStandardCodes = ['XK'];

        $invalidCodes = [];
        $allFlags = FlagDataProvider::allFlags();

        foreach ($allFlags as $isoCode => [$code, $countryName, $region, $sourceFile]) {
            $upperCode = strtoupper($isoCode);

            // Skip known non-standard but accepted codes
            if (in_array($upperCode, $allowedNonStandardCodes, true)) {
                continue;
            }

            // Symfony Intl uses uppercase ISO codes
            if (!Countries::exists($upperCode)) {
                $invalidCodes[] = sprintf(
                    '%s (%s) from %s - not a valid ISO 3166-1 alpha-2 code',
                    $isoCode,
                    $countryName,
                    $sourceFile
                );
            }
        }

        if (!empty($invalidCodes)) {
            $this->fail(
                "Invalid ISO codes found in JSON files:\n" . implode("\n", $invalidCodes)
            );
        }

        $this->assertTrue(true, 'All ISO codes are valid');
    }

    public function testNoDuplicateIsoCodesAcrossRegions(): void
    {
        $seenCodes = [];
        $duplicates = [];

        foreach (FlagDataProvider::flagsByRegion() as $fileName => [$codes, $file, $status]) {
            if ('OK' !== $status) {
                continue;
            }

            foreach ($codes as $country) {
                $isoCode = $country['isoCode'];
                if (isset($seenCodes[$isoCode])) {
                    $duplicates[] = sprintf(
                        '%s (%s) appears in both %s and %s',
                        $isoCode,
                        $country['name'],
                        $seenCodes[$isoCode],
                        $fileName
                    );
                }
                $seenCodes[$isoCode] = $fileName;
            }
        }

        if (!empty($duplicates)) {
            $this->fail(
                "Duplicate ISO codes found across JSON files:\n" . implode("\n", $duplicates)
            );
        }

        $this->assertTrue(true, 'No duplicates found');
    }

    public function testPopulateCommandCreatesFlagsMatchingJson(): void
    {
        // Simulate what PopulateFlagsCommand does
        $jsonCodes = FlagDataProvider::getAllIsoCodes();
        $jsonCodesLower = array_map('strtolower', $jsonCodes);

        // Create flags as the command would
        $this->flags->createMany($jsonCodes);

        // Verify all were created
        $dbFlags = $this->em->getRepository(Flag::class)->findAll();
        $dbCodes = array_map(fn (Flag $f) => $f->getCode(), $dbFlags);

        sort($jsonCodesLower);
        sort($dbCodes);

        $this->assertSame(
            $jsonCodesLower,
            $dbCodes,
            'Database flags should match JSON codes (lowercase)'
        );
    }

    /**
     * @dataProvider flagDataProvider
     */
    public function testFlagEmojiCanBeGenerated(
        string $isoCode,
        string $countryName,
        string $region,
        string $sourceFile,
    ): void {
        $flagsGenerator = new FlagsGenerator();
        $emoji = $flagsGenerator->getEmojiFlagOrNull(strtolower($isoCode));

        $this->assertNotNull(
            $emoji,
            sprintf(
                'Flag emoji should be generated for %s (%s) from %s',
                $isoCode,
                $countryName,
                $sourceFile
            )
        );

        $this->assertNotEmpty(
            $emoji,
            sprintf('Flag emoji for %s should not be empty', $isoCode)
        );
    }

    public static function flagDataProvider(): array
    {
        return FlagDataProvider::allFlags();
    }
}
