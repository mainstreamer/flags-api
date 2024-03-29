<?php

namespace App\Flags\ConsoleCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SetWebhookCommand extends Command
{
    /**
     * This needs to be added to services.yaml to inject arg:
     *   _defaults:
     *     bind:
     *       $botToken: '%env(string:BOT_TOKEN)%'
     */
    protected static $defaultName = 'app:set-webhook';
    
    protected HttpClientInterface $client;
    
    protected string $botToken;
    
    public function __construct(HttpClientInterface $client, string $botToken)
    {
        parent::__construct();
        $this->client = $client;
        $this->botToken = $botToken;
    }
    
    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');
//        $r = $this->client->request(Request::METHOD_GET, 'https://api.telegram.org/bot' . $this->botToken . '/setWebhook?url=' . $arg1);
        $r = $this->client->request(Request::METHOD_GET, 'https://api.telegram.org/bot' . $this->botToken . '/setWebhook?url=' . $arg1);
        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }
        $io->write($r->getContent());
        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');
        
        return 0;
    }
}
