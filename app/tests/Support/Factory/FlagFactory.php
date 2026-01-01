<?php

declare(strict_types=1);

namespace App\Tests\Support\Factory;

use App\Flags\Entity\Flag;
use Doctrine\ORM\EntityManagerInterface;

final class FlagFactory
{
    private int $sequence = 0;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function create(array $attributes = []): Flag
    {
        $flag = $this->make($attributes);
        $this->em->persist($flag);
        $this->em->flush();

        return $flag;
    }

    public function make(array $attributes = []): Flag
    {
        ++$this->sequence;

        $flag = new Flag();
        $flag->setCode($attributes['code'] ?? 'xx' . $this->sequence);

        if (isset($attributes['shows'])) {
            $flag->setShows($attributes['shows']);
        }

        return $flag;
    }

    public function createFromIsoCode(string $isoCode): Flag
    {
        return $this->create(['code' => strtolower($isoCode)]);
    }

    public function createMany(array $isoCodes): array
    {
        $flags = [];
        foreach ($isoCodes as $code) {
            $flags[] = $this->make(['code' => strtolower($code)]);
            $this->em->persist($flags[array_key_last($flags)]);
        }
        $this->em->flush();

        return $flags;
    }
}
