<?php

namespace App\Flags\Service;

use App\Flags\Entity\Capital;
use App\Flags\Entity\CapitalsStat;
use App\Flags\Entity\Enum\GameType;
use App\Flags\Entity\Game;
use App\Flags\Entity\User;
use App\Flags\Repository\CapitalRepository;
use App\Flags\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Rteeom\FlagsGenerator\FlagsGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

readonly class CapitalsGameService
{
    private FlagsGenerator $isoFlags;
    public function __construct(
        private CapitalRepository      $repository,
        private GameRepository         $gameRepository,
        private TokenStorageInterface  $tokenStorage,
        private EntityManagerInterface $entityManager
    )
    {
        $this->isoFlags = new FlagsGenerator();
    }

    /**
     * @throws Exception
     */
    public function getQuestion(?Game $game = null): array
    {
        $excluded = $game !== null ? $game->getQuestions() : [];

        $region = match ($game->getType()->value) {
          GameType::CAPITALS_AFRICA->value => 'Africa',
          GameType::CAPITALS_AMERICAS->value => 'Americas',
          GameType::CAPITALS_ASIA->value => 'Asia',
          GameType::CAPITALS_EUROPE->value => 'Europe',
          GameType::CAPITALS_OCEANIA->value => 'Oceania',
        };

        $countries = $this->repository->findBy(['region' => $region], ['id' => 'ASC']);
        if (!$countries) {
            throw new Exception('no countries found');
        }

        $totalQuestions = count($countries);
        $excludedCount = count($excluded);
        $options = [];

        while (count($options) < 4) {
            shuffle($countries);
            if (count($options) === 3) {
                $capital = array_pop($countries);
                if ($excludedCount < $totalQuestions && in_array($capital->getCode(), $excluded)) {

                } else {
                    $correct = $capital;
                    $options[] = $correct;
                    if ($game) {
                        $game->addQuestion($correct->getCode());
                        $this->entityManager->flush();
                    }
                }
            } else {
                $options[] = array_pop($countries);
            }
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

    public function giveAnswer(string $questionCountryCode, string $answer, Game $game): array
    {
        /** @var Capital $capital */
        $capital = $this->repository->findOneBy(['code' => $questionCountryCode]);
        $isCorrect = strtolower($capital->getName()) === strtolower($answer);
        if (!$isCorrect) {
            $game->removeQuestion($questionCountryCode);
            $this->entityManager->flush();
        }
        return [
            'isCorrect' => $isCorrect,
            'text' => sprintf('%s it\'s %s', $isCorrect ? 'Yes ✅ - ': 'No ❌ - ', $capital->getName()),
        ];
    }

    public function handleGameOver(Request $request): array
    {
        [
            'sessionTimer' => $sessionTimer,
            'score' => $score,
            'gameId' => $gameId
        ] = json_decode($request->getContent(), true);

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        /** @var Game $game */
        $game = $this->gameRepository->findOneById((int) $gameId);

        $entity = new CapitalsStat($sessionTimer, $score, $user, $game->getType());
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return [$entity->getId()];
    }

    public function getHighScores(string $gameType): array
    {
        return array_map(
            fn (array $item) => [
                'userName' => $item['firstName'] .' ' .$item['lastName'],'score' => $item['score'],
                'sessionTimer' => $item['sessionTimer']
            ],
            $this->entityManager->getRepository(CapitalsStat::class)->getHighScores($gameType)
        );
    }

    public function startGame(GameType $gameType): Game
    {
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $this->entityManager->persist($game = new Game($user, $gameType));
        $this->entityManager->flush();

        return $game;
    }
}
