<?php

namespace App\Flags\Controller;

use App\Flags\Entity\User;
use App\Flags\Service\CapitalsGameService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Rteeom\FlagsGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    #[Route('/capitals', name: 'check_cap', methods: ['GET'])]
    public function check(CapitalsGameService $service): JsonResponse
    {
        return $this->json($service->getQuestion());
    }

    #[Route('/capitals/{countryCode}/{answer}', name: 'answer_cap', methods: ['GET'])]
    public function answer(CapitalsGameService $service, string $countryCode, string $answer): JsonResponse
    {
        return $this->json($service->giveAnswer($countryCode, base64_decode($answer)));
    }

    #[Route('/capitals/test', name: 'test_cap', methods: ['GET'])]
    public function test(): JsonResponse
    {
        return $this->json($this->flagsGenerator->getEmojiFlagOrNull('ss'));
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
                'exp' => time() + 6000000 + getenv('JWT_TOKEN_TTL')
            ]);

        } catch (\Throwable $exception) {
            return new JsonResponse(['error' => $exception->getMessage()]);
        }

        return new JsonResponse(['token' => $token]);
    }
}
