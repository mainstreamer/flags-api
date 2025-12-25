<?php

declare(strict_types=1);

namespace App\Flags\ConsoleCommand;

use App\Flags\Entity\Flag;
use App\Flags\Repository\FlagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:populate:flags',
    description: 'Populate the flags table from JSON data files (uses capitals JSON for ISO codes)',
)]
class PopulateFlagsCommand extends Command
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
        private readonly FlagRepository $flagRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Purge existing flags before populating')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('purge')) {
            $this->purgeExistingFlags($io);
        }

        $codes = $this->collectAllCodes($io);

        if (empty($codes)) {
            $io->error('No country codes found in JSON files.');
            return Command::FAILURE;
        }

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($codes as $code) {
            $existing = $this->flagRepository->findOneBy(['code' => $code]);

            if ($existing !== null) {
                $skippedCount++;
                continue;
            }

            $flag = new Flag();
            $flag->setCode($code);
            $this->entityManager->persist($flag);
            $createdCount++;
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Flags populated: %d created, %d skipped (already exist).',
            $createdCount,
            $skippedCount
        ));

        return Command::SUCCESS;
    }

    private function purgeExistingFlags(SymfonyStyle $io): void
    {
        $existingCount = $this->flagRepository->count([]);

        if ($existingCount > 0) {
            $this->entityManager->createQuery('DELETE FROM App\Flags\Entity\Flag')->execute();
            $io->warning(sprintf('Purged %d existing flags.', $existingCount));
        }
    }

    /**
     * @return string[]
     */
    private function collectAllCodes(SymfonyStyle $io): array
    {
        $codes = [];

        foreach (self::COUNTRY_FILES as $fileName) {
            if (!file_exists($fileName)) {
                $io->warning(sprintf('File not found: %s', $fileName));
                continue;
            }

            $content = file_get_contents($fileName);
            if ($content === false) {
                $io->warning(sprintf('Could not read file: %s', $fileName));
                continue;
            }

            $data = json_decode($content, true);
            if (!isset($data['countries']) || !is_array($data['countries'])) {
                $io->warning(sprintf('Invalid JSON structure in: %s', $fileName));
                continue;
            }

            foreach ($data['countries'] as $country) {
                if (isset($country['isoCode'])) {
                    $codes[] = strtolower($country['isoCode']);
                }
            }

            $io->info(sprintf('Read %d codes from %s', count($data['countries']), $fileName));
        }

        return array_unique($codes);
    }
}
