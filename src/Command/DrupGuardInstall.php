<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Hackzilla\PasswordGenerator\Generator\ComputerPasswordGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\PhpExecutableFinder;

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
            'successMessage' => 'Database schema dropped successfully.',
            'errorMessage' => 'Database schema drop fail.'
        ],
        [
            'name' => 'doctrine:schema:create',
            'parameters' => [
                '--no-interaction' => true,
            ],
            'message' => 'Create schema',
            'successMessage' => 'Database schema created successfully.',
            'errorMessage' => 'Database schema creation fail.'
        ]
    ];

    private $projectDir;
    protected $entityManager;
    protected $passwordEncoder;

    public function __construct(KernelInterface $kernel, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordEncoder)
    {
        parent::__construct();
        $this->projectDir = $kernel->getProjectDir();
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
    }

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
        $outputStyle->title('Installing DrupGuard');
        $outputStyle->writeln($this->getDrupGuardLogo());
        $filesystem = new Filesystem();

        //check .env.local
        $nbStep = count($this->commands)+1;
        $commandStart = 1;
        $noOutput = new NullOutput();
        if (!$filesystem->exists($this->projectDir . '/.env.local')) {
            $nbStep++;
            $commandStart++;

            $outputStyle->section(
                sprintf(
                    'Step %d of %d. <info>%s</info>',
                    1,
                    $nbStep,
                    'Create .env.local file'
                )
            );
            try {
                $databaseQuestion = new Question(
                    'Database url (mysql://DB_USER:DB_PASSWORD@DB_HOST:DB_PORT/DB_NAME?serverVersion=DB_SERVER_VERSION, sqlite://KERNEL_PROJECT_DIR/var/app.db, postgresql://DB_USER:DB_PASSWORD@DB_HOST:DB_PORT/DB_NAME?serverVersion=DB_SERVER_VERSION&charset=DB_CHARSET, oci8://DB_USER:DB_PASSWORD@DB_HOST:DB_PORT/DB_NAME)'
                );
                $databaseQuestion->setValidator(
                    function ($answer) {
                        if (!is_string($answer) || !preg_match(
                            '#^(((mysql|oci8|postgresql)://[^:]+:[^@]+@[^:]+:[1-9]\d+/[^\?]+(\?serverVersion=.*)?(&charset=.*)?)|sqlite://.*/var/app.db)$#i',
                            $answer
                        )) {
                            throw new \RuntimeException(
                                'The database url\'s format should be : (mysql://DB_USER:DB_PASSWORD@DB_HOST:DB_PORT/DB_NAME?serverVersion=DB_SERVER_VERSION, sqlite://KERNEL_PROJECT_DIR/var/app.db, postgresql://DB_USER:DB_PASSWORD@DB_HOST:DB_PORT/DB_NAME?serverVersion=DB_SERVER_VERSION&charset=DB_CHARSET or oci8://DB_USER:DB_PASSWORD@DB_HOST:DB_PORT/DB_NAME)'
                            );
                        }

                        return $answer;
                    }
                );
                $databaseUrl = $outputStyle->askQuestion($databaseQuestion);

                $mailerQuestion = new Question(
                    'Mailer DSN (smtp://MAILER_HOST:MAILER_PORT, sendmail://default, native://default)'
                );
                $mailerQuestion->setValidator(
                    function ($answer) {
                        if (!is_string($answer) || ($answer !== 'sendmail://default' && $answer !== 'native://default' && !preg_match(
                            '#^((smtp://[^:]+:[1-9]\d+)|((sendmail|native)://default))$#i',
                            $answer
                        ))) {
                            throw new \RuntimeException(
                                'The mailer dsn\'s format should be : smtp://MAILER_HOST:MAILER_PORT, sendmail://default or native://default'
                            );
                        }

                        return $answer;
                    }
                );
                $mailerDsn = $outputStyle->askQuestion($mailerQuestion);

                $phpFinder = new PhpExecutableFinder();
                $phpExecutable = $phpFinder->find();
                $phpQuestion = new Question(
                    'Php binary path',
                    $phpExecutable ?: null
                );
                $phpExecutable = $outputStyle->askQuestion($phpQuestion);

                $composerFinder = new ExecutableFinder();
                $composerExecutable = $composerFinder->find('composer');
                $composerQuestion = new Question(
                    'Composer binary path',
                    $composerExecutable ?: null
                );
                $composerExecutable = $outputStyle->askQuestion($composerQuestion);

                $composerV1Executable = $composerFinder->find('composer1');
                $composerV1Question = new Question(
                    'Composer v1 binary path',
                    $composerV1Executable ?: null
                );
                $composerV1Executable = $outputStyle->askQuestion($composerV1Question);

                $hostnameQuestion = new Question(
                  'Hostname, used for mail links (drupguard.docksal.site, mysite.domain.tld, 1.2.3.4)',
                );
                $hostnameQuestion->setValidator(
                  function ($answer) {
                    if (
                      !is_string($answer) ||
                      (
                        !preg_match(
                          '#^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$#i',
                          $answer
                        )
                        &&
                        !preg_match(
                          '#^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$#i',
                          $answer
                        )
                      )) {
                      throw new \RuntimeException(
                        'This should be a valid hostname or IP.'
                      );
                    }

                    return $answer;
                  }
                );
                $hostname = $outputStyle->askQuestion($hostnameQuestion);

                $secret = md5(random_bytes(10));

                $envLocal = <<<EOT
APP_SECRET={$secret}
DATABASE_URL={$databaseUrl}
MAILER_DSN={$mailerDsn}
PHP_BINARY={$phpExecutable}
COMPOSER_BINARY={$composerExecutable}
COMPOSER_V1_BINARY={$composerV1Executable}
HOST={$hostname}
EOT;
                file_put_contents($this->projectDir.'/.env.local', $envLocal);
                $outputStyle->success('File .env.local created.');
            } catch (\Exception $e) {
                $outputStyle->error('File .env.local creation failed.');
                return Command::FAILURE;
            }

            $_ENV['DATABASE_URL'] = $databaseUrl;
            $_ENV['MAILER_DSN'] = $mailerDsn;

            $commandObj = $this->getApplication()->find('cache:clear');
            $commandParameters = new ArrayInput([]);
            if ($commandObj->run($commandParameters, $noOutput) != Command::SUCCESS) {
                $outputStyle->error('Cache clear failed.');
                return Command::FAILURE;
            } else {
                $outputStyle->success('Cache has been successfully cleared.');
            }
        }

        $dropQuestion = new ConfirmationQuestion('Existing database will be dropped, continue ?', false);
        if (!$outputStyle->askQuestion($dropQuestion)) {
            $outputStyle->warning('Installation aborted.');
            return Command::SUCCESS;
        }

        foreach ($this->commands as $step => $command) {
            $outputStyle->section(
                sprintf(
                    'Step %d of %d. <info>%s</info>',
                    $step + $commandStart,
                    $nbStep,
                    $command['message']
                )
            );

            $commandObj = $this->getApplication()->find(
                $command['name']
            );
            $commandParameters = new ArrayInput($command['parameters']);
            if ($commandObj->run($commandParameters, $noOutput) != Command::SUCCESS) {
                $outputStyle->error($command['errorMessage']);
                return Command::FAILURE;
            } else {
                $outputStyle->success($command['successMessage']);
            }
        }

        $outputStyle->section(
            sprintf(
                'Step %d of %d. <info>%s</info>',
                $nbStep,
                $nbStep,
                'Create super admin user'
            )
        );
        try {
            $generator = new ComputerPasswordGenerator();

            $generator
                ->setUppercase()
                ->setLowercase()
                ->setNumbers()
                ->setSymbols()
                ->setLength(12);

            $adminPassword = $generator->generatePassword();
            $user = new User();
            $user
              ->setUsername('admin')
              ->setFirstname('admin')
              ->setLastname('admin')
              ->setIsVerified(true)
              ->setEmail('admin@drupguard.com')
              ->setPassword(
                  $this->passwordEncoder->hashPassword(
                      $user,
                      $adminPassword
                  )
              )
              ->setRoles(['ROLE_SUPER_ADMIN']);

            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $outputStyle->success('Admin user created.');
        } catch (\Exception $e) {
            $outputStyle->error('Admin user creation failed.');
            return Command::FAILURE;
        }

        $outputStyle->section('End');
        $outputStyle->comment('Please connect as Admin user with credentials username/password: . <info>admin / ' . $adminPassword . '</info>');
        $outputStyle->success('Drupguard has been successfully installed.');

        return Command::SUCCESS;
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
