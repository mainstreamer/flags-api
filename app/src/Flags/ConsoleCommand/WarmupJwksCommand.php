<?php

declare(strict_types=1);

namespace App\Flags\ConsoleCommand;

use App\Flags\Service\JwksService;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @psalm-suppress UnusedClass
 */
#[AsCommand(
    name: 'app:jwks:warmup',
    description: 'Fetch and cache the public key from JWKS endpoint',
)]
final class WarmupJwksCommand extends Command
{
    public function __construct(
        private readonly JwksService $jwksService,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Fetching public key from JWKS endpoint...');

        $publicKey = $this->jwksService->refreshPublicKey();

        if ($publicKey) {
            $io->success('Public key fetched and cached successfully.');

            return Command::SUCCESS;
        }

        $io->error('Failed to fetch public key from JWKS endpoint.');

        return Command::FAILURE;
    }
}
