<?php

declare(strict_types=1);

namespace App\Flags\ConsoleCommand;

use App\Flags\Entity\User;
use App\Flags\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:populate:users',
    description: 'Create users - single or batch from JSON file',
)]
class PopulateUsersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('sub', InputArgument::OPTIONAL, 'User subject identifier (sub) for single user creation')
            ->addOption('username', 'u', InputOption::VALUE_OPTIONAL, 'Telegram username')
            ->addOption('first-name', 'f', InputOption::VALUE_OPTIONAL, 'First name')
            ->addOption('last-name', 'l', InputOption::VALUE_OPTIONAL, 'Last name')
            ->addOption('json', 'j', InputOption::VALUE_REQUIRED, 'Path to JSON file for batch creation')
            ->addOption('skip-existing', null, InputOption::VALUE_NONE, 'Skip users that already exist (by sub)')
            ->setHelp(<<<'HELP'
Create users individually or in batch from a JSON file.

<info>Single user:</info>
  bin/console app:populate:users user123 -u johndoe -f John -l Doe

<info>Batch from JSON:</info>
  bin/console app:populate:users --json users.json --skip-existing

<info>JSON file format:</info>
  [
    {"sub": "user123", "telegramUsername": "johndoe", "firstName": "John", "lastName": "Doe"},
    {"sub": "user456", "firstName": "Jane"}
  ]

  Or with wrapper:
  {"users": [...]}

<info>Available fields:</info>
  sub (required), telegramUsername, firstName, lastName, telegramPhotoUrl
HELP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $jsonPath = $input->getOption('json');

        if (null !== $jsonPath) {
            return $this->createFromJson($jsonPath, $input->getOption('skip-existing'), $io);
        }

        return $this->createSingleUser($input, $io);
    }

    private function createSingleUser(InputInterface $input, SymfonyStyle $io): int
    {
        $sub = $input->getArgument('sub');

        if (empty($sub)) {
            $io->error('User subject identifier (sub) is required for single user creation.');

            return Command::FAILURE;
        }

        $existing = $this->userRepository->findOneBy(['sub' => $sub]);
        if (null !== $existing) {
            $io->warning(sprintf('User with sub "%s" already exists (id: %d). Skipping.', $sub, $existing->getId()));

            return Command::SUCCESS;
        }

        $user = $this->buildUser([
            'sub' => $sub,
            'telegramUsername' => $input->getOption('username'),
            'firstName' => $input->getOption('first-name'),
            'lastName' => $input->getOption('last-name'),
        ]);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Created user: sub=%s, id=%d', $sub, $user->getId()));

        return Command::SUCCESS;
    }

    private function createFromJson(string $jsonPath, bool $skipExisting, SymfonyStyle $io): int
    {
        if (!file_exists($jsonPath)) {
            $io->error(sprintf('JSON file not found: %s', $jsonPath));

            return Command::FAILURE;
        }

        $content = file_get_contents($jsonPath);
        if (false === $content) {
            $io->error(sprintf('Could not read file: %s', $jsonPath));

            return Command::FAILURE;
        }

        $data = json_decode($content, true);
        if (null === $data) {
            $io->error('Invalid JSON format.');

            return Command::FAILURE;
        }

        // Support both {"users": [...]} and plain [...]
        $users = $data['users'] ?? $data;

        if (!is_array($users)) {
            $io->error('JSON must be an array of users or {"users": [...]}');

            return Command::FAILURE;
        }

        $createdCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($users as $index => $userData) {
            if (!isset($userData['sub'])) {
                $io->warning(sprintf('Entry %d missing sub (subject identifier), skipped.', $index));
                ++$errorCount;
                continue;
            }

            $sub = (string) $userData['sub'];
            $existing = $this->userRepository->findOneBy(['sub' => $sub]);

            if (null !== $existing) {
                if ($skipExisting) {
                    ++$skippedCount;
                    continue;
                }
                $io->warning(sprintf('User sub=%s already exists, skipped.', $sub));
                ++$skippedCount;
                continue;
            }

            $user = $this->buildUser($userData);
            $this->entityManager->persist($user);
            ++$createdCount;
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Batch complete: %d created, %d skipped, %d errors.',
            $createdCount,
            $skippedCount,
            $errorCount
        ));

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildUser(array $data): User
    {
        $user = new User();
        $user->setSub((string) $data['sub']);

        if (!empty($data['telegramUsername'])) {
            $user->setTelegramUsername($data['telegramUsername']);
        }
        if (!empty($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (!empty($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }
        if (!empty($data['telegramPhotoUrl'])) {
            $user->setTelegramPhotoUrl($data['telegramPhotoUrl']);
        }

        return $user;
    }
}
