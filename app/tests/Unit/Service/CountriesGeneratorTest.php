<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Intl\Countries;

class CountriesGeneratorTest extends TestCase
{
    public function testCountryIsFetched(): void
    {
        // TODO move getCountryName to testable unit and update the test
        $this->assertFalse(Countries::exists('AC'));
    }
}
