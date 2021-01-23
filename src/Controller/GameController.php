<?php

namespace App\Controller;

use App\DTO\ScoreDTO;
use App\Entity\Answer;
use App\Entity\Flag;
use App\Entity\Score;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\LazyCriteriaCollection;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class GameController extends AbstractController
{
    public function __construct(
        protected ValidatorInterface $validator, 
        protected string $botToken,
    ) {}

    #[Route('/test', name: 'test', methods: ['GET'])]
    public function getQuestion(): JsonResponse
    {
        $flagsGenerator = new FlagsGenerator();
        $flags = [];

        while (count($flags) < 4) {
            $countryCode = chr(rand(97,122)).chr(rand(97,122));
            $flag = $flagsGenerator->getEmojiFlagOrNull($countryCode);
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
     * @Security("is_granted('ROLE_USER')")
     */
    #[Route('/flags/correct/{flags}', name: 'submit_correct', methods: ['POST'])]
    public function correct(Flag $flag): Response
    {
        $flag->setCorrectGuesses($flag->getCorrectGuesses() + 1);
        $this->getDoctrine()->getManager()->flush();

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
            $user->setTelegramUsername($data['username']);
            $user->setTelegramPhotoUrl($data['photo_url']);
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

    /**
     * @Security("is_granted('ROLE_USER')")
     */
    #[Route('/protected', name: 'get_profile', methods: ['GET'])]
    public function getProfile(): Response
    {
        return $this->json($this->getUser());
    }

    #[Route('/flags/scores', name: 'get_high_scores', methods: ['GET'])]
    public function getHighScores(): Response
    {
        return $this->json($this->getDoctrine()->getManager()->getRepository(User::class)->getHighScores());
    }

    /**
     * @Entity("score")
     * @Security("is_granted('ROLE_USER')") 
     */
    #[Route('/flags/scores', name: 'submit_game_results', methods: ['POST'])]
    public function postScore(Request $request): Response
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
        $user = $this->getUser();
        $user->finalizeGame($score, $answers);
        $this->getDoctrine()->getManager()->flush();
        
        return new Response(null, Response::HTTP_OK);
    }

    #[Route('/test/{flag}', name: 'test_flag', methods: ['GET'])]
    public function getEmoji(string $flag): Response
    {
        $flag = (new FlagsGenerator())->getEmojiFlagOrNull($flag) ?? 'invalid code';     

        return new Response($flag);
    }

    #[Route('/token', name: 'test_token', methods: ['GET'])]
    public function getToken(JWTEncoderInterface $encoder): Response
    {
        $user = $this->getDoctrine()->getRepository(User::class)->matching( 
             ($criteria = new Criteria())->where($criteria->expr()->gt('id', 0))->setMaxResults(1)
        )->get(0);
        
        $token = $encoder
            ->encode([
                'username' => $user->getTelegramId(),
                'exp' => time() + 36000
            ]);
    
        return new JsonResponse(['token' => $token]);
    }
    
    /** @Security("is_granted('ROLE_USER')") */
    #[Route('/incorrect', name: 'incorrect', methods: ['GET'])]
    public function getStat(): Response
    {
        $user = $this->getUser();
        $result = $this->getDoctrine()->getRepository(Answer::class)->findIncorrectGuesses($user->getId());
        
        foreach ($result as $key => $item) {
            $result[$key]['flag'] = $flag = (new FlagsGenerator())->getEmojiFlag($item['flagCode']);
            $result[$key]['country'] = Countries::getName(strtoupper($item['flagCode']));
        }
        
        return new JsonResponse($result);
    }
    
    /** @Security("is_granted('ROLE_USER')") */
    #[Route('/correct', name: 'correct', methods: ['GET'])]
    public function getRight(): Response
    {
        $user = $this->getUser();
        $result = $this->getDoctrine()->getRepository(Answer::class)->findCorrectGuesses($user->getId());
        
        foreach ($result as $key => $item) {
            $result[$key]['flag'] = $flag = (new FlagsGenerator())->getEmojiFlag($item['flagCode']);
            $result[$key]['country'] = Countries::getName(strtoupper($item['flagCode']));
        }

        return new JsonResponse($result);
    }
}
