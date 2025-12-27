<?php

namespace App\Flags\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: "App\Flags\Repository\AnswerRepository")]
class Answer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected int $id;

    #[ORM\Column(type: 'integer')]
    protected int $timer;

    #[ORM\Column(type: 'string', length: 255)]
    protected string $flagCode;

    #[ORM\Column(type: 'string', length: 255)]
    protected string $answerOptions;

    #[ORM\Column(type: 'boolean')]
    protected bool $correct;

    #[ORM\Column(type: 'datetime')]
    protected \DateTime $date;

    #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'answers')]
    #[Ignore]
    protected ?User $user;

    public function getTimer(): int
    {
        return $this->timer;
    }

    public function setTimer(int $timer): void
    {
        $this->timer = $timer;
    }

    public function getFlagCode(): string
    {
        return $this->flagCode;
    }

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

    public function setAnswerOptions(array $answerOptions): void
    {
        $this->answerOptions = json_encode($answerOptions);
    }

    public function isCorrect(): bool
    {
        return $this->correct;
    }

    public function setCorrect(bool $correct): void
    {
        $this->correct = $correct;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function fromArray(array $array): self
    {
        $item = new static();
        $item->setAnswerOptions($array['options']);
        $item->setFlagCode($array['answerCode']);
        $item->setTimer($array['time']);
        $item->setCorrect($array['correct']);
        $item->setDate((new \DateTime())->setTimestamp(round($array['answerDateTime'] / 1000)));

        return $item;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }
}
