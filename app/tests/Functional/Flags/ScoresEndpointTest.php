<?php

declare(strict_types=1);

namespace App\Tests\Functional\Flags;

use App\Tests\Functional\ApiTestCase;

final class ScoresEndpointTest extends ApiTestCase
{
    public function testScoresEndpointIsPublic(): void
    {
        $this->api->asGuest()
            ->get('/api/flags/scores')
            ->assertOk();
    }

    public function testScoresReturnsUsersOrderedByHighScore(): void
    {
        $this->users->create(['highScore' => 100, 'firstName' => 'Low']);
        $this->users->create(['highScore' => 500, 'firstName' => 'High']);
        $this->users->create(['highScore' => 300, 'firstName' => 'Mid']);

        $response = $this->api->get('/api/flags/scores')->assertOk();

        $scores = $response->getJson();

        $this->assertGreaterThanOrEqual(3, $scores);
        $this->assertSame('High', $scores[0]['firstName']);
        $this->assertSame('Mid', $scores[1]['firstName']);
        $this->assertSame('Low', $scores[2]['firstName']);
    }

    public function testProtectedEndpointRequiresAuth(): void
    {
        $this->api->asGuest()
            ->get('/api/flags/protected')
            ->assertUnauthorized();
    }

    public function testAuthenticatedUserCanAccessProtectedEndpoint(): void
    {
        $user = $this->users->create(['firstName' => 'John']);

        $this->api->asUser($user)
            ->get('/api/flags/protected')
            ->assertOk()
            ->assertJsonPath('firstName', 'John');
    }
}
