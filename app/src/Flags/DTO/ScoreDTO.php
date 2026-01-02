<?php

namespace App\Flags\DTO;

class ScoreDTO
{
    public int $sessionTimer;
    public int $score;
    public \DateTime $date;

    public function __construct(array $requestArray)
    {
        $this->date = new \DateTime();
        $this->score = $requestArray['score'] ?? 0;
        $this->sessionTimer = $requestArray['sessionTimer'] ?? 999;
    }
}
