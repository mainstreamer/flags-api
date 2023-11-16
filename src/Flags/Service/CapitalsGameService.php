<?php

namespace App\Flags\Service;

use App\Flags\Entity\Capital;
use App\Flags\Repository\CapitalRepository;
use Exception;
use Rteeom\FlagsGenerator;

class CapitalsGameService
{
    private readonly FlagsGenerator $isoFlags;
    public function __construct(private readonly CapitalRepository $repository)
    {
        $this->isoFlags = new FlagsGenerator();
    }

    /**
     * @throws Exception
     */
    public function getQuestion(): array
    {
        $african = $this->repository->findBy(['region' => 'Africa'], ['id' => 'ASC']);
        if (!$african) {
            throw new Exception('no countries found');
        }

        $options = [];
        while (count($options) < 4) {
            shuffle($african);
            $options[] = array_pop($african);
        }
        $optionsDebug = $options;
        /** @var Capital $correct */
        $correct = (array_pop($options));

        $options = [
            ['option' => $correct->getName(), 'country' => $correct->getCountry(), 'flag' => $this->isoFlags->getEmojiFlag(strtolower($correct->getCode()))],
            ['option' => ($entry = array_pop($options))->getName(), 'country' => $entry->getCountry(), 'flag' => $this->isoFlags->getEmojiFlag(strtolower($entry->getCode()))],
            ['option' => ($entry = array_pop($options))->getName(), 'country' => $entry->getCountry(), 'flag' => $this->isoFlags->getEmojiFlag(strtolower($entry->getCode()))],
            ['option' => ($entry = array_pop($options))->getName(), 'country' => $entry->getCountry(), 'flag' => $this->isoFlags->getEmojiFlag(strtolower($entry->getCode()))],
        ];
        shuffle($options);
        try {
            return [
                'text' => sprintf('Capital of %s?', $correct->getCountry()),
                'flag' => $this->isoFlags->getEmojiFlag(strtolower($correct->getCode())),
                'isoCode' => $correct->getCode(),
                'correct' => $correct->getName(),
                'options' => $options,
            ];
        } catch (\Throwable $e) {
            return [$e->getMessage(), array_map(fn (Capital $item) => $item->getCode(), $optionsDebug)];
        }
    }

    public function giveAnswer(string $isoCode, string $answer): array
    {
        $capital = $this->repository->findOneBy(['code' => $isoCode]);
        $isCorrect = strtolower($capital->getName()) === strtolower($answer);
        return ['text' => sprintf('%s it\'s %s', $isCorrect ? 'Yes ✅ - ': 'No ❌ - ', $capital->getName())];
    }
}
