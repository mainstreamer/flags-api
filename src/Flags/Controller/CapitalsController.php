<?php

namespace App\Flags\Controller;

use App\Flags\Entity\Enum\GameType;
use App\Flags\Entity\Game;
use App\Flags\Entity\User;
use App\Flags\Service\CapitalsGameService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Rteeom\FlagsGenerator\FlagsGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route(path:'/v1', name: "api_")]
class CapitalsController extends AbstractController
{
    protected FlagsGenerator $flagsGenerator;

    public function __construct(
        protected ValidatorInterface $validator, 
        protected string $botToken,
        protected readonly EntityManagerInterface $em

    ) {
        $this->flagsGenerator = new FlagsGenerator();
    }

    #[Route('/capitals', name: 'get_question', methods: ['GET'])]
    public function check(CapitalsGameService $service): JsonResponse
    {
        return $this->json($service->getQuestion());
    }

    #[Route('/capitals/question/{game}', name: 'get_question_with_game', methods: ['GET'])]
    public function questionForGame(Game $game, CapitalsGameService $service): JsonResponse
    {
        return $this->json($service->getQuestion($game));
    }

    #[Route('/capitals/answer/{game}/{countryCode}/{answer}', name: 'answer_cap', methods: ['GET'])]
    public function answer(CapitalsGameService $service, Game $game, string $countryCode, string $answer): JsonResponse
    {
        return $this->json($service->giveAnswer($countryCode, base64_decode($answer), $game));
    }

    #[Route('/capitals/game-over', name: 'capitals_game_over', methods: ['POST'])]
    public function gameOver(Request $request, CapitalsGameService $service): JsonResponse
    {
        try {
            $entity = $service->handleGameOver($request);
           return new JsonResponse($entity);
        } catch (\Throwable $e) {
            return new JsonResponse($e->getMessage());
        }
    }

    #[Route('/capitals/answer/{game}/{countryCode}/{answer}', name: 'get_question_for_game', methods: ['GET'])]
    public function getQuestion(Game $game, string $countryCode, string $answer, CapitalsGameService $service): JsonResponse
    {
        return $this->json($service->giveAnswer($countryCode, base64_decode($answer), $game));
    }

    #[Route('/capitals/high-scores/{gameType}', name: 'capitals_high_scores', methods: ['GET'])]
    public function highScores(Request $request, string $gameType, CapitalsGameService $service): JsonResponse
    {
        $type = match (strtolower($gameType)) {
            'europe' => GameType::CAPITALS_EUROPE,
            'asia' => GameType::CAPITALS_ASIA,
            'africa' => GameType::CAPITALS_AFRICA,
            'oceania' => GameType::CAPITALS_OCEANIA,
            'americas' => GameType::CAPITALS_AMERICAS,
        };

        try {
            return new JsonResponse($service->getHighScores($type->value));
        } catch (\Throwable $e) {
            return new JsonResponse($e->getMessage());
        }
    }

    #[Route('/capitals/test', name: 'test_cap', methods: ['GET'])]
    public function test(CapitalsGameService $service): JsonResponse
    {
        try {
            return new JsonResponse($service->startGame(GameType::CAPITALS_EUROPE));
        } catch (\Throwable $e) {
            return new JsonResponse($e->getMessage());
        }

        return $this->json($this->flagsGenerator->getEmojiFlagOrNull('ss'));
    }

    #[Route('/capitals/test2', name: 'test_cap2', methods: ['GET'])]
    public function test2(CapitalsGameService $service): JsonResponse
    {
        try {
            return new JsonResponse($service->startGame(GameType::CAPITALS_EUROPE));
        } catch (\Throwable $e) {
            return new JsonResponse($e->getMessage());
        }

        return $this->json($this->flagsGenerator->getEmojiFlagOrNull('ss'));
    }

    #[Route('/capitals/game-start/{type}', name: 'capitals_game_start', methods: ['GET'])]
    public function startGame(string $type, CapitalsGameService $service): JsonResponse
    {
        try {
            $game = $service->startGame(GameType::from($type));
            return new JsonResponse(['gameId' => $game->getId()]);
        } catch (\Throwable $e) {
            return new JsonResponse($e->getMessage());
        }
    }

    #[Route('/api/tg/login/demo', name: 'telegramLogin3', methods: ['GET'])]
    public function authDemoAction(Request $request, JWTEncoderInterface $encoder): Response
    {
        $user = $this->em->getRepository(User::class)->findOneByTelegramUsername('rteeom');
        $token = $encoder
            ->encode([
                'username' => $user->getTelegramId(),
                'exp' => time() + 600000 + getenv('JWT_TOKEN_TTL')
            ]);
        return new JsonResponse(['token' => $token]);
    }

        #[Route('/api/tg/login', name: 'telegramLogin2', methods: ['GET'])]
    public function authAction(Request $request, JWTEncoderInterface $encoder): Response
    {
        try {
        $data = $request->query->all();
        $hash = $data['hash'];
        unset($data['hash']);

        $data_check_arr = [];
        foreach ($data as $key => $value) {
            $data_check_arr[] = $key . '=' . $value;
        }

        sort($data_check_arr);
        $data_check_string = implode("\n", $data_check_arr);
        $bot_token = $this->botToken;
        $check_hash = $hash;
        $secret_key = hash('sha256', $bot_token, true);
        $hash = hash_hmac('sha256', $data_check_string, $secret_key);

        if (strcmp($hash, $check_hash) !== 0) {
            throw new \Exception('Data is NOT from Telegram');
        }

        if ((time() - $data['auth_date']) > 86400) {
            throw new \Exception('Data is outdated');
        }

        $user = $this->em->getRepository(User::class)->findOneByTelegramId($data['id']);

        if ($user === null) {
            $user = new User();
            $user->setTelegramId($data['id']);
            $user->setFirstName($data['first_name']);
            $user->setLastName($data['last_name']);
            $user->setTelegramUsername($data['username'] ?? null);
            $user->setTelegramPhotoUrl($data['photo_url'] ?? null);
            $this->em->persist($user);
            $this->em->flush();
        }

        $token = $encoder
            ->encode([
                'username' => $user->getTelegramId(),
                'exp' => time() + 600000 + getenv('JWT_TOKEN_TTL')
            ]);

        } catch (\Throwable $exception) {
            return new JsonResponse(['error' => $exception->getMessage()]);
        }

        return new JsonResponse(['token' => $token]);
    }
}
