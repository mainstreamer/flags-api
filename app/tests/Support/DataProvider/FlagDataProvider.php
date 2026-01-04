<?php

declare(strict_types=1);

namespace App\Tests\Support\DataProvider;

final class FlagDataProvider
{
    private const array JSON_FILES = [
        'africa_extended.json',
        'americas.json',
        'asia_extended.json',
        'europe_extended.json',
        'oceania.json',
    ];

    public static function getProjectDir(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * Returns all ISO codes from all JSON files.
     * Format: ['isoCode' => ['isoCode', 'countryName', 'region', 'sourceFile']].
     */
    public static function allFlags(): array
    {
        $data = [];
        $projectDir = self::getProjectDir();

        foreach (self::JSON_FILES as $fileName) {
            $filePath = $projectDir . '/src/Common/Resources/' . $fileName;

            if (!file_exists($filePath)) {
                continue;
            }

            $content = file_get_contents($filePath);
            $json = json_decode($content, true);

            if (!is_array($json)) {
                continue;
            }

            foreach ($json as $country) {
                if (!isset($country['isoCode'])) {
                    continue;
                }

                $isoCode = $country['isoCode'];
                $data[$isoCode] = [
                    $isoCode,
                    $country['name'] ?? 'Unknown',
                    $country['region'] ?? 'Unknown',
                    $fileName,
                ];
            }
        }

        return $data;
    }

    /**
     * Returns flags grouped by region/file.
     */
    public static function flagsByRegion(): array
    {
        $data = [];
        $projectDir = self::getProjectDir();

        foreach (self::JSON_FILES as $fileName) {
            $filePath = $projectDir . '/src/Common/Resources/' . $fileName;

            if (!file_exists($filePath)) {
                $data[$fileName] = [[], $fileName, 'File not found'];
                continue;
            }

            $content = file_get_contents($filePath);
            $json = json_decode($content, true);

            if (!is_array($json)) {
                $data[$fileName] = [[], $fileName, 'Invalid JSON structure'];
                continue;
            }

            $codes = [];
            foreach ($json as $country) {
                if (isset($country['isoCode'])) {
                    $codes[] = [
                        'isoCode' => $country['isoCode'],
                        'name' => $country['name'] ?? 'Unknown',
                    ];
                }
            }

            $data[$fileName] = [$codes, $fileName, 'OK'];
        }

        return $data;
    }

    /**
     * Returns just ISO codes as a flat array.
     */
    public static function getAllIsoCodes(): array
    {
        return array_keys(self::allFlags());
    }
}
