<?php

namespace App\Entity;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator as AppAssert;

/**
 * @AppAssert\InstallDb(groups={"flow_install_step2"})
 * @AppAssert\InstallEmail(groups={"flow_install_step3"})
 */
class Install {

    /**
     * @var string
     * @AppAssert\BinaryPath(timeout=2, versionValidationRegex="(.*\s*)?PHP(\s*.*)?", groups={"flow_install_step1"})
     */
    public $requirement_php_binary;

    /**
     * @var string
     * @AppAssert\BinaryPath(timeout=2, versionValidationRegex="Composer version\s([0-9\.]+)(\s.*)?", versionCompareRegex="1\..*", groups={"flow_install_step1"})
     */
    public $requirement_composer_v1_binary;

    /**
     * @var string
     * @AppAssert\BinaryPath(timeout=2, versionValidationRegex="Composer version\s([0-9\.]+)(\s.*)?", versionCompareRegex="2\..*", groups={"flow_install_step1"})
     */
    public $requirement_composer_v2_binary;

    /**
     * @var string
     * @Assert\Choice(callback="getDbDrivers", groups={"flow_install_step2"}, strict=true)
     * @Assert\NotBlank(groups={"flow_install_step2"})
     */
    public $db_driver = 'mysql';

    /**
     * @var string
     * @Assert\NotBlank(groups={"flow_install_step2"})
     */
    public $db_host;

    /**
     * @var string
     * @Assert\NotBlank(groups={"flow_install_step2"})
     */
    public $db_user;

    /**
     * @var string
     * @Assert\NotBlank(groups={"flow_install_step2"})
     */
    public $db_password;

    /**
     * @var string
     * @Assert\NotBlank(groups={"flow_install_step2"})
     */
    public $db_database;

    /**
     * @var string
     * @Assert\Choice(callback="getEmailType", groups={"flow_install_step3"}, strict=true)
     * @Assert\NotBlank(groups={"flow_install_step3"})
     */
    public $email_type_install = 'null';

    /**
     * @var string
     * @Assert\When(expression="this.email_type_install=='custom'", constraints={@Assert\NotBlank()},groups={"flow_install_step3"})
     * @Assert\When(expression="this.email_type_install!='custom'", constraints={@Assert\Blank()},groups={"flow_install_step3"})
     */
    public $email_dsn_custom;

    /**
     * @var string
     * @Assert\When(expression="this.email_type_install!='sendmail'", constraints={@Assert\Blank()},groups={"flow_install_step3"})
     */
    public $email_command;

    /**
     * @var string
     * @Assert\When(expression="not(this.email_type_install starts with 'smtp')", constraints={@Assert\Blank()},groups={"flow_install_step3"})
     */
    public $email_user;

    /**
     * @var string
     * @Assert\When(expression="this.email_user==''", constraints={@Assert\Blank()},groups={"flow_install_step3"})
     */
    public $email_password;

    /**
     * @var string
     * @Assert\When(expression="this.email_type_install starts with 'smtp'", constraints={@Assert\NotBlank()},groups={"flow_install_step3"})
     * @Assert\When(expression="not(this.email_type_install starts with 'smtp')", constraints={@Assert\Blank()},groups={"flow_install_step3"})
     */
    public $email_host;

    /**
     * @var string
     * @Assert\When(expression="not(this.email_type_install starts with 'smtp')", constraints={@Assert\Blank()},groups={"flow_install_step3"})
     */
    public $email_local_domain;

    /**
     * @var int
     * @Assert\When(expression="not(this.email_type_install starts with 'smtp')", constraints={@Assert\Blank()},groups={"flow_install_step3"})
     * @Assert\When(expression="this.email_type_install starts with 'smtp'", constraints={@Assert\PositiveOrZero()},groups={"flow_install_step3"})
     */
    public $email_restart_threshold;

    /**
     * @var int
     * @Assert\When(expression="not(this.email_type_install starts with 'smtp')", constraints={@Assert\Blank()},groups={"flow_install_step3"})
     * @Assert\When(expression="this.email_type_install starts with 'smtp'", constraints={@Assert\PositiveOrZero()},groups={"flow_install_step3"})
     */
    public $email_restart_threshold_sleep;

    /**
     * @var int
     * @Assert\When(expression="not(this.email_type_install starts with 'smtp')", constraints={@Assert\Blank()},groups={"flow_install_step3"})
     * @Assert\When(expression="this.email_type_install starts with 'smtp'", constraints={@Assert\PositiveOrZero()},groups={"flow_install_step3"})
     */
    public $email_ping_threshold;

    /**
     * @var int
     * @Assert\When(expression="not(this.email_type_install starts with 'smtp')", constraints={@Assert\Blank()},groups={"flow_install_step3"})
     * @Assert\When(expression="this.email_type_install starts with 'smtp'", constraints={@Assert\PositiveOrZero()},groups={"flow_install_step3"})
     */
    public $email_max_per_second;

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

    public static function getDbDrivers() {
        return [
            'DB2' => 'db2',
            'SqlServer' => 'mssql',
            'Mysql/MariaDB' => 'mysql',
            'Amazon RDS' => 'mysql2',
            'Postgres' => 'postgres',
            'Sqlite' => 'sqlite',
        ];
    }

    public static function getDbDriversDefault() {
        return 'mysql';
    }

    public static function getEmailType() {
        return [
            'None' => 'null',
            'Smtp' => 'smtp',
            'Smtps' => 'smtps',
            'Sendmail' => 'sendmail',
            'Native' => 'native',
            'Custom' => 'custom',
        ];
    }

    public static function getEmailTypeDefault() {
        return 'null';
    }

    public function getDbUrl() {
        return $this->db_driver . '://' .
            urlencode($this->db_user) . ':' .
            urlencode($this->db_password) . '@' .
            urlencode($this->db_host);
    }

    public function getEmailDsn() {
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
