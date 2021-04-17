<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DrupGardCron extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'drupguard:cron';

    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
          ->setDescription('Run projects analyses.')
          ->setHelp('This command allows you to run all projects analyses. If cron is enable for project, check frequency to run or not.')
          ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force run analyse, work only on cron project')
          ->addOption('cron-only', 'co', InputOption::VALUE_NONE, 'Run only project which has cron setting')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repo = $this->entityManager->getRepository("App:Project");
        $projects = $repo->findByCronNeeded(boolval($input->getOption('cron-only')));
        foreach($projects as $project) {
            $command = $this->getApplication()->find('drupguard:run');
            $arguments = [
              'project' => $project->getMachineName(),
              '--force' => $input->getOption('force'),
            ];

            $input = new ArrayInput($arguments);
            $command->run($input, $output);
        }
        return Command::SUCCESS;
    }
}
