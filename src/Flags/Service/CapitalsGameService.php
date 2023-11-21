<?php

namespace App\Flags\Service;

use App\Flags\Entity\Capital;
use App\Flags\Entity\CapitalsStat;
use App\Flags\Entity\Enum\GameType;
use App\Flags\Entity\Game;
use App\Flags\Entity\User;
use App\Flags\Repository\CapitalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Rteeom\FlagsGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CapitalsGameService
{
    private readonly FlagsGenerator $isoFlags;
    public function __construct(
        private readonly CapitalRepository $repository,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly EntityManagerInterface $entityManager
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

        $african = $this->repository->findBy(['region' => 'Africa'], ['id' => 'ASC']);
        if (!$african) {
            throw new Exception('no countries found');
        }

        $totalQuestions = count($african);
        $options = [];
        $excludedCount = count($excluded);
        while (count($options) < 4) {
            shuffle($african);
            if (count($options) === 3) {
                $capital = array_pop($african);
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
                $options[] = array_pop($african);
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

    public function giveAnswer(string $isoCode, string $answer): array
    {
        /** @var Capital $capital */
        $capital = $this->repository->findOneBy(['code' => $isoCode]);
        $isCorrect = strtolower($capital->getName()) === strtolower($answer);
        return [
            'isCorrect' => $isCorrect,
            'text' => sprintf('%s it\'s %s', $isCorrect ? 'Yes ✅ - ': 'No ❌ - ', $capital->getName()),
        ];
    }

    public function handleGameOver(Request $request): array
    {
        ['sessionTimer' => $sessionTimer, 'score' => $score] = json_decode($request->getContent(), true);
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $entity = new CapitalsStat($sessionTimer, $score, $user);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return [$entity->getId()];
    }

    public function getHighScores(): array
    {
        return array_map(fn (array $item) =>
        ['userName' => $item['firstName'] .' ' .$item['lastName'],'score' => $item['score'], 'sessionTimer' => $item['sessionTimer']], $this->entityManager->getRepository(CapitalsStat::class)->getHighScores());
    }

    public function startGame(): Game
    {
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $this->entityManager->persist($g = new Game($user, GameType::CAPITALS_AFRICA));
        $this->entityManager->flush();
        return $g;
    }
}
