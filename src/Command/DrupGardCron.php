<?php

namespace App\Command;

use App\Exception\AnalyseException;
use App\Service\AnalyseHelper;
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

    protected $analyseHelper;

    public function __construct(EntityManagerInterface $entityManager, AnalyseHelper $analyseHelper)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->analyseHelper = $analyseHelper;
    }

    protected function configure()
    {
        $this
          ->setDescription('Run projects analyses.')
          ->setHelp('This command allows you to run all projects analyses. If cron is enable for project, check frequency to run or not.')
          ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force run analyse, work only on cron project')
          ->addOption('cron-only', 'c', InputOption::VALUE_NONE, 'Run only project which has cron setting')
          ->addOption('queue-items', 'i', InputOption::VALUE_OPTIONAL, 'Number of queue items to process', 10)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //Add needed project to queue
        $repo = $this->entityManager->getRepository("App:Project");
        $projects = $repo->findByCronNeeded(boolval($input->getOption('cron-only')));
        foreach($projects as $project) {
            $command = $this->getApplication()->find('drupguard:run');
            $arguments = [
              'projects' => [$project->getMachineName()],
              '--force' => $input->getOption('force'),
            ];

            $inputCommand = new ArrayInput($arguments);
            $command->run($inputCommand, $output);
        }

        $nbITems = intval($input->getOption('queue-items'));
        $projectQueues = $repo->findByQueue($nbITems);
        foreach($projectQueues as $project) {
            try {
                $queue = $project->getAnalyseQueue();
                $this->entityManager->remove($queue);
                $project->setAnalyseQueue(null);
                $this->entityManager->flush();
                $output->writeln('<info>Project "' . $project->getMachineName() .'"\'s analyse start.</info>');
                $this->analyseHelper->start($project);
                $output->writeln('<info>Project "' . $project->getMachineName() .'"\'s analyse end.</info>');
            }
            catch (AnalyseException $e) {
                switch ($e->getCode()) {
                    case AnalyseException::WARNING:
                        $output->writeln('<comment>' . $e->getMessage() . '</comment>');
                    default:
                        $output->writeln('<error>' . $e->getMessage() . '</error>');
                }
            }
        }

        return Command::SUCCESS;
    }
}
