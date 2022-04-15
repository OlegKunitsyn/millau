<?php

namespace App\Command;

use App\Service\TelegramService;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WebhookGetCommand extends Command
{
    protected static $defaultName = 'app:webhook-get';
    private TelegramService $manager;

    public function __construct(TelegramService $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $webhook = $this->manager->getWebhook();
            $output->writeln($webhook);
            return 0;
        } catch (Exception $e) {
            $message = $e->getMessage();
            $output->writeln($message);
            return 1;
        }
    }
}
