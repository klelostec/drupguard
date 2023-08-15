<?php

namespace Install\Entity;

use App\Kernel;
use Install\Validator as InstallAssert;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Component\Validator\Constraints as Assert;

#[InstallAssert\InstallDb(groups: ['Default', 'database'])]
#[InstallAssert\InstallEmail(groups: ['Default', 'email'])]
class Install {

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

    #[InstallAssert\BinaryPath(timeout:2, versionValidationRegex:"(.*\s*)?PHP(\s*.*)?", groups: ['Default', 'requirements'])]
    private ?string $requirement_php_binary = null;

    #[InstallAssert\BinaryPath(timeout:2, versionValidationRegex:"Composer version\s([0-9\.]+)(\s.*)?", versionCompareRegex:"1\..*", groups: ['Default', 'requirements'])]
    private ?string $requirement_composer_v1_binary = null;

    #[InstallAssert\BinaryPath(timeout:2, versionValidationRegex:"Composer version\s([0-9\.]+)(\s.*)?", versionCompareRegex:"2\..*", groups: ['Default', 'requirements'])]
    private ?string $requirement_composer_v2_binary = null;

    /**
     * @var string
     * @Assert\Choice(callback="getDbDrivers", strict=true)
     * @Assert\NotBlank
     */
    #[Assert\Choice(callback:"getDbDrivers", strict:true, groups: ['Default', 'database'])]
    #[Assert\NotBlank(groups: ['Default', 'database'])]
    private string $db_driver = 'mysql';

    #[Assert\NotBlank(groups: ['Default', 'database'])]
    private ?string $db_host = null;

    #[Assert\NotBlank(groups: ['Default', 'database'])]
    private ?string $db_user = null;

    #[Assert\NotBlank(groups: ['Default', 'database'])]
    private ?string $db_password = null;

    #[Assert\NotBlank(groups: ['Default', 'database'])]
    private ?string $db_database = null;

    #[Assert\Expression(
        negate: false,
        expression:'this.getDbParameters() starts with "?"',
        message: 'Parameters should not begins with "?".',
        groups: ['Default', 'database']
    )]
    #[Assert\When(
        expression:'this.getDbDriver() in ["mysql", "mysql2", "postgres"]',
        constraints:[
            new Assert\NotBlank(
                message: 'Parameters must contain not empty "serverVersion" parameter.'
            ),
            new Assert\Regex(
                pattern: '/(^|&|\?)serverVersion=[^&]+(&|$)/',
                message: 'Parameters must contain not empty "serverVersion" parameter.',
            )
        ],
        groups: ['Default', 'database']
    )]
    private ?string $db_parameters = null;

    #[Assert\Choice(callback:"getEmailType", strict:true, groups: ['Default', 'email'])]
    private ?string $email_type_install = null;

    #[Assert\When(
        expression:'this.getEmailTypeInstall() === "custom"',
        constraints:[
            new Assert\NotBlank()
        ],
        groups: ['Default', 'email']
    )]
    #[Assert\When(
        expression:'this.getEmailTypeInstall() !== "custom"',
        constraints:[
            new Assert\Blank()
        ],
        groups: ['Default', 'email']
    )]
    private ?string $email_dsn_custom = null;


    #[Assert\When(
        expression:'this.getEmailTypeInstall() !== "sendmail"',
        constraints:[
            new Assert\Blank()
        ],
        groups: ['Default', 'email']
    )]
    private ?string $email_command = null;

    #[Assert\When(
        expression:'not(this.getEmailTypeInstall() starts with "smtp")',
        constraints:[
            new Assert\Blank()
        ],
        groups: ['Default', 'email']
    )]
    private ?String $email_user = null;

    #[Assert\When(
        expression:'this.getEmailUser() === ""',
        constraints:[
            new Assert\Blank()
        ],
        groups: ['Default', 'email']
    )]
    private ?string $email_password = null;

    #[Assert\When(
        expression:'this.getEmailTypeInstall() starts with "smtp"',
        constraints:[
            new Assert\NotBlank()
        ],
        groups: ['Default', 'email']
    )]
    #[Assert\When(
        expression:'not(this.getEmailTypeInstall() starts with "smtp")',
        constraints:[
            new Assert\Blank()
        ],
        groups: ['Default', 'email']
    )]
    private ?string $email_host = null;

    #[Assert\When(
        expression:'not(this.getEmailTypeInstall() starts with "smtp")',
        constraints:[
            new Assert\Blank()
        ],
        groups: ['Default', 'email']
    )]
    private ?string $email_local_domain = null;

    #[Assert\When(
        expression:'this.getEmailTypeInstall() starts with "smtp"',
        constraints:[
            new Assert\PositiveOrZero()
        ],
        groups: ['Default', 'email']
    )]
    #[Assert\When(
        expression:'not(this.getEmailTypeInstall() starts with "smtp")',
        constraints:[
            new Assert\Blank()
        ],
        groups: ['Default', 'email']
    )]
    private ?int $email_restart_threshold = null;

    #[Assert\When(
        expression:'this.getEmailTypeInstall() starts with "smtp"',
        constraints:[
            new Assert\PositiveOrZero()
        ],
        groups: ['Default', 'email']
    )]
    #[Assert\When(
        expression:'not(this.getEmailTypeInstall() starts with "smtp")',
        constraints:[
            new Assert\Blank()
        ],
        groups: ['Default', 'email']
    )]
    private ?int $email_restart_threshold_sleep = null;

    #[Assert\When(
        expression:'this.getEmailTypeInstall() starts with "smtp"',
        constraints:[
            new Assert\PositiveOrZero()
        ],
        groups: ['Default', 'email']
    )]
    #[Assert\When(
        expression:'not(this.getEmailTypeInstall() starts with "smtp")',
        constraints:[
            new Assert\Blank()
        ],
        groups: ['Default', 'email']
    )]
    private ?int $email_ping_threshold = null;

    #[Assert\When(
        expression:'this.getEmailTypeInstall() starts with "smtp"',
        constraints:[
            new Assert\PositiveOrZero()
        ],
        groups: ['Default', 'email']
    )]
    #[Assert\When(
        expression:'not(this.getEmailTypeInstall() starts with "smtp")',
        constraints:[
            new Assert\Blank()
        ],
        groups: ['Default', 'email']
    )]
    private ?int $email_max_per_second = null;

    #[Assert\When(
        expression:'this.getEmailTypeInstall() !== "null"',
        constraints:[
            new Assert\NotBlank(),
            new Assert\Email()
        ],
        groups: ['check_email']
    )]
    private ?string $email = null;

    public function __construct() {
        $phpExecutableFinder = new PhpExecutableFinder();
        $this->requirement_php_binary = $phpExecutableFinder->find(FALSE) ?? NULL;

        $executableFinder = new ExecutableFinder();
        foreach (['', 1, 2] as $composer_version) {
            if ($composer_version !== '' && !empty($this->{'requirement_composer_v' . $composer_version . '_binary'})) {
                continue;
            }
            if ($composer_path = $executableFinder->find('composer' . $composer_version)) {
                $composer = new Process([$composer_path, '--version']);
                try {
                    $composer->setTimeout(5);
                    $composer->run();
                    $output = $composer->getOutput();
                    if (preg_match('/^Composer version (\d)\..*$/i', $output ?? '', $matches)) {
                        if ($matches[1] === '1') {
                            $this->requirement_composer_v1_binary = $composer_path;
                        }
                        else if ($matches[1] === '2') {
                            $this->requirement_composer_v2_binary = $composer_path;
                        }
                    }
                }
                catch (\Exception $e) {
                    // Only catch exception to prevent fatal errors during binaries detection
                }
            }
        }
    }

    public function getRequirementPhpBinary(): ?string
    {
        return $this->requirement_php_binary;
    }

    public function setRequirementPhpBinary(?string $requirement_php_binary): static
    {
        $this->requirement_php_binary = $requirement_php_binary;
        return $this;
    }

    public function getRequirementComposerV1Binary(): ?string
    {
        return $this->requirement_composer_v1_binary;
    }

    public function setRequirementComposerV1Binary(?string $requirement_composer_v1_binary): static
    {
        $this->requirement_composer_v1_binary = $requirement_composer_v1_binary;
        return $this;
    }

    public function getRequirementComposerV2Binary(): ?string
    {
        return $this->requirement_composer_v2_binary;
    }

    public function setRequirementComposerV2Binary(?string $requirement_composer_v2_binary): static
    {
        $this->requirement_composer_v2_binary = $requirement_composer_v2_binary;
        return $this;
    }

    public function getDbDriver(): string
    {
        return $this->db_driver;
    }

    public function setDbDriver(string $db_driver): static
    {
        $this->db_driver = $db_driver;
        return $this;
    }

    public function getDbHost(): ?string
    {
        return $this->db_host;
    }

    public function setDbHost(?string $db_host): static
    {
        $this->db_host = $db_host;
        return $this;
    }

    public function getDbUser(): ?string
    {
        return $this->db_user;
    }

    public function setDbUser(?string $db_user): static
    {
        $this->db_user = $db_user;
        return $this;
    }

    public function getDbPassword(): ?string
    {
        return $this->db_password;
    }

    public function setDbPassword(?string $db_password): static
    {
        $this->db_password = $db_password;
        return $this;
    }

    public function getDbDatabase(): ?string
    {
        return $this->db_database;
    }

    public function setDbDatabase(?string $db_database): static
    {
        $this->db_database = $db_database;
        return $this;
    }

    public function getDbParameters(): ?string
    {
        return $this->db_parameters;
    }

    public function setDbParameters(?string $db_parameters): static
    {
        $this->db_parameters = $db_parameters;
        return $this;
    }

    public function getEmailTypeInstall(): string
    {
        return $this->email_type_install;
    }

    public function setEmailTypeInstall(string $email_type_install): static
    {
        $this->email_type_install = $email_type_install;
        return $this;
    }

    public function getEmailDsnCustom(): ?string
    {
        return $this->email_dsn_custom;
    }

    public function setEmailDsnCustom(?string $email_dsn_custom): static
    {
        $this->email_dsn_custom = $email_dsn_custom;
        return $this;
    }

    public function getEmailCommand(): ?string
    {
        return $this->email_command;
    }

    public function setEmailCommand(?string $email_command): static
    {
        $this->email_command = $email_command;
        return $this;
    }

    public function getEmailUser(): ?string
    {
        return $this->email_user;
    }

    public function setEmailUser(?string $email_user): static
    {
        $this->email_user = $email_user;
        return $this;
    }

    public function getEmailPassword(): ?string
    {
        return $this->email_password;
    }

    public function setEmailPassword(?string $email_password): static
    {
        $this->email_password = $email_password;
        return $this;
    }

    public function getEmailHost(): ?string
    {
        return $this->email_host;
    }

    public function setEmailHost(?string $email_host): static
    {
        $this->email_host = $email_host;
        return $this;
    }

    public function getEmailLocalDomain(): ?string
    {
        return $this->email_local_domain;
    }

    public function setEmailLocalDomain(?string $email_local_domain): static
    {
        $this->email_local_domain = $email_local_domain;
        return $this;
    }

    public function getEmailRestartThreshold(): ?int
    {
        return $this->email_restart_threshold;
    }

    public function setEmailRestartThreshold(?int $email_restart_threshold): static
    {
        $this->email_restart_threshold = $email_restart_threshold;
        return $this;
    }

    public function getEmailRestartThresholdSleep(): ?int
    {
        return $this->email_restart_threshold_sleep;
    }

    public function setEmailRestartThresholdSleep(?int $email_restart_threshold_sleep): static
    {
        $this->email_restart_threshold_sleep = $email_restart_threshold_sleep;
        return $this;
    }

    public function getEmailPingThreshold(): ?int
    {
        return $this->email_ping_threshold;
    }

    public function setEmailPingThreshold(?int $email_ping_threshold): static
    {
        $this->email_ping_threshold = $email_ping_threshold;
        return $this;
    }

    public function getEmailMaxPerSecond(): ?int
    {
        return $this->email_max_per_second;
    }

    public function setEmailMaxPerSecond(?int $email_max_per_second): static
    {
        $this->email_max_per_second = $email_max_per_second;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public static function getDbDrivers(): array {
        return [
            'DB2' => 'db2',
            'SqlServer' => 'mssql',
            'Mysql/MariaDB' => 'mysql',
            'Amazon RDS' => 'mysql2',
            'Postgres' => 'postgres',
            'Sqlite' => 'sqlite',
        ];
    }

    public static function getDbDriversDefault(): string {
        return 'mysql';
    }

    public static function getEmailType(): array {
        return [
            'None' => '',
            'Smtp' => 'smtp',
            'Smtps' => 'smtps',
            'Sendmail' => 'sendmail',
            'Native' => 'native',
            'Custom' => 'custom',
        ];
    }

    public static function getEmailTypeDefault(): string {
        return 'null';
    }

    public function getDbUrl(): string {
        return $this->db_driver . '://' .
            urlencode($this->db_user) . ':' .
            urlencode($this->db_password) . '@' .
            urlencode($this->db_host) .
            $this->getDbDatabase() ? '/' . urlencode($this->getDbDatabase()) : '';
    }

    public function getDatabaseDsn(bool $withDatabase = false): string {
        return $this->getDbDriver() . '://' .
            urlencode($this->getDbUser()) . ':' .
            urlencode($this->getDbPassword()) . '@' .
            urlencode($this->getDbHost()) .
            ($withDatabase ? '/' . urlencode($this->getDbDatabase()) : '') .
            ($this->getDbParameters() ? '?' . $this->getDbParameters() : '');
    }

    public function getEmailDsn(): string {
        $dsn = $this->email_type_install . '://';
        switch ($this->email_type_install) {
            case 'sendmail':
                $dsn .= 'default';
                if (!empty($this->email_command)) {
                    $dsn .= '?command=' . urlencode($this->email_command);
                }
                break;
            case 'smtp':
            case 'smtps':
                if (!empty($this->email_user)) {
                    $dsn .= urlencode($this->email_user) .
                        ($this->email_password ? ':' . urlencode($this->email_password) : '') .
                        '@';
                }
                if (!empty($this->email_host)) {
                    $dsn .= $this->email_host;
                }

                $paramsKeys = [
                    'local_domain',
                    'restart_threshold',
                    'restart_threshold_sleep',
                    'ping_threshold',
                    'max_per_second'
                ];
                $dsnParams = '';
                foreach ($paramsKeys as $key) {
                    if (!empty($this->{'email_' . $key})) {
                        $dsnParams .= $key . '=' . urlencode($this->{'email_' . $key});
                    }
                }

                if (!empty($dsnParams)) {
                    $dsn .= '?' . $dsnParams;
                }
                break;
            case 'null':
                $dsn .= 'null';
                break;
            case 'custom':
                $dsn = $this->email_dsn_custom;
                break;
            case 'native':
            default:
                $dsn .= 'default';
                break;
        }

        return $dsn;
    }
}
