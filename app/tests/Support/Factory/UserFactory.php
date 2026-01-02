<?php

declare(strict_types=1);

namespace App\Tests\Support\Factory;

use App\Flags\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class UserFactory
{
    private int $sequence = 0;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function create(array $attributes = []): User
    {
        $user = $this->make($attributes);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function make(array $attributes = []): User
    {
        ++$this->sequence;

        $user = new User();
        $user->setTelegramId($attributes['telegramId'] ?? 'test_telegram_' . $this->sequence);
        $user->setFirstName($attributes['firstName'] ?? 'Test');
        $user->setLastName($attributes['lastName'] ?? 'User ' . $this->sequence);
        $user->setTelegramUsername($attributes['telegramUsername'] ?? 'testuser' . $this->sequence);

        if (isset($attributes['sub'])) {
            $user->setSub($attributes['sub']);
        } else {
            $user->setSub('test_sub_' . $this->sequence);
        }

        if (isset($attributes['highScore'])) {
            $user->setHighScore($attributes['highScore']);
        }

        if (isset($attributes['gamesTotal'])) {
            $user->setGamesTotal($attributes['gamesTotal']);
        }

        return $user;
    }

    public function withHighScore(int $score): self
    {
        return new class ($this, ['highScore' => $score]) extends UserFactory {
            public function __construct(
                private readonly UserFactory $parent,
                private readonly array $defaults,
            ) {
            }

            public function create(array $attributes = []): User
            {
                return $this->parent->create(array_merge($this->defaults, $attributes));
            }

            public function make(array $attributes = []): User
            {
                return $this->parent->make(array_merge($this->defaults, $attributes));
            }
        };
    }
}
