<?php

namespace App\Flags\Entity;

use App\Flags\Repository\AnswerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: AnswerRepository::class)]
class Answer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected int $id {
        get {
            return $this->id;
        }
    }

    #[ORM\Column(type: 'integer')]
    protected int $timer {
        get {
            return $this->timer;
        }
        set {
            $this->timer = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 255)]
    protected string $flagCode {
        get {
            return $this->flagCode;
        }
        set {
            $this->flagCode = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 255)]
    protected private(set) string $answerOptions {
        get => $this->answerOptions;
        set(array|string $value) {
            $this->answerOptions = is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : $value;
        }
    }

    #[ORM\Column(type: 'boolean')]
    protected bool $correct {
        get {
            return $this->correct;
        }
        set {
            $this->correct = $value;
        }
    }

    #[ORM\Column(type: 'datetime')]
    protected \DateTime $date {
        get {
            return $this->date;
        }
        set {
            $this->date = $value;
        }
    }

    #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'answers')]
    #[Ignore]
    public ?User $user {
        get => $this->user;

        set {
            $this->user = $value;
        }
    }

    public function fromArray(array $array): self
    {
        $item = new static();
        $item->answerOptions = $array['options'] ?? '';
        $item->flagCode = $array['answerCode'] ?? '';
        $item->timer = $array['time'] ?? 0;
        $item->correct = $array['correct'] ?? true;
        $item->date = isset($array['answerDateTime']) ? new \DateTime()->setTimestamp(
            round($array['answerDateTime'] / 1000))
        : new \DateTime();

        return $item;
    }
}
