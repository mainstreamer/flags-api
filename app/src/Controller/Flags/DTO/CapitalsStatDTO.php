<?php

namespace App\Flags\DTO;

use App\Flags\Entity\User;

readonly class CapitalsStatDTO
{
    public function __construct(
        public int $sessionTimer,
        public int $score,
        public \DateTime $created,
        public User $user,
    ) {
    }
}
