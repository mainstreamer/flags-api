<?php

namespace App\Flags\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToOne;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass="App\Flags\Repository\AnswerRepository")
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
    protected \DateTime $date;
    
    /**
     * Many features have one product. This is the owning side.
     * @ManyToOne(targetEntity="User", inversedBy="answers")
     * @Ignore
     */
    protected ?User $user;
    
    public function __construct()
    {
        $this->date = new \DateTime();
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
    public function getAnswerOptions(): string
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
     * @return \DateTime
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }
    
    /**
     * @param \DateTime $date
     */
    public function setDate(\DateTime $date): void
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
