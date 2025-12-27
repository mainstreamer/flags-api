<?php

namespace App\Entity;

use App\Flags\Entity\User;
use App\Flags\Repository\CapitalsStatRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CapitalsStatRepository::class)]
class CapitalsStat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected string $id;

    public function __construct(
        #[ORM\Column(type: 'integer', length: 255)]
        protected int $sessionTimer,
        #[ORM\Column(type: 'integer', length: 255)]
        protected int $score,
        #[ORM\ManyToOne(targetEntity: User::class)]
        protected readonly User $user,
        #[ORM\Column(type: 'datetime')]
        protected readonly \DateTime $created = new \DateTime(),
    ) {
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

    public function getCreated(): \DateTime
    {
        return $this->created;
    }
}
