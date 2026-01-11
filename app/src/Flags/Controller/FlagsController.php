<?php

namespace App\Flags\Controller;

use App\Flags\DTO\ScoreDTO;
use App\Flags\Entity\Answer;
use App\Flags\Entity\Flag;
use App\Flags\Entity\Score;
use App\Flags\Entity\User;
use App\Flags\Repository\AnswerRepository;
use App\Flags\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Rteeom\FlagsGenerator\Enums\CodeSet;
use Rteeom\FlagsGenerator\Exceptions\FlagsGeneratorException;
use Rteeom\FlagsGenerator\FlagsGenerator;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/flags')]
class FlagsController extends AbstractController
{
    protected FlagsGenerator $flagsGenerator;

    public function __construct(
        protected ValidatorInterface $validator,
        protected string $botToken,
    ) {
        $this->flagsGenerator = new FlagsGenerator();
    }

    #[Route('/correct/{flag}', name: 'submit_correct', methods: ['POST'])]
    public function correct(
        #[MapEntity(mapping: ['flag' => 'code'])] Flag $flag,
        EntityManagerInterface $entityManager,
    ): Response {
        $flag->incrementCorrectAnswersCounter();
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_OK);
    }

    /**
     * @throws RandomException
     */
    #[Route('/test', name: 'test', methods: ['GET'])]
    public function getQuestion(): JsonResponse
    {
        $flags = [];

        while (count($flags) < 4) {
            $countryCode = chr(random_int(97, 122)) . chr(random_int(97, 122));
            $flag = $this->flagsGenerator->getEmojiFlagOrNull($countryCode, CodeSet::EXTENDED);
            if ($flag) {
                $flags[$countryCode] = $flag;
            }
        }

        $key = random_int(0, 3);

        return $this->json([
            'flags' => $flags,
            'ques' => $this->getCountryName(strtoupper(array_keys($flags)[$key])),
            'answer' => $flags[array_keys($flags)[$key]],
            'answerCode' => array_keys($flags)[$key],
        ]);
    }

    private function getCountryName(string $countryCode): string
    {
        return match ($countryCode) {
            // Extended ISO codes not in Symfony Intl component
            'XK' => 'Kosovo',
            'AC' => 'Ascension Island',
            'DG' => 'Diego Garcia',
            'EA' => 'Ceuta & Melilla',
            'IC' => 'Canary Islands',
            'TA' => 'Tristan da Cunha',
            default => Countries::exists($countryCode)
                ? Countries::getName($countryCode)
                : throw new \InvalidArgumentException("Unknown country code: $countryCode"),
        };
    }

    #[Route('/protected', name: 'get_profile', methods: ['GET', 'OPTIONS'])]
    public function getProfile(): Response
    {
        return $this->json($this->getUser());
    }

    #[IsGranted('PUBLIC_ACCESS')]
    #[Route('/scores', name: 'get_high_scores', methods: ['GET'])]
    public function getHighScores(UserRepository $repository): Response
    {
        return $this->json($repository->getHighScores());
    }

    /**
     * @throws \JsonException
     */
    #[Route('/scores', name: 'submit_game_results', methods: ['POST'])]
    public function postScore(
        Request $request,
        EntityManagerInterface $entityManager,
        #[CurrentUser] User $user,
    ): Response {
        $requestArray = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $scoreDTO = new ScoreDTO($requestArray);
        $score = new Score()->fromDTO($scoreDTO);

        $answers = [];
        if (isset($requestArray['answers'])) {
            foreach ($requestArray['answers'] as $answer) {
                $item = new Answer()->fromArray($answer);
                $answers[] = $item;
            }
        }

        $user->finalizeGame($score, $answers);
        $entityManager->flush();

        return new Response(null, Response::HTTP_OK);
    }

    #[Route('/test/{flag}', name: 'test_flag', methods: ['GET'])]
    public function getEmoji(string $flag): Response
    {
        $flag = $this->flagsGenerator->getEmojiFlagOrNull($flag) ?? 'invalid code';

        return new Response($flag);
    }

    /**
     * @throws FlagsGeneratorException
     */
    #[Route('/incorrect', name: 'incorrect', methods: ['GET', 'OPTIONS'])]
    public function getStat(#[CurrentUser] $user, AnswerRepository $repository): Response
    {
        $correctResults = $repository->findCorrectGuesses($user->getId());
        $result = $repository->findAllGuesses($user->getId());
        // TODO move this logic to service
        foreach ($result as $key => $item) {
            $result[$key]['flag'] = $this->flagsGenerator->getEmojiFlag($item['flagCode'], CodeSet::EXTENDED);
            $result[$key]['country'] = $this->getCountryName(strtoupper($item['flagCode']));
            foreach ($correctResults as $value) {
                if ($value['flagCode'] === $result[$key]['flagCode']) {
                    $shown = (int) $result[$key]['times'];
                    $guessed = (int) $value['times'];
                    $res = $shown - $guessed;
                    $result[$key]['rate'] = (int) (round($res / $shown, 2) * 100);
                    $result[$key]['times'] = $res . "/$shown";

                    break;
                }
            }

            if (!isset($result[$key]['rate'])) {
                $result[$key]['rate'] = 100;
                $result[$key]['times'] .= '/' . $result[$key]['times'];
            }
        }

        array_multisort($result, SORT_DESC, SORT_NUMERIC, array_column($result, 'rate'), SORT_DESC, SORT_NUMERIC);

        return $this->json($result);
    }

    #[Route('/correct', name: 'correct', methods: ['GET', 'OPTIONS'])]
    public function getRight(#[CurrentUser] $user, AnswerRepository $repository): Response
    {
        $correctResults = $repository->findCorrectGuesses($user->getId());
        $result = $repository->findAllGuesses($user->getId());
        // TODO move this logic to service
        foreach ($result as $key => $item) {
            $result[$key]['flag'] = $this->flagsGenerator->getEmojiFlag($item['flagCode'], CodeSet::EXTENDED);
            $result[$key]['country'] = $this->getCountryName(strtoupper($item['flagCode']));
            foreach ($correctResults as $value) {
                if ($value['flagCode'] === $result[$key]['flagCode']) {
                    $shown = (int) $result[$key]['times'];
                    $errors = (int) $value['times'];
                    $result[$key]['rate'] = (int) (round($errors / $shown, 2) * 100);
                    $result[$key]['times'] = "$errors/$shown";

                    break;
                }
            }

            if (!isset($result[$key]['rate'])) {
                $result[$key]['rate'] = 0;
                $result[$key]['times'] = '0/' . $result[$key]['times'];
            }
        }

        array_multisort(
            $result,
            SORT_DESC,
            SORT_NUMERIC,
            array_column($result, 'rate'),
            SORT_DESC,
            SORT_NUMERIC,
        );

        return $this->json($result);
    }
}
