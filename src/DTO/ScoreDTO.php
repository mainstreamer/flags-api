<?php

namespace App\DTO;

class ScoreDTO
{
    public int $sessionTimer;
    public int $score;
    public \DateTime $date;
    
    public function __construct(array $requestArray)
    {
        $this->date = new \DateTime();
        $this->score = $requestArray['score'];
        $this->sessionTimer = $requestArray['sessionTimer'];
    }
}
