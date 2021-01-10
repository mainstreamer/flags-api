<?php

namespace App\Entity;

use App\DTO\ScoreDTO;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AnswerRepository")
 */
class Answer
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected string $id;
    
    /**
     * @ORM\Column(type="integer")
     */
    protected int $timer;
    
    /**
     * @ORM\Column(type="string", length=255)
     */
    protected string $flagCode;
    
    /**
     * @ORM\Column(type="string", length=255)
     */
    protected string $answerOptions;
    
    /**
     * @ORM\Column(type="boolean")
     */
    protected bool $correct;
    
    /**
     * @ORM\Column(type="datetime")
     */
    protected \DateTimeImmutable $date;
    
    /**
     * Many features have one product. This is the owning side.
     * @ManyToOne(targetEntity="User", inversedBy="answers")
     */
    protected ?User $user;
    
    public function __construct()
    {
        $this->date = new \DateTimeImmutable();
    }
    
    /**
     * @return int
     */
    public function getTimer(): int
    {
        return $this->timer;
    }
    
    /**
     * @param int $timer
     */
    public function setTimer(int $timer): void
    {
        $this->timer = $timer;
    }
    
    /**
     * @return string
     */
    public function getFlagCode(): string
    {
        return $this->flagCode;
    }
    
    /**
     * @param string $flagCode
     */
    public function setFlagCode(string $flagCode): void
    {
        $this->flagCode = $flagCode;
    }
    
    /**
     * @return array
     */
    public function getAnswerOptions(): array
    {
        return $this->answerOptions;
    }
    
    /**
     * @param array $answerOptions
     */
    public function setAnswerOptions(array $answerOptions): void
    {
        $this->answerOptions = json_encode($answerOptions);
    }
    
    /**
     * @return bool
     */
    public function isCorrect(): bool
    {
        return $this->correct;
    }
    
    /**
     * @param bool $correct
     */
    public function setCorrect(bool $correct): void
    {
        $this->correct = $correct;
    }
    
    /**
     * @return \DateTimeImmutable
     */
    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }
    
    /**
     * @param \DateTimeImmutable $date
     */
    public function setDate(\DateTimeImmutable $date): void
    {
        $this->date = $date;
    }
    
    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    
    public function fromArray(array $array): self
    {
        $item = new static;
        $item->setAnswerOptions($array['options']);
        $item->setFlagCode($array['answerCode']);
        $item->setTimer($array['time']);
        $item->setCorrect($array['correct']);
        
        return $item;
    }
    
    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }
    
    /**
     * @param User|null $user
     */
    public function setUser(?User $user): void
    {
        $this->user = $user;
    }
}
