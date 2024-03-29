<?php

namespace App\Flags\Entity;

use App\Flags\DTO\ScoreDTO;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Flags\Repository\ScoreRepository")
 */
class Score
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected int $id;
    
    /**
     * @ORM\Column(type="integer", length=255)
     */
    protected int $sessionTimer;
    
    /**
     * @ORM\Column(type="integer", length=255)
     * @Assert\Type("integer")
     */
    protected int $score;
    
    /**
     * @ORM\Column(type="datetime")
     */
    protected \DateTime $date;
    
    public function __construct()
    {
        $this->date = new \DateTime();
    }
    
    /**
     * @return int|mixed
     */
    public function getSessionTimer(): int
    {
        return $this->sessionTimer;
    }
    
    /**
     * @param int|mixed $sessionTimer
     */
    public function setSessionTimer($sessionTimer): void
    {
        $this->sessionTimer = $sessionTimer;
    }
    
    /**
     * @return int|mixed
     */
    public function getScore()
    {
        return $this->score;
    }
    
    /**
     * @param int|mixed $score
     */
    public function setScore($score): void
    {
        $this->score = $score;
    }
    
    public function fromDTO(ScoreDTO $dto): self
    {
        $score = new static();
        $score->setSessionTimer($dto->sessionTimer);
        $score->setScore($dto->score);
        
        return $score;
    }
}
