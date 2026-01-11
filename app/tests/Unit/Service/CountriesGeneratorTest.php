<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Flags\Repository\UserRepository;
use App\Flags\Security\HqAuthAuthenticator;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Routing\RouterInterface;

class CountriesGeneratorTest extends TestCase
{
    public function testCountryIsFetched(): void
    {
        // TODO move getCountryName to testable unit and update the test
        $this->assertFalse(Countries::exists('AC'));
    }
}
