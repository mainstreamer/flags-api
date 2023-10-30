<?php

namespace App\Flags\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping\OneToMany;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass="App\Flags\Repository\UserRepository")
 */
class User implements UserInterface
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
    private string $telegramId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $firstName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $lastName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $telegramUsername;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $telegramPhotoUrl;
    
    /**
     * @ORM\Column(type="integer")
     */
    private int $highScore = 0;
    
    /**
     * @ORM\Column(type="integer")
     */
    private int $gamesTotal = 0;
    
    /**
     * @ORM\Column(type="integer")
     */
    private int $bestTime = 0;
    
    /**
     * @ORM\Column(type="integer")
     */
    private int $timeTotal = 0;
    
    /**
     * @OneToMany(targetEntity="Answer", mappedBy="user", cascade={"persist"})
     * @Ignore
     */
    private ?Collection $answers;
    
    public function __construct()
    {
        $this->answers = new ArrayCollection();
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTelegramId(): ?string
    {
        return $this->telegramId;
    }

    public function setTelegramId(string $telegramId): self
    {
        $this->telegramId = $telegramId;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getTelegramUsername(): ?string
    {
        return $this->telegramUsername;
    }

    public function setTelegramUsername(?string $telegramUsername): self
    {
        $this->telegramUsername = $telegramUsername;

        return $this;
    }

    public function getTelegramPhotoUrl(): ?string
    {
        return $this->telegramPhotoUrl;
    }

    public function setTelegramPhotoUrl(?string $telegramPhotoUrl): self
    {
        $this->telegramPhotoUrl = $telegramPhotoUrl;

        return $this;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getPassword()
    {
        // TODO: Implement getPassword() method.
    }

    public function getSalt()
    {
        // TODO: Implement getSalt() method.
    }

    public function getUsername(): ?string
    {
        return $this->getTelegramId();
    }

    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }
    
    public function getHighScore(): int
    {
        return $this->highScore;
    }
    
    public function setHighScore(int $score): void
    {
        $this->highScore = $score;   
    }
    
    /**
     * @return int
     */
    public function getGamesTotal(): int
    {
        return $this->gamesTotal;
    }
    
    /**
     * @param int $gamesTotal
     */
    public function setGamesTotal(int $gamesTotal): void
    {
        $this->gamesTotal = $gamesTotal;
    }
    
    /**
     * @return int
     */
    public function getBestTime(): int
    {
        return $this->bestTime;
    }
    
    /**
     * @param int $bestTime
     */
    public function setBestTime(int $bestTime): void
    {
        $this->bestTime = $bestTime;
    }
    
    /**
     * @return int
     */
    public function getTimeTotal(): int
    {
        return $this->timeTotal;
    }
    
    /**
     * @param Score $score
     * @param array[App\Entity\Answer] $answers
     */
    public function finalizeGame(Score $score, array $answers): void
    {
        ++$this->gamesTotal;
        $this->timeTotal += $score->getSessionTimer();
        if ($this->highScore <= $score->getScore()) {
            $this->highScore = $score->getScore();
            $this->bestTime  = $score->getSessionTimer();
        } elseif ($this->highScore == $score->getScore() && ($this->bestTime > $score->getSessionTimer())) {
            $this->bestTime  = $score->getSessionTimer();
        }

        foreach ($answers as $item) {
            $this->addAnswer($item);
        }
    }
    
    public function addAnswer(Answer $answer): void
    {
        $answer->setUser($this);
        $this->answers[] = $answer;
    }
    
    public function removeAnswer(Answer $answer): void
    {
        $answer->setUser(null);
        $this->answers->removeElement($answer);
    }
    
    /**
     * @return ArrayCollection|Collection
     */
    public function getAnswers(): ?Collection
    {
        return $this->answers;
    }

    public function getUserIdentifier(): string
    {
        return $this->id;
    }
}
