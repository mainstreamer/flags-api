<?php

namespace App\Command;

use Rteeom\FlagsGenerator\FlagsGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Intl;

#[AsCommand(
    name: 'game:flags',
    description: 'guess flag console game',
)]
class GameFlagsCommand extends Command
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
        $output->setDecorated(true); // Ensures proper emoji and color support


        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        if ($input->getOption('option1')) {
            // ...
        }

        $choices = [];
        for ($total = count($this->isoCodes), $max = 4, $i = 0; $i < $max; ++$i) {
            $choices[] = strtolower($this->isoCodes[rand(0, $total)]);
        }

        $correctIndex = rand(0, 3);

        dump(Countries::getName('uk'));

        $options = array_map(fn (string $code) => $this->flagsGenerator->getEmojiFlagOrNull($code), $choices);

        $choice = $io->choice(
            'Select the flag of ' . $choices[$correctIndex],
            $options,
        );

        if ($choice === $this->flagsGenerator->getEmojiFlagOrNull($choices[$correctIndex])) {
            $io->success('Yes! :]');
        } else {
            $io->warning('No :[');
        }

        return Command::SUCCESS;
    }
}
