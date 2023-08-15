<?php

namespace Install\Service;

use App\Kernel;
use Install\Entity\Install;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Translation\LocaleSwitcher;

class InstallManager
{
    public function __construct(
        protected LocaleSwitcher $localeSwitcher,
        protected ParameterBagInterface $parameterBag,
    ) {
    }



    public function processInstall(Install $install)
    {
        $projectDir = $this->parameterBag->get('kernel.project_dir');
        $env = $this->parameterBag->get('kernel.environment');
        $debug = $this->parameterBag->get('kernel.debug');

        $databaseDsn = $install->getDatabaseDsn(true);
        $emailDsn = $install->getEmailDsn();
        $secret = bin2hex(random_bytes(16));
        file_put_contents(
            $projectDir . '/.env.'.$env,
            "DATABASE_URL=\"$databaseDsn\"" . PHP_EOL .
            "MAILER_DSN=\"$emailDsn\"" . PHP_EOL .
            "APP_SECRET=" . $secret . PHP_EOL .
            "DEFAULT_LOCALE=" . $this->localeSwitcher->getLocale() . PHP_EOL
        );
        $_ENV['DATABASE_URL'] = $databaseDsn;
        $_ENV['MAILER_DSN'] = $emailDsn;
        $_ENV['APP_SECRET'] = $secret;

        $commands = [
            [
                'command' => 'doctrine:schema:create',
                '--env' => $env,
                '--no-interaction' => true,
            ],
            [
                'command' => 'cache:clear',
                '--env' => $env,
                '--no-interaction' => true,
            ],
        ];

        $kernel = new Kernel($env, $debug, 'main');
        $kernel->setPreventInstallRedirect(true);
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $errors = '';
        $result = 0;
        foreach ($commands as $command) {
            $input = new ArrayInput($command);
            $output = new BufferedOutput();
            $result = $application->run($input, $output);
            if ($result !==0) {
                $errors = $output->fetch();
                break;
            }
        }

        if($result != 0) {
            throw new \Exception($errors);
        }
    }
}