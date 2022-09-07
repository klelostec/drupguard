<?php

namespace App\Command;

use App\Entity\Project;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DrupGardList extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'drupguard:list';

    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
          ->setDescription('List cron available.')
          ->setHelp('This command list all projects available.')
          ->addOption('filter', 'f', InputOption::VALUE_OPTIONAL, 'Filter value.')
          ->addOption('filter-field', 'ff', InputOption::VALUE_OPTIONAL, 'Filter field.', 'machineName');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders(['Project name', 'Machine name', 'Cron frequency', 'Last analyse', 'Last analyse state', 'Is running', 'Pending']);
        $repo = $this->entityManager->getRepository(Project::class);
        if ($filter = $input->getOption('filter')) {
            // Add a not equals parameter to your criteria
            $criteria = new Criteria();
            $criteria->where(Criteria::expr()->contains($input->getOption('filter-field'), $filter));
            $projects = $repo->matching($criteria);
        } else {
            $projects = $repo->findAll();
        }
        foreach ($projects as $p) {
            /**
             * @var $analyse \App\Entity\Analyse
             */
            $analyse = $p->getLastAnalyse();
            $table->addRow([
              $p->getName(),
              $p->getMachineName(),
              $p->getCronFrequency(),
              $analyse ? $analyse->getDate()->format('d/m/Y H:i:s') : '',
              $analyse ? $analyse->getState() : '',
              $analyse ? $analyse->isRunning() : '',
              $p->isPending(),
            ]);
        }
        $table->render();

        return Command::SUCCESS;
    }
}
