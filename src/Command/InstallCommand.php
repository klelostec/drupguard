<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Service\ExecutableFinderTrait;
use Install\Service\InstallManager;
use Install\Validator\BinaryPath;
use Install\Validator\InstallDb;
use Install\Validator\InstallEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;

/**
 * A console command that install drupguard.
 *
 * To use this command, open a terminal window, enter into your project
 * directory and execute the following:
 *
 *     $ php bin/console drupguard:install
 *
 * To output detailed information, increase the command verbosity:
 *
 *     $ php bin/console drupguard:install -vv
 *
 * See https://symfony.com/doc/current/console.html
 *
 * We use the default services.yaml configuration, so command classes are registered as services.
 * See https://symfony.com/doc/current/console/commands_as_services.html
 */
#[AsCommand(
    name: 'drupguard:install',
    description: 'Install Drupguard application'
)]
final class InstallCommand extends Command
{
    use ExecutableFinderTrait;

    private SymfonyStyle $io;

    protected string $phpDefault;
    protected string $composerV1Default;
    protected string $composerV2Default;
    protected string $emailDsnDefault = 'null://null';
    protected string $localeDefault = 'en';
    protected array $allowedLocales;
    protected string $allowedLocalesString;

    public function __construct() {
        $this->phpDefault = $this->getPhpBinary();
        $this->composerV1Default = $this->getComposerBinary(1);
        $this->composerV2Default = $this->getComposerBinary(2);

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp($this->getCommandHelp())
            // commands can optionally define arguments and/or options (mandatory and optional)
            // see https://symfony.com/doc/current/components/console/console_arguments.html
            ->addArgument('url', InputArgument::REQUIRED, 'The drupguard Url (to build correct links when needed)')
            ->addArgument('database-dsn', InputArgument::REQUIRED, 'The database DSN')
            ->addArgument('email-dsn', InputArgument::OPTIONAL, 'The email DSN', $this->emailDsnDefault)
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'The locale', $this->localeDefault)
            ->addOption('php', null, InputOption::VALUE_REQUIRED, 'The php binary path', $this->phpDefault)
            ->addOption('composer1', null, InputOption::VALUE_REQUIRED, 'The composer v1 binary path', $this->composerV1Default)
            ->addOption('composer2', null, InputOption::VALUE_REQUIRED, 'The composer v2 binary path', $this->composerV2Default)
        ;
    }

    /**
     * This optional method is the first one executed for a command after configure()
     * and is useful to initialize properties based on the input arguments and options.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // SymfonyStyle is an optional feature that Symfony provides so you can
        // apply a consistent look to the commands of your application.
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);

        $kernel = $this->getApplication()->getKernel();
        $this->allowedLocalesString = $kernel->getContainer()->getParameterBag()->get('app.supported_locales');
        $this->allowedLocales = explode('|', $this->allowedLocalesString);
    }

    /**
     * This method is executed after initialize() and before execute(). Its purpose
     * is to check if some of the options/arguments are missing and interactively
     * ask the user for those values.
     *
     * This method is completely optional. If you are developing an internal console
     * command, you probably should not implement this method because it requires
     * quite a lot of work. However, if the command is meant to be used by external
     * users, this method is a nice way to fall back and prevent errors.
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (
            null !== $input->getArgument('url') &&
            null !== $input->getArgument('database-dsn') &&
            null !== $input->getArgument('email-dsn')) {
            return;
        }

        $this->io->title('Drupguard Install Command Interactive Wizard');
        $this->io->text([
            'If you prefer to not use this interactive wizard, provide the',
            'arguments required by this command as follows:',
            '',
            ' $ php bin/console drupguard:install https://drupguard.com mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=5.7 smtp://user:pass@smtp.example.com:25',
            '',
            'Now we\'ll ask you for the value of all the missing command arguments.',
        ]);

        // Ask for the host if it's not defined
        $url = $input->getArgument('url');
        if (null !== $url) {
            $this->io->text(' > <info>Url</info>: '.$url);
        } else {
            $url = $this->io->ask('Url', null, $this->validate_url());
            $input->setArgument('url', $url);
        }

        // Ask for the database-dsn if it's not defined
        $db = $input->getArgument('database-dsn');
        if (null !== $db) {
            $this->io->text(' > <info>Database DSN</info>: '.$db);
        } else {
            $db = $this->io->ask('Database DSN', null, $this->validate_db());
            $input->setArgument('database-dsn', $db);
        }

        // Ask for the email-dsn if it's not defined
        $email = $input->getArgument('email-dsn');
        if ($this->emailDsnDefault !== $email) {
            $this->io->text(' > <info>Email DSN</info>: '.$email);
        } else {
            $email = $this->io->ask('Email DSN', $this->emailDsnDefault, $this->validate_email());
            $input->setArgument('email-dsn', $email);
        }

        // Ask for the locale if it's not defined
        $locale = $input->getOption('locale');
        if ($this->localeDefault !== $locale) {
            $this->io->text(' > <info>Default locale</info>: '.$locale);
        } else {
            $locale = $this->io->choice('Default locale', $this->allowedLocales, $this->localeDefault);
            $input->setOption('locale', $locale);
        }

        // Ask for the php if it's not defined
        $php = $input->getOption('php');
        if ($this->phpDefault !== $php) {
            $this->io->text(' > <info>Php binary path</info>: '.$php);
        } else {
            $php = $this->io->ask('Php binary path', $this->phpDefault, $this->validate_php());
            $input->setOption('php', $php);
        }

        $composer1 = $input->getOption('composer1');
        if ($this->composerV1Default !== $composer1) {
            $this->io->text(' > <info>Composer v1 binary path</info>: '.$composer1);
        } else {
            $composer1 = $this->io->ask('Composer v1 binary path', $this->composerV1Default, $this->validate_composer_v1());
            $input->setOption('composer1', $composer1);
        }

        $composer2 = $input->getOption('composer2');
        if ($this->composerV2Default !== $composer2) {
            $this->io->text(' > <info>Composer v2 binary path</info>: '.$composer2);
        } else {
            $composer2 = $this->io->ask('Composer v2 binary path', $this->composerV2Default, $this->validate_composer_v2());
            $input->setOption('composer2', $composer2);
        }
    }

    protected function validate_url() {
        return function(string $val) :string {
            if (empty($val)) {
                throw new InvalidArgumentException('The url can not be empty.');
            }

            $validator = Validation::createValidator();
            $violations = $validator->validate($val, [
                new Url(
                    protocols: ['http', 'https'],
                    normalizer: function($val){return trim($val, "/ \n\r\t\v\x00");}
                ),
            ]);

            if (0 !== count($violations)) {
                // there are errors, now you can show them
                foreach ($violations as $violation) {
                    throw new InvalidArgumentException($violation->getMessage());
                }
            }

            return $val;
        };
    }

    protected function validate_db() {
        return function(string $val) :string {
            if (empty($val)) {
                throw new InvalidArgumentException('The database-dsn can not be empty.');
            }

            $validator = Validation::createValidator();
            $violations = $validator->validate($val, [
                new InstallDb(),
            ]);

            if (0 !== count($violations)) {
                // there are errors, now you can show them
                foreach ($violations as $violation) {
                    throw new InvalidArgumentException($violation->getMessage());
                }
            }

            return $val;
        };
    }
    protected function validate_email() {
        return function (string $val):string {
            if (empty($val)) {
                throw new InvalidArgumentException('The email-dsn can not be empty.');
            }

            $validator = Validation::createValidator();
            $violations = $validator->validate($val, [
                new InstallEmail(),
            ]);

            if (0 !== count($violations)) {
                // there are errors, now you can show them
                foreach ($violations as $violation) {
                    throw new InvalidArgumentException($violation->getMessage());
                }
            }

            return $val;
        };
    }



    protected function validate_locale() {
        $allowed_locales = $this->allowedLocales;
        return function (string $val) use ($allowed_locales):string {
            if (empty($val)) {
                throw new InvalidArgumentException('The default locale can not be empty.');
            }

            $validator = Validation::createValidator();
            $violations = $validator->validate($val, [
                new Choice(
                    choices: $allowed_locales
                )
            ]);

            if (0 !== count($violations)) {
                // there are errors, now you can show them
                foreach ($violations as $violation) {
                    throw new InvalidArgumentException($violation->getMessage());
                }
            }

            return $val;
        };
    }
    protected function validate_php() {
        return function (string $val):string {
            if (empty($val)) {
                throw new InvalidArgumentException('The php binary path can not be empty.');
            }

            $validator = Validation::createValidator();
            $violations = $validator->validate($val, [
                new BinaryPath(2, null, "(.*\s*)?PHP(\s*.*)?")
            ]);

            if (0 !== count($violations)) {
                // there are errors, now you can show them
                foreach ($violations as $violation) {
                    throw new InvalidArgumentException($violation->getMessage());
                }
            }

            return $val;
        };
    }
    protected function validate_composer_v1() {
        return function (string $val):string {
            if (empty($val)) {
                throw new InvalidArgumentException('The composer v1 binary path can not be empty.');
            }

            $validator = Validation::createValidator();
            $violations = $validator->validate($val, [
                new BinaryPath(2, null, "Composer version\s([0-9\.]+)(\s.*)?", "1\..*")
            ]);

            if (0 !== count($violations)) {
                // there are errors, now you can show them
                foreach ($violations as $violation) {
                    throw new InvalidArgumentException($violation->getMessage());
                }
            }

            return $val;
        };
    }
    protected function validate_composer_v2() {
        return function (string $val):string {
            if (empty($val)) {
                throw new InvalidArgumentException('The composer v2 binary path can not be empty.');
            }

            $validator = Validation::createValidator();
            $violations = $validator->validate($val, [
                new BinaryPath(2, null, "Composer version\s([0-9\.]+)(\s.*)?", "2\..*")
            ]);

            if (0 !== count($violations)) {
                // there are errors, now you can show them
                foreach ($violations as $violation) {
                    throw new InvalidArgumentException($violation->getMessage());
                }
            }

            return $val;
        };
    }

    /**
     * This method is executed after interact() and initialize(). It usually
     * contains the logic to execute to complete this command task.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('drupguard-install-command');

        /** @var string $url */
        $url = $input->getArgument('url');

        /** @var string $databaseDsn */
        $databaseDsn = $input->getArgument('database-dsn');

        /** @var string $emailDsn */
        $emailDsn = $input->getArgument('email-dsn');

        /** @var string =locale */
        $locale = $input->getOption('locale');

        /** @var string $php */
        $php = $input->getOption('php');

        /** @var string $composer1 */
        $composer1 = $input->getOption('composer1');

        /** @var string $composer2 */
        $composer2 = $input->getOption('composer2');

        // make sure to validate the user data is correct
        $this->validateData($url, $databaseDsn, $emailDsn, $locale, $php, $composer1, $composer2);

        $kernel = $this->getApplication()->getKernel();
        $projectDir = $kernel->getContainer()->getParameterBag()->get('kernel.project_dir');
        $env = $kernel->getContainer()->getParameterBag()->get('kernel.environment');
        $secret = bin2hex(random_bytes(16));
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME);
        file_put_contents(
            $projectDir . '/.env.'.$env,
            "DATABASE_URL=\"$databaseDsn\"" . PHP_EOL .
            "MAILER_DSN=\"$emailDsn\"" . PHP_EOL .
            "APP_SECRET=" . $secret . PHP_EOL .
            "DEFAULT_LOCALE=" . $locale . PHP_EOL .
            "ROUTER_HOST=\"" . $host . "\"" . PHP_EOL .
            "ROUTER_SCHEME=" . $scheme . PHP_EOL
        );

        $_ENV['DATABASE_URL'] = $databaseDsn;
        $_ENV['MAILER_DSN'] = $emailDsn;
        $_ENV['APP_SECRET'] = $secret;

        $databaseDsnArray = InstallManager::getDsnParsed($databaseDsn);
        $db = $databaseDsnArray['dbname'];
        unset($databaseDsnArray['dbname']);

        $conn = InstallManager::getConnection($databaseDsnArray);
        $databases = $conn->createSchemaManager()->listDatabases();
        $commands = [];
        if (!in_array($db, $databases)) {
            $commands[] = [
                'command' => 'doctrine:database:create',
                'args' => [
                    '--no-interaction' => true
                ]
            ];
        }
        array_push($commands, ...[
            [
                'command' => 'doctrine:schema:create',
                'args' => [
                    '--no-interaction' => true
                ]
            ],
            [
                'command' => 'cache:clear',
                'args' => [
                    '--no-interaction' => true
                ]
            ],
            [
                'command' => 'drupguard:user:create',
                'args' => [
                    'username' => 'admin',
                    'password' => 'admin',
                    'email' => 'admin@drupguard.com',
                    '--admin' => true,
                    '--verified' => true,
                    '--password-validation-ignored' => true,
                    '--show-credentials' => true,
                    '--no-interaction' => true,
                ],
            ],
        ]);

        foreach ($commands as $commandDef) {
            $command = $this->getApplication()->find($commandDef['command']);
            $cmdInput = new ArrayInput($commandDef['args']);
            $returnCode = $command->run($cmdInput, $output);
            if ($returnCode !==0) {
                $errors = $output->fetch();
                break;
            }
        }

        $this->io->success('Drupguard was successfully installed');

        $event = $stopwatch->stop('drupguard-install-command');
        if ($output->isVerbose()) {
            $this->io->comment(sprintf('Elapsed time: %.2f ms / Consumed memory: %.2f MB', $event->getDuration(), $event->getMemory() / (1024 ** 2)));
        }

        return Command::SUCCESS;
    }

    private function validateData(string $url, string $database_dsn, string $email_dsn, string $locale, string $php, string $composer_v1, string $composer_v2): void
    {
        call_user_func($this->validate_url(),$url);
        call_user_func($this->validate_db(),$database_dsn);
        call_user_func($this->validate_email(), $email_dsn);
        call_user_func($this->validate_locale(), $locale);
        call_user_func($this->validate_php(), $php);
        call_user_func($this->validate_composer_v1(), $composer_v1);
        call_user_func($this->validate_composer_v2(), $composer_v2);
    }

    /**
     * The command help is usually included in the configure() method, but when
     * it's too long, it's better to define a separate method to maintain the
     * code readability.
     */
    private function getCommandHelp(): string
    {
        return <<<'HELP'
            The <info>%command.name%</info> command install drupguard:

              <info>php %command.full_name%</info>

            By default the command install Drupguard:

              <info>php %command.full_name%</info> database-dsn email-dsn <comment>--php</comment> <comment>--composer1</comment> <comment>--composer2</comment>

            If you omit any of the required arguments, the command will ask you to
            provide the missing values:

              # command will ask you for the email-dsn
              <info>php %command.full_name%</info> <comment>database-dsn</comment>

              # command will ask you for all arguments
              <info>php %command.full_name%</info>
            HELP;
    }
}
