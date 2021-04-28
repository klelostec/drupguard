<?php

namespace App\Service;

use App\Entity\Analyse;
use App\Entity\AnalyseItem;
use App\Entity\Project;
use App\Exception\AnalyseException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class AnalyseHelper
{

    const UPDATE_DEFAULT_URL = 'https://updates.drupal.org/release-history';

    protected $entityManager;

    protected $httpClient;

    protected $projectDir;

    protected $params;

    protected $filesystem;

    protected $mailer;

    public function __construct(
      EntityManagerInterface $entityManager,
      KernelInterface $kernel,
      ContainerBagInterface $params,
      MailerInterface $mailer
    ) {
        $this->entityManager = $entityManager;
        $this->projectDir = $kernel->getProjectDir();
        $this->params = $params;
        $this->filesystem = new Filesystem();
        $this->mailer = $mailer;
    }

    function start(Project $project)
    {
        $analyse = new Analyse();
        $analyse->setDate(new \DateTime());
        $analyse->setProject($project);
        $analyse->setIsRunning(true);
        $this->entityManager->persist($analyse);
        $project->setLastAnalyse($analyse);
        $this->entityManager->flush();

        $projectWorkspace = $this->projectDir.'/workspace/'.$project->getMachineName(
          );
        $drupalDir = $projectWorkspace.$project->getDrupalDirectory();

        try {
            $this->gitCheckout($project, $projectWorkspace);
        }
        catch (\Exception $e) {
            $this->stopAnalyse($analyse, Analyse::ERROR);
            throw new AnalyseException(
              'Cannot checkout project "'.$project->getMachineName().'".',
              AnalyseException::ERROR
            );
        }
        try {
            $this->build($drupalDir);
        }
        catch (\Exception $e) {
            $this->stopAnalyse($analyse, Analyse::ERROR);
            throw new AnalyseException(
              'Cannot build project "'.$project->getMachineName().'".' . PHP_EOL . $e->getMessage(),
              AnalyseException::ERROR
            );
        }
        $drupalInfo = $this->getDrupalInfo($drupalDir);

        if (empty($drupalInfo['version'])) {
            $this->stopAnalyse($analyse, Analyse::ERROR);
            throw new AnalyseException(
              'Project "'.$project->getMachineName(
              ).'" directory "'.$project->getDrupalDirectory(
              ).'" isn\'t a Drupal directory.', AnalyseException::ERROR
            );
        }

        $drupalCompare = new DrupalUpdateCompare();
        $drupalProcessor = new DrupalUpdateProcessor($drupalInfo['compat']);
        switch ($drupalInfo['compat']) {
            case '7.x':
            case '8.x':
                $compareFunction = 'update_calculate_project_update_status_branches';
                break;
            default:
                $compareFunction = 'update_calculate_project_update_status_current';
        }

        try {
            $status = null;
            $items = $this->getItems($drupalInfo);
            foreach ($items as $keyItem => $currentItem) {
                $drupalCompare->update_process_project_info($currentItem);
                $available = $drupalProcessor->processFetchTask($currentItem);

                $drupalCompare->{$compareFunction}(
                    $currentItem,
                    $available
                );

                $analyseItem = new AnalyseItem();
                $analyseItem->setAnalyse($analyse)
                    ->setType($currentItem['project_type'])
                    ->setName($currentItem['info']['name'])
                    ->setMachineName($keyItem ?: 'unknown')
                    ->setCurrentVersion($currentItem['existing_version'])
                    ->setLatestVersion(!empty($currentItem['latest_version']) ? $currentItem['latest_version'] : '')
                    ->setRecommandedVersion(!empty($currentItem['recommended']) ? $currentItem['recommended'] : '')
                    ->setState($currentItem['status']);

                $analyse->addAnalyseItem($analyseItem);

                $detail = '';
                if (!empty($currentItem['also'])) {
                    $detail .= '<div>Major version available : <br><ul>';
                    foreach ($currentItem['also'] as $also) {
                        $detail .= '<li><a href="' . $currentItem['releases'][$also]['release_link'] . '" target="_blank">' . $currentItem['releases'][$also]['version'] . '</a></li>';
                    }
                    $detail .= '</ul></div>';
                }
                if (!empty($currentItem['security updates'])) {
                    $detail .= '<div>Security update available : <br><ul>';
                    foreach ($currentItem['security updates'] as $securityUpdate) {
                        $detail .= '<li><a href="' . $securityUpdate['release_link'] . '" target="_blank">' . $securityUpdate['version'] . '</a></li>';
                    }
                    $detail .= '</ul></div>';
                }

                if (!empty($currentItem['reason'])) {
                    $detail .= '<div>' . $currentItem['reason'] . '</div>';
                }
                if (!empty($currentItem['extra'])) {
                    foreach ($currentItem['extra'] as $extra) {
                        $detail .= '<div><strong>' . $extra['label'] . '</strong><br>' . $extra['data'] . '</div>';
                    }
                }
                $analyseItem->setDetail($detail);

                $this->entityManager->persist($analyseItem);

                switch ($currentItem['status']) {
                    case AnalyseItem::CURRENT :
                        if (is_null($status)) {
                            $status = Analyse::SUCCESS;
                        }
                        break;
                    case AnalyseItem::NOT_SECURE:
                        if (is_null($status) || $status > Analyse::ERROR) {
                            $status = Analyse::ERROR;
                        }
                        break;
                    default:
                        if (is_null($status) || $status === Analyse::SUCCESS) {
                            $status = Analyse::WARNING;
                        }
                        break;
                }
            }
        }
        catch (\Exception $exception) {
            $this->stopAnalyse($analyse, Analyse::ERROR);
            throw new AnalyseException(
                'Project "'.$project->getMachineName(
                ).'" error during analyse run.', AnalyseException::ERROR
            );
        }

        $analyse->setState($status);
        $this->stopAnalyse($analyse, $status);
    }

    protected function gitCheckout(Project $project, $projectWorkspace)
    {
        if ($this->filesystem->exists($projectWorkspace)) {
            $gitClient = new GitHelper($projectWorkspace);
            $gitClient->reset(
              true
            ); //ensure modified files are restored to prevent errors when checkout
            $gitClient->checkout($project->getGitBranch());
            $gitClient->pull();
        } else {
            $this->filesystem->mkdir($projectWorkspace);
            $gitClient = GitHelper::cloneRepository(
              $project->getGitRemoteRepository(),
              $projectWorkspace
            );
            $gitClient->checkout($project->getGitBranch());
        }
    }

    protected function build($drupalDir)
    {
        if ($this->filesystem->exists(
            $drupalDir.'/composer.json'
          ) && $this->filesystem->exists($drupalDir.'/composer.lock')) {
            $composerCmd = explode(
              ' ',
              $this->params->get(
                'drupguard.composer_binary'
              ).' install --ignore-platform-reqs --no-scripts --no-autoloader --quiet --no-interaction'
            );
            $composerInstall = new Process($composerCmd, $drupalDir);
            $composerInstall->setTimeout(60*60);
            if($composerInstall->run() !== 0) {
                throw new \Exception($composerInstall->getErrorOutput());
            }
        }
    }

    protected function getDrupalInfo($drupalDir)
    {
        $info = [
          'version' => '',
          'compat' => '',
          'directories' => [],
        ];
        if ($this->filesystem->exists($drupalDir.'/composer.json')) {
            $composerJson = file_get_contents($drupalDir.'/composer.json');
            $composerJson = json_decode($composerJson, true);

            if (!empty($composerJson['extra']['installer-paths'])) {
                foreach ($composerJson['extra']['installer-paths'] as $dir => $types) {
                    if (in_array('type:drupal-core', $types)) {
                        $info['directories']['core'] = $drupalDir.'/'.str_replace(
                            '/{$name}',
                            '',
                            $dir
                          );
                    } else {
                        if (in_array('type:drupal-module', $types)) {
                            $info['directories']['module'] = $drupalDir.'/'.str_replace(
                                '/{$name}',
                                '',
                                $dir
                              );
                        } else {
                            if (in_array('type:drupal-theme', $types)) {
                                $info['directories']['theme'] = $drupalDir.'/'.str_replace(
                                    '/{$name}',
                                    '',
                                    $dir
                                  );
                            }
                        }
                    }
                };
            }
        }

        if (empty($info['directories']['core'])) {
            if ($this->filesystem->exists($drupalDir.'/core/lib/Drupal.php')) {
                $info['directories']['core'] = $drupalDir.'/core';
                $info['directories']['module'] = $drupalDir.'/modules'.($this->filesystem->exists(
                    $drupalDir.'/modules/contrib'
                  ) ? '/contrib' : '');
                $info['directories']['theme'] = $drupalDir.'/themes'.($this->filesystem->exists(
                    $drupalDir.'/themes/contrib'
                  ) ? '/contrib' : '');
            } else {
                if ($this->filesystem->exists(
                  $drupalDir.'/includes/bootstrap.inc'
                )) {
                    $info['directories']['core'] = $drupalDir;
                    $info['directories']['module'] = $drupalDir.'/sites/all/modules'.($this->filesystem->exists(
                        $drupalDir.'/sites/all/modules/contrib'
                      ) ? '/contrib' : '');
                    $info['directories']['theme'] = $drupalDir.'/sites/all/themes'.($this->filesystem->exists(
                        $drupalDir.'/sites/all/themes/contrib'
                      ) ? '/contrib' : '');
                }
            }
        }

        if(!empty($info['directories']['core'])) {
            if ($this->filesystem->exists(
                $info['directories']['core'].'/lib/Drupal.php'
            )) {
                $drupalClass = file_get_contents($info['directories']['core'].'/lib/Drupal.php');
                preg_match('/const CORE_COMPATIBILITY = \'([0-9a-z\.]+)\';/i', $drupalClass, $matches);
                $info['compat'] = $matches[1];
                preg_match('/const VERSION = \'([0-9a-z\.\-]+)\';/i', $drupalClass, $matches);
                $info['version'] = $matches[1];
                $info['extension'] = '.info.yml';
            } else {
                if ($this->filesystem->exists(
                    $info['directories']['core'].'/includes/bootstrap.inc'
                )) {
                    $bootstrapInc = file_get_contents($info['directories']['core'].'/includes/bootstrap.inc');
                    preg_match('/define\(\'DRUPAL_CORE_COMPATIBILITY\', \'([0-9a-z\.]+)\'\);/i', $bootstrapInc, $matches);
                    $info['compat'] = $matches[1];
                    preg_match('/define\(\'VERSION\', \'([0-9a-z\.\-]+)\'\);/i', $bootstrapInc, $matches);
                    $info['version'] = $matches[1];
                    $info['extension'] = '.info';
                }
            }

            if(substr($info['compat'], 0, 1) < substr($info['version'], 0, 1)) {
                $info['compat'] = 'current';
            }
        }

        return $info;
    }

    protected function getItems($drupalInfo)
    {
        $items = [];

        //core
        $items['drupal'] = [
          'name' => 'drupal',
          'info' => [
            'name' => 'Drupal core',
            'description' => 'Drupal core',
            'version' => $drupalInfo['version'],
            'core' => $drupalInfo['compat'],
          ],
          'project_type' => 'core',
        ];

        //modules
        $this->searchItem(
          $drupalInfo['directories']['module'],
          'module',
          $drupalInfo['extension'],
          $items
        );

        //themes
        $this->searchItem(
          $drupalInfo['directories']['theme'],
          'theme',
          $drupalInfo['extension'],
          $items
        );

        return $items;
    }

    protected function scanItem($directory, $extension) {
        if (!is_dir($directory)) {
            return FALSE;
        }
        $handle = opendir($directory);
        while (false !== ($entry = readdir($handle))) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }
            if(substr($entry, -1*strlen($extension)) === $extension) {
                return "$directory/$entry";
            }
        }
        return FALSE;
    }

    protected function searchItem($directory, $type, $extension, &$items)
    {
        if (!is_dir($directory)) {
            return;
        }
        $handle = opendir($directory);
        while (false !== ($entry = readdir($handle))) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }
            if (is_dir("$directory/$entry")) {
                if ($infoFile = $this->scanItem("$directory/$entry", $extension)) {
                    $data = file_get_contents($infoFile);
                    switch ($extension) {
                        case '.info.yml':
                            $items[$entry] = [
                              'name' => $entry,
                              'info' => Yaml::parse($data),
                              'project_type' => $type,
                            ];
                            break;
                        case '.info':
                            $items[$entry] = [
                              'name' => $entry,
                              'info' => $this->drupal_parse_info_format(
                                $data
                              ),
                              'project_type' => $type,
                            ];
                            break;
                    }
                } else {
                    $this->searchItem(
                      "$directory/$entry",
                      $type,
                      $extension,
                      $items
                    );
                }
            }
        }
        closedir($handle);
    }

    /**
     * Parses data in Drupal's .info format.
     *
     * Data should be in an .ini-like format to specify values. White-space
     * generally doesn't matter, except inside values:
     *
     * @code
     *   key = value
     *   key = "value"
     *   key = 'value'
     *   key = "multi-line
     *   value"
     *   key = 'multi-line
     *   value'
     *   key
     *   =
     *   'value'
     * @endcode
     *
     * Arrays are created using a HTTP GET alike syntax:
     * @code
     *   key[] = "numeric array"
     *   key[index] = "associative array"
     *   key[index][] = "nested numeric array"
     *   key[index][index] = "nested associative array"
     * @endcode
     *
     * PHP constants are substituted in, but only when used as the entire value.
     * Comments should start with a semi-colon at the beginning of a line.
     *
     * @param $data
     *   A string to parse.
     *
     * @return
     *   The info array.
     *
     * @see drupal_parse_info_file()
     */
    function drupal_parse_info_format($data)
    {
        $info = [];

        if (preg_match_all(
          '
    @^\s*                           # Start at the beginning of a line, ignoring leading whitespace
    ((?:
      [^=;\[\]]|                    # Key names cannot contain equal signs, semi-colons or square brackets,
      \[[^\[\]]*\]                  # unless they are balanced and not nested
    )+?)
    \s*=\s*                         # Key/value pairs are separated by equal signs (ignoring white-space)
    (?:
      ("(?:[^"]|(?<=\\\\)")*")|     # Double-quoted string, which may contain slash-escaped quotes/slashes
      (\'(?:[^\']|(?<=\\\\)\')*\')| # Single-quoted string, which may contain slash-escaped quotes/slashes
      ([^\r\n]*?)                   # Non-quoted string
    )\s*$                           # Stop at the next end of a line, ignoring trailing whitespace
    @msx',
          $data,
          $matches,
          PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                // Fetch the key and value string.
                $i = 0;
                foreach (['key', 'value1', 'value2', 'value3'] as $var) {
                    $$var = isset($match[++$i]) ? $match[$i] : '';
                }
                $value = stripslashes(substr($value1, 1, -1)).stripslashes(
                    substr($value2, 1, -1)
                  ).$value3;

                // Parse array syntax.
                $keys = preg_split('/\]?\[/', rtrim($key, ']'));
                $last = array_pop($keys);
                $parent = &$info;

                // Create nested arrays.
                foreach ($keys as $key) {
                    if ($key == '') {
                        $key = count($parent);
                    }
                    if (!isset($parent[$key]) || !is_array($parent[$key])) {
                        $parent[$key] = [];
                    }
                    $parent = &$parent[$key];
                }

                // Handle PHP constants.
                if (preg_match('/^\w+$/i', $value) && defined($value)) {
                    $value = constant($value);
                }

                // Insert actual value.
                if ($last == '') {
                    $last = count($parent);
                }
                $parent[$last] = $value;
            }
        }

        return $info;
    }

    protected function stopAnalyse(Analyse $analyse, $state = Analyse::SUCCESS)
    {
        $analyse->setIsRunning(false);
        $analyse->setState($state);
        $this->entityManager->flush();

        $project = $analyse->getProject();
        if($project->needEmail()) {
            $email = (new TemplatedEmail())
              ->subject('Project ' . $analyse->getProject()->getName() . ' - ' . $analyse->getDate()->format('d/m/Y H:i:s'))
              ->from('no-reply@drupguard.com');
            $emailsAddress = [$project->getOwner()->getEmail()];
            foreach($project->getAllowedUsers() as $user) {
                $emailsAddress[] = $user->getEmail();
            }

            $extraEmails = $project->getEmailExtra();
            $extraEmails = str_replace("\r\n", "\n", $extraEmails);
            $extraEmails = explode("\n", $extraEmails);
            $emailsAddress = $emailsAddress + $extraEmails;

            $emailsAddress = array_unique($emailsAddress);
            $email->to(...$emailsAddress);
            $email->htmlTemplate('project/email.html.twig')
                ->context([
                  'project' => $project,
                  'analyse' => $analyse,
                ]);
            $this->mailer->send($email);
        }
    }

}