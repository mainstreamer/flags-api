<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FlagRepository")
 */
class Flag
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private string $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $code;

    /**
     * @ORM\Column(type="integer")
     */
    private int $shows = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private int $correctGuesses = 0;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return int
     */
    public function getShows(): int
    {
        return $this->shows;
    }

    /**
     * @param int $shows
     */
    public function setShows(int $shows): void
    {
        $this->shows = $shows;
    }

    /**
     * @return mixed
     */
    public function getCorrectGuesses(): int
    {
        return $this->correctGuesses;
    }

    /**
     * @param mixed $correctGuesses
     */
    public function setCorrectGuesses($correctGuesses): void
    {
        $this->correctGuesses = $correctGuesses;
    }
}
