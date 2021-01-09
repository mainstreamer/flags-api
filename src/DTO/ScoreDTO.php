<?php

namespace App\DTO;

class ScoreDTO
{
    public int $sessionTimer;
    public int $score;
    public \DateTimeImmutable $date;
    
    public function __construct(array $requestArray)
    {
        $this->date = new \DateTimeImmutable();
        $this->score = $requestArray['score'];
        $this->sessionTimer = $requestArray['sessionTimer'];
    }
}
