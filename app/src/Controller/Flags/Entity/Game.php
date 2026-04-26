<?php

namespace App\Flags\Entity;

use App\Flags\Entity\Enum\GameType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Flags\Repository\GameRepository")]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $type;

    #[ORM\ManyToOne(targetEntity: "App\Flags\Entity\User")]
    private readonly User $user;

    #[ORM\Column(type: 'json')]
    private array $questions = [];

    public function __construct(
        User $user,
        GameType $type,
    ) {
        $this->user = $user;
        $this->type = $type->value;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function addQuestion(string $string): void
    {
        if (!in_array($string, $this->questions)) {
            $this->questions[] = $string;
        }
    }

    public function removeQuestion(string $string): void
    {
        if (($key = array_search($string, $this->questions)) !== false) {
            unset($this->questions[$key]);
        }
    }

    public function getQuestions(): array
    {
        return $this->questions;
    }

    public function getType(): GameType
    {
        return GameType::from($this->type);
    }
}
