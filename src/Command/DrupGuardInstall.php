<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DrupGuardInstall extends Command
{
    protected static $defaultName = 'drupguard:install';

    protected $commands = [
      [
        'name' => 'doctrine:schema:drop',
        'parameters' => [
          '--force' => true,
        ],
        'message' => 'Ensure empty database',
      ],
      [
        'name' => 'doctrine:schema:create',
        'parameters' => [
          '--no-interaction' => true,
        ],
        'message' => 'Create schema',
      ],
      [
        'name' => 'doctrine:fixtures:load',
        'parameters' => [
          '--append' => true,
        ],
        'message' => 'Load default data',
      ],
    ];

    protected function configure(): void
    {
        $this
          ->setDescription('Install Drupguard.')
          ->setHelp('This command install Drupguard.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $outputStyle = new SymfonyStyle($input, $output);
        $outputStyle->writeln('<info>Installing DrupGuard...</info>');
        $outputStyle->writeln($this->getDrupGuardLogo());

        $errored = false;
        $nbCommands = count($this->commands);
        foreach ($this->commands as $step => $command) {
            try {
                $outputStyle->newLine();
                $outputStyle->section(sprintf(
                  'Step %d of %d. <info>%s</info>',
                  $step + 1,
                  $nbCommands,
                  $command['message']
                ));

                $commandObj = $this->getApplication()->find($command['name']);
                $commandParameters = new ArrayInput($command['parameters']);
                $commandObj->run($commandParameters, $output);
            } catch (\Exception $exception) {
                $errored = true;
            }
        }

        $outputStyle->newLine(2);
        $outputStyle->success($this->getProperFinalMessage($errored));
        if(!$errored) {
            $outputStyle->newLine(2);
            $outputStyle->section('Please connect as Admin user with credentials username/password: . <info>admin / admin</info>');
        }

        return $errored ? 1 : 0;
    }

    private function getProperFinalMessage(bool $errored): string
    {
        if ($errored) {
            return 'Drupguard has been installed, but some error occurred.';
        }

        return 'Drupguard has been successfully installed.';
    }

    private function getDrupguardLogo(): string
    {
        return '
                           .@,                               
                           @@***,                            
                         /@@%*****,                          
                     ,@@@%%/***********,                     
                ,*@@@@@@@%%*****************,                
            ./@@@@@@@@@%%***********************,            
          %@@@@@@@@@%%%****************************,         
       ,@@@@@@@@%%%#*********************************,       
     ,&@@@@@%%%%**************************************,,                    
    *%%%%%%*******************************************,,,                   
   **************************************************,,,,,                  
  ***************************************************,,,,,,         8888888b.                                                                   888 
 ,**************************************************,,,,,,,,        888  "Y88b                                                                  888 
 *************************************************,,,,,,,,,,        888    888                                                                  888 
,***********************************************,,,,,,,,,,,,        888    888 888d888 888  888 88888b.   .d88b.  888  888  8888b.  888d888 .d88888 
,*******************#@@@@@@@&****************,,,,,,,,,,,,,,,        888    888 888P"   888  888 888 "88b d88P"88b 888  888     "88b 888P"  d88" 888 
 ***************/@@@@@@@@@@@@@@@@#********,,,,,,,#@@@@@@@@,,        888    888 888     888  888 888  888 888  888 888  888 .d888888 888    888  888 
 ,*************@@@@@@@@@@@@@@@@@@@@@@**,,,,,,,@@@@@@@@@@@@,,        888  .d88P 888     Y88b 888 888 d88P Y88b 888 Y88b 888 888  888 888    Y88b 888 
  ************@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@,,        8888888P"  888      "Y88888 88888P"   "Y88888  "Y88888 "Y888888 888     "Y88888 
  .**********%@@@@@@@@@@@@@@@@@@@@@@@@@,,,,(@@@@@@@@@@@@@@,                                     888           888                                  
    **********@@@@@@@@@@@@@@@@@@@@,,,,,,,,,,,,@@@@@@@@@@@,                                      888      Y8b d88P                                  
     ,,********%@@@@@@@@@@@@@@,,,,,,@@@@@@@@,,,,,@@@@@@,                                        888       "Y88P"                                   
       ,,,,,,,,,,,,,,,,,,,,,,,,,,*@%,,,,,,,@@,,,,,,,,,,                   
         ,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,         
            ,,,,,,,,,,,,,,,@@@@,,,,,,,,,,,@@@@,,,.           
                ,,,,,,,,,,,,,,,%@@@@@@@@*,,,,.               
                      ,,,,,,,,,,,,,,,,,,                     '
          ;
    }
}