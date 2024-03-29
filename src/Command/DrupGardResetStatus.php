<?php

namespace App\Command;

use App\Entity\Project;
use App\Service\AnalyseHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class DrupGardResetStatus extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'drupguard:reset-status';

    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager, KernelInterface $kernel, AnalyseHelper $analyseHelper)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
          ->setDescription('Reset status for frozen projects analyses.')
          ->setHelp('This command allows you to reset status for frozen projects analyses.')
          ->addArgument('projects', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Projects\'s machine names.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repo = $this->entityManager->getRepository(Project::class);

        $projectsMachineNames = $input->getArgument('projects');
        if (count($projectsMachineNames) > 0) {
            foreach ($projectsMachineNames as $machineName) {
                $project = $repo->findOneBy(['machineName' => $machineName]);
                if (!$project) {
                    $output->writeln('<error>Project "' . $machineName .'" not found.</error>');
                    return Command::FAILURE;
                }

                $analyse = $project->getLastAnalyse();
                $analyse->setIsRunning(false);
                $this->entityManager->flush();

                $output->writeln('<info>Project "' . $machineName .'"\'s status reset.</info>');
            }
        }

        return Command::SUCCESS;
    }
}
