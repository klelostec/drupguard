<?php

namespace App\Command;

use App\Entity\AnalyseQueue;
use App\Entity\Project;
use Cron\CronExpression;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class DrupGardRun extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'drupguard:run';

    protected $entityManager;

    protected $projectDir;

    public function __construct(EntityManagerInterface $entityManager, KernelInterface $kernel)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->projectDir = $kernel->getProjectDir();
    }

    protected function configure()
    {
        $this
          ->setDescription('Run projects analyses.')
          ->setHelp('This command allows you to run all projects analyses. If cron is enable for project, check frequency to run or not.')
          ->addArgument('projects', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Projects\'s machine names.')
          ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force run analyse, work only on cron project')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repo = $this->entityManager->getRepository("App:Project");

        $projectsMachineNames = $input->getArgument('projects');
        if (count($projectsMachineNames) > 0) {
            $force = $input->getOption('force');
            foreach ($projectsMachineNames as $machineName) {
                $project = $repo->findOneBy(['machineName' => $machineName]);
                if(!$project) {
                    $output->writeln('<error>Project "' . $machineName .'" not found.</error>');
                    continue;
                }

                if($project->isPending()) {
                    $output->writeln('<comment>Project "'.$project->getMachineName().'"\'s analyse is already pending.</comment>');
                    continue;
                }

                if ($this->isRunning($project)) {
                    $output->writeln('<comment>Project "'.$project->getMachineName().'"\'s analyse is running.</comment>');
                    continue;
                }

                if ($this->needRunAnalyse($project) || boolval($force)) {
                    $queue = new AnalyseQueue();
                    $queue->addProject($project);

                    $this->entityManager->persist($queue);
                    $this->entityManager->flush();
                    $output->writeln('<info>Project "' . $machineName .'" add to queue.</info>');
                }
            }
        }

        return Command::SUCCESS;
    }

    protected function needRunAnalyse(Project $project): bool
    {
        if (!$project->hasCron() || (!$project->getLastAnalyse())) {
            return true;
        }
        $currentDate = new \DateTime();
        $cronHelper = new CronExpression($project->getCronFrequency());

        return $cronHelper->getNextRunDate(
            $project->getLastAnalyse()->getDate()
          ) <= $currentDate;
    }

    protected function isRunning(Project $project): ?bool
    {
        return $project->getLastAnalyse() && $project->getLastAnalyse()
            ->isRunning();
    }
}
