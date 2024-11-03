<?php

namespace App\Command;

use App\Flags\Entity\Capital;
use Rteeom\FlagsGenerator\FlagsGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Intl\Countries;

#[AsCommand(
    name: 'game:capitals',
    description: 'guess capital console game',
)]
class GameCapitalsCommand extends Command
{
    private FlagsGenerator $flagsGenerator;
    private array $isoCodes;

    public function __construct()
    {
        parent::__construct();
        $this->flagsGenerator = new FlagsGenerator();
        $this->isoCodes = FlagsGenerator::getAvailableCodes();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $db = $this->load();
        $excluded = ['UM', 'AQ', 'TF', 'HM', 'SH', 'RU', 'CX', 'SJ'];
        $lives = 3;
        while ($lives > 0) {
            $choices = [];
            for ($total = count($this->isoCodes), $max = 10, $i = 0; $i < $max; ++$i) {
                if ($randomChoice = $this->isoCodes[rand(0, $total - 1)]) {
                    if (in_array($randomChoice, $excluded) || in_array($randomChoice, $choices)) {
                        --$i;
                        continue;
                    }
                    $choices[] = $randomChoice;
                }
            }

            $correctIndex = rand(0, 3);
            $options = array_map(fn (string $code) => $db[$code]->getName(), $choices);

            $io->text(sprintf('Lives: %d', $lives));
            $choice = $io->choice(
                'Select the capital of ' . Countries::getName(strtoupper($choices[$correctIndex])) . " " . $this->flagsGenerator->getEmojiFlagOrNull(strtolower($choices[$correctIndex])) . " ",
                $options,
            );

            if ($choice === $db[$choices[$correctIndex]]->getName()) {
                $io->success('Yes! :]');
            } else {
                $io->warning('No :[');
                --$lives;
            }
        }


        return Command::SUCCESS;
    }

    private const COUNTRY_FILES = [
        'capitals-africa.json',
        'capitals-americas.json',
        'capitals-asia.json',
        'capitals-europe.json',
        'capitals-oceania.json',
    ];

    public function load(): array
    {
        $result = [];
        foreach (self::COUNTRY_FILES as $fileName) {
            $result = array_merge($result, $this->loadFileContent($fileName));
        }

        return $result;
    }

    private function loadFileContent(string $fileName): array
    {
        if (file_exists($fileName)) {
            ['countries' => $countries] = json_decode(file_get_contents($fileName), true);
        }

        $capitals = [];
        foreach ($countries ?? [] as $country) {
            $capitals[$country['isoCode']] = new Capital($country['capital'], $country['name'], $country['isoCode'], $country['region']);
        }

        return $capitals;
    }
}
