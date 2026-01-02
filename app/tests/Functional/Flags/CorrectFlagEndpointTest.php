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
        $flag = $this->em->getRepository(Flag::class)->findOneBy(criteria: []);
        $this->assertInstanceOf(Flag::class, $flag);
        $code = $flag->getCode();
        $initialCount = $flag->getCorrectGuesses();

        $this->api
            ->asNewUser()
            ->post(sprintf('/api/flags/correct/%s', $code))
            ->assertOk();

        $flag = $this->refreshEntity($flag);
        $this->assertSame($initialCount + 1, $flag->getCorrectGuesses());
    }

    public static function flagDataProvider(): array
    {
        return FlagDataProvider::allFlags();
    }
}
