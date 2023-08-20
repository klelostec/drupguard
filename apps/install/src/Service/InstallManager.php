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
use Symfony\Component\HttpKernel\KernelInterface;
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

    public static function getDsnParsed(string $dsn):array {
        $dsnParser  = new DsnParser(InstallManager::$driverSchemeAliases);
        return $dsnParser->parse($dsn);
    }

    public static function getConnection(string|array $dsn): Connection {
        $configuration = new ConfigurationDbal();
        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());
        return DriverManager::getConnection(
            !is_array($dsn) ? self::getDsnParsed($dsn) : $dsn,
            $configuration
        );
    }

    public function processInstall(Install $install)
    {
        $env = $this->parameterBag->get('kernel.environment');
        $debug = $this->parameterBag->get('kernel.debug');
        $kernel = new Kernel($env, $debug, 'main');
        $kernel->setPreventInstallRedirect(true);
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $input = new ArrayInput([
            'command' => 'drupguard:install',
            'url' => $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost(),
            'database-dsn' => $install->getDatabaseDsn(),
            'email-dsn' => $install->getEmailDsn(),
            '--locale' => $this->localeSwitcher->getLocale(),
            '--php' => $install->getRequirementPhpBinary(),
            '--composer1' => $install->getRequirementComposerV1Binary(),
            '--composer2' => $install->getRequirementComposerV2Binary(),
            '--no-interaction' => true
        ]);

        $output = new BufferedOutput();
        if ($application->run($input, $output)!==0) {
            throw new \Exception($output->fetch());
        }
    }
}