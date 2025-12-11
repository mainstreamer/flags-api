<?php

namespace App\Flags\ConsoleCommand;

use App\Flags\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GetTokenCommand extends Command
{
    /**
     * This needs to be added to services.yaml to inject arg:
     *   _defaults:
     *     bind:
     *       $botToken: '%env(string:BOT_TOKEN)%'
     */
    protected static $defaultName = 'dev:token';
    
    public function __construct(
        private readonly JWTEncoderInterface $encoder,
        private readonly HttpClientInterface $client,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $botToken
    )
    {
        parent::__construct();
    }
    
    protected function configure()
    {
        $this
            ->setDescription('Get access token')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repo = $this->entityManager->getRepository(User::class);
        $user = $repo->findOneByTelegramUsername('rteeom');

        $token = $this->encoder
            ->encode([
                'username' => $user->getTelegramId(),
                'exp' => time() + 600000 + getenv('JWT_TOKEN_TTL')
            ]);

        $output->writeln(sprintf('<info>%s</info>', $token));

        return Command::SUCCESS;
    }
}
