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
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Rteeom\FlagsGenerator\FlagsGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

#[Route('/api/flags')]
class GameController extends AbstractController
{
    protected FlagsGenerator $flagsGenerator;

    public function __construct(
        protected ValidatorInterface $validator, 
        protected string $botToken,

    ) {
        $this->flagsGenerator = new FlagsGenerator();
    }

    #[Route('/check', name: 'checkaaaa', methods: ['GET'])]
    public function check(): JsonResponse
    {
        return $this->json(['ok']);
    }
    #[Route('/test', name: 'test', methods: ['GET'])]
    public function getQuestion(): JsonResponse
    {
        $flags = [];

        while (count($flags) < 4) {
            $countryCode = chr(rand(97,122)).chr(rand(97,122));
            $flag = $this->flagsGenerator->getEmojiFlagOrNull($countryCode);
            if ($flag) {
                $flags[$countryCode] = $flag;
            }
        }

        $number = rand(0, 3);

        return $this->json([
            'APP_ENV' => getenv('APP_ENV'),
            'version' => getenv('VERSION_HASH'),
            'flags' => $flags,
            // questionText
            'ques' => Countries::getName(strtoupper(array_keys($flags)[$number])),
            'answer' => $flags[array_keys($flags)[$number]],
            'answerCode' => array_keys($flags)[$number],
        ]);
    }

    #[Route('/correct/{flag}', name: 'submit_correct', methods: ['POST'])]
    public function correct(
        #[MapEntity(mapping: ['flag' => 'code'])] Flag $flag,
        EntityManagerInterface $entityManager,
    ): Response
    {
        $flag->incrementCorrectAnswersCounter();
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_OK);
    }

    #[Route('/api/login', name: 'telegramLogin', methods: ['POST'])]
    public function authAction(Request $request, JWTEncoderInterface $encoder): Response
    {
        $data = json_decode($request->getContent(), true);
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

        $user = $this->getDoctrine()->getRepository(User::class)->findOneByTelegramId($data['id']);

        if ($user === null) {
            $user = new User();
            $user->setTelegramId($data['id']);
            $user->setFirstName($data['first_name']);
            $user->setLastName($data['last_name']);
            $user->setTelegramUsername($data['username'] ?? null);
            $user->setTelegramPhotoUrl($data['photo_url'] ?? null);
            $this->getDoctrine()->getManager()->persist($user);
            $this->getDoctrine()->getManager()->flush();
        }

        $token = $encoder
            ->encode([
                'username' => $user->getTelegramId(),
                'exp' => time() + 6000000 + getenv('JWT_TOKEN_TTL')
            ]);

        return new JsonResponse(['token' => $token]);
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

    #[Route('/scores', name: 'submit_game_results', methods: ['POST'])]
    public function postScore(Request $request, EntityManagerInterface $entityManager, #[CurrentUser] User $user): Response
    {
        $requestArray = json_decode($request->getContent(), true);
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
    
    #[Route('/incorrect', name: 'incorrect', methods: ['GET', 'OPTIONS'])]
    public function getStat(#[CurrentUser] $user, AnswerRepository $repository): Response
    {
        $correctResults = $repository->findCorrectGuesses($user->getId());
        $result = $repository->findAllGuesses($user->getId());
        //TODO move this logic to service
        foreach ($result as $key => $item) {
            $result[$key]['flag'] = $this->flagsGenerator->getEmojiFlag($item['flagCode']);
            $result[$key]['country'] = Countries::getName(strtoupper($item['flagCode']));
            foreach ($correctResults as $value) {
                if ($value['flagCode'] === $result[$key]['flagCode']) {
                    $shown = (int) $result[$key]['times'];
                    $guessed = (int) $value['times'];
                    $res = $shown - $guessed;
                    $result[$key]['rate'] =  (int) (round($res / $shown, 2) * 100) ;
                    $result[$key]['times'] = $res."/$shown";   
                       
                    break;
                } 
            }
            
            if (!isset($result[$key]['rate'])) {
                $result[$key]['rate'] = 100;
                $result[$key]['times'] = $result[$key]['times'].'/'.$result[$key]['times'];
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
        //TODO move this logic to service
        foreach ($result as $key => $item) {
            $result[$key]['flag'] = $this->flagsGenerator->getEmojiFlag($item['flagCode']);
            $result[$key]['country'] = Countries::getName(strtoupper($item['flagCode']));
            foreach ($correctResults as $value) {
                if ($value['flagCode'] === $result[$key]['flagCode']) {
                    $shown =  (int) $result[$key]['times'];
                    $errors = (int) $value['times'];
                    $result[$key]['rate'] = (int) (round($errors/$shown, 2) * 100) ;
                    $result[$key]['times'] = "$errors/$shown";
                
                    break;
                }
            }
        
            if (!isset($result[$key]['rate'])) {
                $result[$key]['rate'] = 0;
                $result[$key]['times'] = "0/".$result[$key]['times'];
            }
        }
    
        array_multisort($result, SORT_DESC, SORT_NUMERIC, array_column($result, 'rate'), SORT_DESC, SORT_NUMERIC);
        
        return $this->json($result);
    }
}
