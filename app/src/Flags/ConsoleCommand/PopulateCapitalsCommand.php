<?php

declare(strict_types=1);

namespace App\Flags\ConsoleCommand;

use App\Flags\Entity\Capital;
use App\Flags\Repository\CapitalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:populate:capitals',
    description: 'Populate the capitals table from JSON data files',
)]
class PopulateCapitalsCommand extends Command
{
    private const array COUNTRY_FILES = [
        'capitals-africa.json',
        'capitals-americas.json',
        'capitals-asia.json',
        'capitals-europe.json',
        'capitals-oceania.json',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CapitalRepository $capitalRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Purge existing capitals before populating')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('purge')) {
            $this->purgeExistingCapitals($io);
        }

        $totalCount = 0;

        foreach (self::COUNTRY_FILES as $fileName) {
            $count = $this->loadFileContent($fileName, $io);
            $totalCount += $count;
        }

        $io->success(sprintf('Successfully populated %d capitals.', $totalCount));

        return Command::SUCCESS;
    }

    private function purgeExistingCapitals(SymfonyStyle $io): void
    {
        $existingCount = $this->capitalRepository->count([]);

        if ($existingCount > 0) {
            $this->entityManager->createQuery('DELETE FROM App\Flags\Entity\Capital')->execute();
            $io->warning(sprintf('Purged %d existing capitals.', $existingCount));
        }
    }

    private function loadFileContent(string $fileName, SymfonyStyle $io): int
    {
        if (!file_exists($fileName)) {
            $io->error(sprintf('File not found: %s', $fileName));
            return 0;
        }

        $content = file_get_contents($fileName);
        if ($content === false) {
            $io->error(sprintf('Could not read file: %s', $fileName));
            return 0;
        }

        $data = json_decode($content, true);
        if (!isset($data['countries']) || !is_array($data['countries'])) {
            $io->error(sprintf('Invalid JSON structure in: %s', $fileName));
            return 0;
        }

        $count = 0;
        foreach ($data['countries'] as $country) {
            $capital = new Capital(
                $country['capital'],
                $country['name'],
                $country['isoCode'],
                $country['region']
            );
            $this->entityManager->persist($capital);
            $count++;
        }

        $this->entityManager->flush();
        $io->info(sprintf('Loaded %d capitals from %s', $count, $fileName));

        return $count;
    }
}
