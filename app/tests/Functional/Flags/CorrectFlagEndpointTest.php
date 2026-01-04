<?php

declare(strict_types=1);

namespace App\Tests\Functional\Flags;

use App\Flags\Entity\Flag;
use App\Tests\Functional\ApiTestCase;
use App\Tests\Support\DataProvider\FlagDataProvider;
use Doctrine\ORM\Exception\ORMException;

final class CorrectFlagEndpointTest extends ApiTestCase
{
    /**
     * @dataProvider flagDataProvider
     */
    public function testCorrectEndpointAcceptsValidFlagCode(
        string $isoCode,
        string $countryName,
        string $region,
        string $sourceFile,
    ): void {
        // Create flag in DB with lowercase code (as PopulateFlagsCommand does)
        $this->flags->createFromIsoCode($isoCode);

        $response = $this->api->asNewUser()
            ->post('/api/flags/correct/' . strtolower($isoCode));

        $this->assertSame(
            200,
            $response->getResponse()->getStatusCode(),
            sprintf(
                'Flag %s (%s) from %s returned %d instead of 200. Response: %s',
                $isoCode,
                $countryName,
                $sourceFile,
                $response->getResponse()->getStatusCode(),
                $response->getResponse()->getContent()
            )
        );
    }

    public function testCorrectEndpointReturns404ForUnknownFlag(): void
    {
        $this->api->asNewUser()
            ->post('/api/flags/correct/xx')
            ->assertNotFound();
    }

    public function testCorrectEndpointRequiresAuthentication(): void
    {
        $this->flags->create(['code' => 'de']);

        $this->api->asGuest()
            ->post('/api/flags/correct/de')
            ->assertUnauthorized();
    }

    /**
     * @throws ORMException
     */
    public function testCorrectEndpointIncrementsCounter(): void
    {
        $flag = $this->flags->create(['code' => 'de']);
        $code = $flag->getCode();
        $initialCount = $flag->getCorrectGuesses();

        // Flush to ensure the flag is persisted
        $this->em->flush();

        // Get the connection from entity manager and commit/restart transaction
        $connection = $this->em->getConnection();
        if ($connection->isTransactionActive()) {
            $connection->commit();
            $connection->beginTransaction();
        }

        $this->api
            ->asNewUser()
            ->post(sprintf('/api/flags/correct/%s', $code))
            ->assertOk();

        // Clear entity manager and reload from database to see changes
        $this->em->clear();
        $flag = $this->em->getRepository(Flag::class)->findOneBy(['code' => $code]);

        $this->assertNotNull($flag, 'Flag should still exist after increment');
        $this->assertSame($initialCount + 1, $flag->getCorrectGuesses());
    }

    public static function flagDataProvider(): array
    {
        return FlagDataProvider::allFlags();
    }
}
