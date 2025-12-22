<?php

namespace App\Flags\Entity;

use App\Flags\Entity\Enum\GameType;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

#[ORM\Entity(repositoryClass: "App\Flags\Repository\CapitalsStatRepository")]
class CapitalsStat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected int $id;

    #[ORM\Column(type: "integer", length: 255)]
    protected int $sessionTimer;

    #[ORM\Column(type: "integer", length: 255)]
    protected int $score;

    #[ORM\ManyToOne(targetEntity: "App\Flags\Entity\User")]
    protected readonly User $user;

    #[ORM\Column(type: "string", length: 255)]
    protected string $gameType;

    #[ORM\Column(type: "datetime")]
    protected readonly DateTime $created;

    public function __construct(
        int $sessionTimer,
        int $score,
        User $user,
        GameType $gameType,
        DateTime $created = new DateTime()
    ) {
        $this->sessionTimer = $sessionTimer;
        $this->score = $score;
        $this->user = $user;
        $this->created = $created;
        $this->gameType = $gameType->value;
    }

    public function getSessionTimer(): int
    {
        return $this->sessionTimer;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getGameType(): GameType
    {
        return GameType::from($this->gameType);
    }
}
