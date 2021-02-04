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
use Rteeom\FlagsGenerator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GameController extends AbstractController
{
    protected FlagsGenerator $flagsGenerator;

    public function __construct(
        protected ValidatorInterface $validator, 
        protected string $botToken,
    ) {
        $this->flagsGenerator = new FlagsGenerator();
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
            'flags' => $flags,
            // questionText
            'ques' => Countries::getName(strtoupper(array_keys($flags)[$number])),
            'answer' => $flags[array_keys($flags)[$number]],
            'answerCode' => array_keys($flags)[$number],
        ]);
    }

    /**
     * @Entity("flag", expr="repository.findOneByCode(flags)")
     */
    #[Route('/flags/correct/{flags}', name: 'submit_correct', methods: ['POST'])]
    public function correct(Flag $flag, EntityManagerInterface $entityManager): Response
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

    /** @Security("is_granted('ROLE_USER')") */
    #[Route('/protected', name: 'get_profile', methods: ['GET'])]
    public function getProfile(): Response
    {
        return $this->json($this->getUser());
    }

    #[Route('/flags/scores', name: 'get_high_scores', methods: ['GET'])]
    public function getHighScores(UserRepository $repository): Response
    {
        return $this->json($repository->getHighScores());
    }

    /** @Security("is_granted('ROLE_USER')") */
    #[Route('/flags/scores', name: 'submit_game_results', methods: ['POST'])]
    public function postScore(Request $request, EntityManagerInterface $entityManager, #[CurrentUser] $user): Response
    {
        $requestArray = json_decode($request->getContent(), true);
        $scoreDTO = new ScoreDTO($requestArray);
        $score = (new Score())->fromDTO($scoreDTO);
    
        $answers = [];
        if (isset($requestArray['answers'])) {
            foreach ($requestArray['answers'] as $answer) {
                $item = (new Answer())->fromArray($answer);
                $answers[] = $item;
            }    
        }

        /** @var User $user */
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

    #[Route('/token', name: 'test_token', methods: ['GET'])]
    public function getToken(JWTEncoderInterface $encoder, UserRepository $repository): Response
    {
        $user = $repository->getAnyUser();
        $token = $encoder
            ->encode([
                'username' => $user->getTelegramId(),
                'exp' => time() + 36000
            ]);
    
        return $this->json(['token' => $token]);
    }
    
    /** @Security("is_granted('ROLE_USER')") */
    #[Route('/incorrect', name: 'incorrect', methods: ['GET'])]
    public function getStat(#[CurrentUser] $user, AnswerRepository $repository): Response
    {
        $correctResults = $repository->findCorrectGuesses($user->getId());
        $incorrectResults = $repository->findIncorrectGuesses($user->getId());
        $result = $incorrectResults;
        //TODO move this logic to service
        foreach ($result as $key => $item) {
            $result[$key]['flag'] = $this->flagsGenerator->getEmojiFlag($item['flagCode']);
            $result[$key]['country'] = Countries::getName(strtoupper($item['flagCode']));
            foreach ($correctResults as $value) {
                if ($value['flagCode'] === $result[$key]['flagCode']) {
                    $shown =  (int) $value['times'] + (int)$result[$key]['times'];
                    $guessed = $value['times'];
                    $result[$key]['rate'] = round(($shown - $guessed) / $shown, 2) * 100 ;
                    $result[$key]['times'] = ($shown - $guessed)."/$shown" ;   
                       
                    break;
                } 
            }
            
            if (!isset($result[$key]['rate'])) {
                $result[$key]['rate'] = 100;
                $result[$key]['times'] = round((int) $result[$key]['times'].'/'.(int) $result[$key]['times']);
            }
        }
        
        array_multisort($result, SORT_DESC, SORT_NUMERIC, array_column($result, 'rate'), SORT_DESC, SORT_NUMERIC);

        return $this->json($result);
    }

    /** @Security("is_granted('ROLE_USER')") */
    #[Route('/correct', name: 'correct', methods: ['GET'])]
    public function getRight(#[CurrentUser] $user, AnswerRepository $repository): Response
    {
        $correctResults = $repository->findCorrectGuesses($user->getId());
        $incorrectResults = $repository->findIncorrectGuesses($user->getId());
        $result = $incorrectResults;
        //TODO move this logic to service + remove duplication
        foreach ($result as $key => $item) {
            $result[$key]['flag'] = $this->flagsGenerator->getEmojiFlag($item['flagCode']);
            $result[$key]['country'] = Countries::getName(strtoupper($item['flagCode']));
            foreach ($correctResults as $value) {
                if ($value['flagCode'] === $result[$key]['flagCode']) {
                    $shown =  (int) $value['times'] + (int)$result[$key]['times'];
                    $guessed = $value['times'];
                    $result[$key]['rate'] = round($guessed / $shown, 2) * 100 ;
                    $result[$key]['times'] = "$guessed/$shown" ;
                
                    break;
                }
            }
        
            if (!isset($result[$key]['rate'])) {
                $result[$key]['rate'] = 0;
                $result[$key]['times'] = round((int) $result[$key]['times'].'/'.(int) $result[$key]['times']);
            }
        }
    
        array_multisort($result, SORT_DESC, SORT_NUMERIC, array_column($result, 'rate'), SORT_DESC, SORT_NUMERIC);
        
        return $this->json($result);
    }
}
