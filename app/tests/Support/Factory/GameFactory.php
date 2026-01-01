<?php

declare(strict_types=1);

namespace App\Tests\Support\Factory;

use App\Flags\Entity\Enum\GameType;
use App\Flags\Entity\Game;
use App\Flags\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class GameFactory
{
    private ?User $user = null;
    private GameType $type = GameType::CAPITALS_EUROPE;
    private array $questions = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserFactory $userFactory,
    ) {
    }

    public function create(array $attributes = []): Game
    {
        $game = $this->make($attributes);
        $this->em->persist($game);
        $this->em->flush();

        $this->reset();

        return $game;
    }

    public function make(array $attributes = []): Game
    {
        $user = $attributes['user'] ?? $this->user ?? $this->userFactory->create();
        $type = $attributes['type'] ?? $this->type;

        $game = new Game($user, $type);

        $questions = $attributes['questions'] ?? $this->questions;
        foreach ($questions as $question) {
            $game->addQuestion($question);
        }

        return $game;
    }

    public function forUser(User $user): self
    {
        $clone = clone $this;
        $clone->user = $user;

        return $clone;
    }

    public function withType(GameType $type): self
    {
        $clone = clone $this;
        $clone->type = $type;

        return $clone;
    }

    public function withQuestions(array $questions): self
    {
        $clone = clone $this;
        $clone->questions = $questions;

        return $clone;
    }

    public function forEurope(): self
    {
        return $this->withType(GameType::CAPITALS_EUROPE);
    }

    public function forAsia(): self
    {
        return $this->withType(GameType::CAPITALS_ASIA);
    }

    public function forAfrica(): self
    {
        return $this->withType(GameType::CAPITALS_AFRICA);
    }

    public function forAmericas(): self
    {
        return $this->withType(GameType::CAPITALS_AMERICAS);
    }

    public function forOceania(): self
    {
        return $this->withType(GameType::CAPITALS_OCEANIA);
    }

    private function reset(): void
    {
        $this->user = null;
        $this->type = GameType::CAPITALS_EUROPE;
        $this->questions = [];
    }
}
