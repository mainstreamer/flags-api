<?php

namespace App\Controller;

use App\DTO\ScoreDTO;
use App\Entity\Answer;
use App\Entity\Flag;
use App\Entity\Score;
use App\Entity\User;
use App\Repository\UserRepository;
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

class TestController extends AbstractController
{
    /**
     * @Route("/test", name="test")
     */
    public function index()
    {
        for ($flags = [];;) {
            try {
                $flag = (new FlagsGenerator())->getEmojiFlag($code = chr(rand(97,122)).chr(rand(97,122)));
            } catch (\Throwable $e) {
                $flag = '❌';
            }

            if ('❌' !== $flag
                // = (new FlagsGenerator())->getEmojiFlag($code = chr(rand(97,122)).chr(rand(97,122)))
            ) {
                $flags[$code] = $flag;
            }

            if (count($flags) === 4) {
                break;
            }
        }

        $repo = $this->getDoctrine()->getManager()->getRepository(Flag::class);

        foreach ($flags as $code => $flag) {
            $item = $repo->findOneByCode($code);
            if (!$item) {
                $item = new Flag();
                $item->setCode($code);
                $this->getDoctrine()->getManager()->persist($item);
                $this->getDoctrine()->getManager()->flush();
            }
        }

        $number = rand(0,3);
        $answerCode = array_keys($flags)[$number];
        $item = $repo->findOneByCode($answerCode);
        $item->setShows($item->getShows()+1);
        $this->getDoctrine()->getManager()->flush();
        

        return $this->json([
//            'message' => (new \DateTime())->format('d-m-Y h:i:s'),
            'flags' => $flags,
            'ques' => Countries::getName(strtoupper(array_keys($flags)[$number])),
            'answer' => $flags[array_keys($flags)[$number]],
            'answerCode' => array_keys($flags)[$number],
            'highScores' => $this->getDoctrine()->getManager()->getRepository(User::class)->getHighScores()
        ]);
    }

    /**
     * @param Flag $flag
     * @return Response
     * @Route("/flags/correct/{flags}", name="correct", methods={"POST"})
     * @Entity("flag", expr="repository.findOneByCode(flags)")
     */

//* @Security("is_granted('ROLE_USER')")
    public function correct(Flag $flag): Response
    {
        $flag->setCorrectGuesses($flag->getCorrectGuesses()+1);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(null, Response::HTTP_OK);
    }

    /**
     * @Route("/api/login", name="login", methods={"POST"})
     */
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
        $bot_token = '890560780:AAHeK8h5BQntCH_9j4bIcnsqsThXDLxOrRs';
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
     * @return Response
     * @Route("/protected", name="protected", methods={"GET"}, defaults={"_format": "json"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function protected(): Response
    {
        return $this->json($this->getUser());
    }
    
    /**
     * @return Response
     * @Route("/flags/scores", name="get scores", methods={"GET"})
     */
    public function getHighScores(): Response
    {
        return $this->json($this->getDoctrine()->getManager()->getRepository(User::class)->getHighScores());
    }
    
    /**
     * @return Response
     * @Route("/flags/scores", name="update scores", methods={"POST"})
     * @Entity("score")
     */
    //* @Security("is_granted('ROLE_USER')")
    public function postScore(Request $request): Response
    {
        $requestArray = json_decode($request->getContent(), true);
        $scoreDTO = new ScoreDTO($requestArray);
        $score = (new Score())->fromDTO($scoreDTO);
    
        $answers = [];
        foreach ($requestArray['answers'] as $answer) {
            $item = (new Answer())->fromArray($answer);
            $answers[] = $item;
        } 
        
//        return new Response($request->getContent(), 200);
        /** @var User $user */
        $user = $this->getUser();
        $user->finalizeGame($score, $answers);
        $this->getDoctrine()->getManager()->flush();
        
        return new Response(null, Response::HTTP_OK);
    }
    
    /**
     * @Route("/test/{flag}", name="test-flag", methods={"GET"})
     */
    public function testFlags(string $flag)
    {
        try {
            $flag = (new FlagsGenerator())->getEmojiFlag($flag);    
        } catch (\Throwable $e) {
            $flag = 'invalid code';
        }
        
        return new Response($flag);
    }
}
