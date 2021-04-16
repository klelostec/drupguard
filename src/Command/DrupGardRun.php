<?php

namespace App\Command;

use App\Exception\AnalyseException;
use App\Service\AnalyseHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
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

    protected $analyseHelper;

    public function __construct(EntityManagerInterface $entityManager, KernelInterface $kernel, AnalyseHelper $analyseHelper)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->projectDir = $kernel->getProjectDir();
        $this->analyseHelper = $analyseHelper;
    }

    protected function configure()
    {
        $this
          ->setDescription('Run projects analyses.')
          ->setHelp('This command allows you to run all projects analyses. If cron is enable for project, check frequency to run or not.')
          ->addArgument('project', InputArgument::REQUIRED, 'Project machine name.')
          ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force run analyse, work only on cron project')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repo = $this->entityManager->getRepository("App:Project");
        $machineName = $input->getArgument('project');
        $project = $repo->findOneBy(['machineName' => $machineName]);
        if(!$project) {
            $output->writeln('<error>Project "' . $machineName .'" not found.</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Project "' . $machineName .'"\'s analyse start.</info>');
        try {
            $this->analyseHelper->start($project, boolval($input->getOption('force')));
        }
        catch (AnalyseException $e) {
            switch ($e->getCode()) {
                case AnalyseException::WARNING:
                    $output->writeln('<warning>' . $e->getMessage() . '</warning>');
                    return Command::SUCCESS;
                default:
                    $output->writeln('<error>' . $e->getMessage() . '</error>');
                    return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
