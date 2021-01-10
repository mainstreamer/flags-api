<?php

namespace App\Entity;

use App\DTO\ScoreDTO;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ScoreRepository")
 */
class Score
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected string $id;
    
    /**
     * @ORM\Column(type="string", length=255)
     */
    protected int $sessionTimer;
    
    /**
     * @ORM\Column(type="string", length=255)
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
    public function getSessionTimer()
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
