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
            ->addArgument('telegramId', InputArgument::OPTIONAL, 'Telegram ID for single user creation', '0')
            ->addOption('username', 'u', InputOption::VALUE_OPTIONAL, 'Telegram username')
            ->addOption('first-name', 'f', InputOption::VALUE_OPTIONAL, 'First name')
            ->addOption('last-name', 'l', InputOption::VALUE_OPTIONAL, 'Last name')
            ->addOption('json', 'j', InputOption::VALUE_REQUIRED, 'Path to JSON file for batch creation')
            ->addOption('skip-existing', null, InputOption::VALUE_NONE, 'Skip users that already exist (by telegramId)')
            ->setHelp(<<<'HELP'
Create users individually or in batch from a JSON file.

<info>Single user:</info>
  bin/console app:populate:users 123456 -u johndoe -f John -l Doe

<info>Batch from JSON:</info>
  bin/console app:populate:users --json users.json --skip-existing

<info>JSON file format:</info>
  [
    {"telegramId": "123456", "telegramUsername": "johndoe", "firstName": "John", "lastName": "Doe"},
    {"telegramId": "789012", "firstName": "Jane"}
  ]

  Or with wrapper:
  {"users": [...]}

<info>Available fields:</info>
  telegramId (required), telegramUsername, firstName, lastName, telegramPhotoUrl, sub
HELP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $jsonPath = $input->getOption('json');

        if ($jsonPath !== null) {
            return $this->createFromJson($jsonPath, $input->getOption('skip-existing'), $io);
        }

        return $this->createSingleUser($input, $io);
    }

    private function createSingleUser(InputInterface $input, SymfonyStyle $io): int
    {
        $telegramId = $input->getArgument('telegramId');

        $existing = $this->userRepository->findOneBy(['telegramId' => $telegramId]);
        if ($existing !== null) {
            $io->error(sprintf('User with telegramId "%s" already exists (id: %d).', $telegramId, $existing->getId()));
            return Command::FAILURE;
        }

        $user = $this->buildUser([
            'telegramId' => $telegramId,
            'telegramUsername' => $input->getOption('username'),
            'firstName' => $input->getOption('first-name'),
            'lastName' => $input->getOption('last-name'),
        ]);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Created user: telegramId=%s, id=%d', $telegramId, $user->getId()));

        return Command::SUCCESS;
    }

    private function createFromJson(string $jsonPath, bool $skipExisting, SymfonyStyle $io): int
    {
        if (!file_exists($jsonPath)) {
            $io->error(sprintf('JSON file not found: %s', $jsonPath));
            return Command::FAILURE;
        }

        $content = file_get_contents($jsonPath);
        if ($content === false) {
            $io->error(sprintf('Could not read file: %s', $jsonPath));
            return Command::FAILURE;
        }

        $data = json_decode($content, true);
        if ($data === null) {
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
            if (!isset($userData['telegramId'])) {
                $io->warning(sprintf('Entry %d missing telegramId, skipped.', $index));
                $errorCount++;
                continue;
            }

            $telegramId = (string) $userData['telegramId'];
            $existing = $this->userRepository->findOneBy(['telegramId' => $telegramId]);

            if ($existing !== null) {
                if ($skipExisting) {
                    $skippedCount++;
                    continue;
                }
                $io->warning(sprintf('User telegramId=%s already exists, skipped.', $telegramId));
                $skippedCount++;
                continue;
            }

            $user = $this->buildUser($userData);
            $this->entityManager->persist($user);
            $createdCount++;
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
        $user->setTelegramId((string) $data['telegramId']);

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
        if (!empty($data['sub'])) {
            $user->setSub($data['sub']);
        }

        return $user;
    }
}
