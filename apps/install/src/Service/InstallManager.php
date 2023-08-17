<?php

namespace Install\Service;

use App\Kernel;
use Doctrine\DBAL\Configuration as ConfigurationDbal;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\DBAL\Tools\DsnParser;
use Install\Entity\Install;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\LocaleSwitcher;

class InstallManager
{

    public const TABLE_CHECK_INSTALLER = 'user';

    public static array $driverSchemeAliases = [
        'db2'        => 'ibm_db2',
        'mssql'      => 'pdo_sqlsrv',
        'mysql'      => 'pdo_mysql',
        'mysql2'     => 'pdo_mysql', // Amazon RDS, for some weird reason
        'postgres'   => 'pdo_pgsql',
        'postgresql' => 'pdo_pgsql',
        'pgsql'      => 'pdo_pgsql',
        'sqlite'     => 'pdo_sqlite',
        'sqlite3'    => 'pdo_sqlite',
    ];

    protected LocaleSwitcher $localeSwitcher;
    protected ParameterBagInterface $parameterBag;
    protected RequestStack $requestStack;

    public function __construct(LocaleSwitcher $localeSwitcher, ParameterBagInterface $parameterBag, RequestStack $requestStack) {
        $this->localeSwitcher = $localeSwitcher;
        $this->parameterBag = $parameterBag;
        $this->requestStack = $requestStack;
    }

    public static function getConnection(string $dsn): Connection {
        $dsnParser  = new DsnParser(InstallManager::$driverSchemeAliases);
        $configuration = new ConfigurationDbal();
        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());
        return DriverManager::getConnection(
            $dsnParser->parse($dsn),
            $configuration
        );
    }

    public function processInstall(Install $install)
    {
        $projectDir = $this->parameterBag->get('kernel.project_dir');
        $env = $this->parameterBag->get('kernel.environment');
        $debug = $this->parameterBag->get('kernel.debug');

        $databaseDsn = $install->getDatabaseDsn();
        $emailDsn = $install->getEmailDsn();
        $secret = bin2hex(random_bytes(16));
        file_put_contents(
            $projectDir . '/.env.'.$env,
            "DATABASE_URL=\"$databaseDsn\"" . PHP_EOL .
            "MAILER_DSN=\"$emailDsn\"" . PHP_EOL .
            "APP_SECRET=" . $secret . PHP_EOL .
            "DEFAULT_LOCALE=" . $this->localeSwitcher->getLocale() . PHP_EOL .
            "ROUTER_HOST=\"" . $this->requestStack->getCurrentRequest()->getHost() . "\"" . PHP_EOL .
            "ROUTER_SCHEME=" . $this->requestStack->getCurrentRequest()->getScheme() . PHP_EOL
        );
        $_ENV['DATABASE_URL'] = $databaseDsn;
        $_ENV['MAILER_DSN'] = $emailDsn;
        $_ENV['APP_SECRET'] = $secret;

        $commands = [
            [
                'command' => 'doctrine:schema:create',
                '--env' => $env,
                '--no-interaction' => true
            ],
            [
                'command' => 'cache:clear',
                '--env' => $env,
                '--no-interaction' => true,
            ],
            [
                'command' => 'app:user:create',
                'username' => 'admin',
                'password' => 'admin',
                'email' => 'admin@drupguard.com',
                '--admin' => true,
                '--verified' => true,
                '--env' => $env,
                '--no-interaction' => true,
            ],
        ];
        $conn = self::getConnection($install->getDatabaseDsn(false));
        if (!in_array($install->getDbDatabase(), $conn->createSchemaManager()->listDatabases())) {
            array_unshift($commands, [
                'command' => 'doctrine:database:create',
                '--env' => $env,
                '--no-interaction' => true
            ]);
        }

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