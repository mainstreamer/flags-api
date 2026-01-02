<?php

declare(strict_types=1);

namespace App\Tests\Functional\Capitals;

use App\Flags\Entity\Enum\GameType;
use App\Tests\Functional\ApiTestCase;

final class GameStartEndpointTest extends ApiTestCase
{
    public function testGuestCannotStartGame(): void
    {
        $this->api->asGuest()
            ->get('/capitals/game-start/' . GameType::CAPITALS_EUROPE->value)
            ->assertUnauthorized();
    }

    public function testAuthenticatedUserCanStartGame(): void
    {
        $this->api->asNewUser()
            ->get('/capitals/game-start/' . GameType::CAPITALS_EUROPE->value)
            ->assertOk()
            ->assertJsonPathExists('gameId');
    }

    public function testGameIsPersistedWithCorrectType(): void
    {
        $user = $this->users->create();

        $response = $this->api->asUser($user)
            ->get('/capitals/game-start/' . GameType::CAPITALS_ASIA->value)
            ->assertOk();

        $gameId = $response->getJson()['gameId'];

        $game = $this->em->getRepository(\App\Flags\Entity\Game::class)->find($gameId);

        $this->assertNotNull($game);
        $this->assertSame(GameType::CAPITALS_ASIA, $game->getType());
    }
}
