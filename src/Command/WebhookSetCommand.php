<?php

namespace App\Command;

use App\Service\TelegramService;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WebhookSetCommand extends Command
{
    protected static $defaultName = 'app:webhook-set';
    private TelegramService $manager;

    public function __construct(TelegramService $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->manager->setWebhook();
            $output->writeln("OK");
            return 0;
        } catch (Exception $e) {
            $message = $e->getMessage();
            $output->writeln($message);
            return 1;
        }
    }
}
