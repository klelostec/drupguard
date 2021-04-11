<?php

namespace App\Service;

use App\Entity\Analyse;
use App\Entity\AnalyseItem;
use App\Entity\Project;
use App\Exception\AnalyseException;
use Cron\CronExpression;
use Cz\Git\GitRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class AnalyseHelper {

    /**
     * Project is missing security update(s).
     */
    const NOT_SECURE = 1;

    /**
     * Current release has been unpublished and is no longer available.
     */
    const REVOKED = 2;

    /**
     * Current release is no longer supported by the project maintainer.
     */
    const NOT_SUPPORTED = 3;

    /**
     * Project has a new release available, but it is not a security release.
     */
    const NOT_CURRENT = 4;

    /**
     * Project is up to date.
     */
    const CURRENT = 5;

    /**
     * Project's status cannot be checked.
     */
    const NOT_CHECKED = -1;

    /**
     * No available update data was found for project.
     */
    const UNKNOWN = -2;

    /**
     * There was a failure fetching available update data for this project.
     */
    const NOT_FETCHED = -3;

    /**
     * We need to (re)fetch available update data for this project.
     */
    const FETCH_PENDING = -4;

    const SUCCESS = 3;
    const WARNING = 2;
    const ERROR = 1;

    protected $entityManager;

    protected $httpClient;

    protected $projectDir;

    public function __construct(EntityManagerInterface $entityManager, KernelInterface $kernel)
    {
        $this->entityManager = $entityManager;
        $this->projectDir = $kernel->getProjectDir();
    }

    function start(Project $project, bool $force = false) {
        if($this->isRunning($project)) {
            throw new AnalyseException('Project "' . $project->getMachineName() .'"\'s analyse is running.', AnalyseException::WARNING);
        }

        if($this->needRunAnalyse($project) || $force) {
            $analyse = new Analyse();
            $analyse->setDate(new \DateTime());
            $analyse->setProject($project);
            $analyse->setIsRunning(true);
            $this->entityManager->persist($analyse);
            $project->setLastAnalyse($analyse);
            $this->entityManager->flush();

            $filesystem = new Filesystem();
            $projectWorkspace = $this->projectDir . '/workspace/' . $project->getMachineName();
            if($filesystem->exists($projectWorkspace)) {
                $filesystem->remove($projectWorkspace);
            }
            $filesystem->mkdir($projectWorkspace);
            $gitClient = GitRepository::cloneRepository($project->getGitRemoteRepository(), $projectWorkspace);
            $gitClient->checkout($project->getGitBranch());

            $drupalDir = $projectWorkspace . $project->getDrupalDirectory();
            if(!$filesystem->exists($drupalDir . '/composer.json') || !$filesystem->exists($drupalDir . '/composer.lock')) {
                $this->stopAnalyse($analyse, 'error');
                throw new AnalyseException('Project "' . $project->getMachineName() .'" doesn\'t contain composer json or lock file.', AnalyseException::ERROR);
            }

            $composerJson = file_get_contents($drupalDir . '/composer.json');
            $composerJson = json_decode($composerJson, true);
            if(empty($composerJson['extra']['installer-paths'])) {
                $this->stopAnalyse($analyse, 'error');
                throw new AnalyseException('Project "' . $project->getMachineName() .'" : composer.json doesn\'t contain key "extra/installer-path".', AnalyseException::ERROR);
            }

            $composerLock = file_get_contents($drupalDir . '/composer.lock');
            $composerLock = json_decode($composerLock, true);
            $status = null;
            if(!empty($composerLock['packages'])) {
                $this->doAnalyse($composerLock['packages'], $analyse, $status);
            }
            if(!empty($composerLock['packages-dev'])) {
                $this->doAnalyse($composerLock['packages-dev'], $analyse, $status);
            }

            $analyse->setState($status);
            $this->entityManager->flush();

            $this->stopAnalyse($analyse);
        }
    }

    protected function doAnalyse(array $packages, Analyse $analyse, &$status) {
        foreach($packages as $p) {
            if(!isset($p['type']) || !in_array($p['type'], ['drupal-core', 'drupal-library', 'drupal-module', 'drupal-profile', 'drupal-theme'])) {
                continue;
            }

            $projectData = $this->update_process_project_info($p);
            try {
                $url = 'https://updates.drupal.org/release-history/' . $projectData['project'] . '/' . $projectData['core_major'];
                $data = (string) $this->httpClient()
                  ->get($url, ['headers' => ['Accept' => 'text/xml']])
                  ->getBody();
                $available = $this->parseXml($data);
                $this->update_calculate_project_update_status($projectData, $available);
            }
            catch (RequestException $exception) {
                $projectData['status'] = self::UNKNOWN;
                $projectData['reason'] = 'No available releases found';
            }

            $analyseItem = new AnalyseItem();
            $analyseItem->setAnalyse($analyse);
            $analyseItem->setType($projectData['type']);
            $analyseItem->setName($projectData['project']);
            $analyseItem->setCurrentVersion($projectData['existing_version']);
            $analyseItem->setLatestVersion($projectData['latest_version']);
            $analyseItem->setRecommandedVersion($projectData['recommended']);
            $analyseItem->setState($projectData['status']);
            $this->entityManager->persist($analyseItem);

            switch ($projectData['status']) {
                case self::CURRENT :
                    if(is_null($status)) {
                        $status = self::SUCCESS;
                    }
                    break;
                case self::NOT_SECURE:
                    if(is_null($status) || $status > self::ERROR) {
                        $status = self::ERROR;
                    }
                    break;
                default:
                    if(is_null($status) || $status === self::SUCCESS) {
                        $status = self::WARNING;
                    }
                    break;
            }

        }
        $this->entityManager->flush();
    }

    protected function parseXml($raw_xml) {
        try {
            $xml = new \SimpleXMLElement($raw_xml);
        }
        catch (\Exception $e) {
            // SimpleXMLElement::__construct produces an E_WARNING error message for
            // each error found in the XML data and throws an exception if errors
            // were detected. Catch any exception and return failure (NULL).
            return NULL;
        }
        // If there is no valid project data, the XML is invalid, so return failure.
        if (!isset($xml->short_name)) {
            return NULL;
        }
        $data = [];
        foreach ($xml as $k => $v) {
            $data[$k] = (string) $v;
        }
        $data['releases'] = [];
        if (isset($xml->releases)) {
            foreach ($xml->releases->children() as $release) {
                $version = (string) $release->version;
                $data['releases'][$version] = [];
                foreach ($release->children() as $k => $v) {
                    $data['releases'][$version][$k] = (string) $v;
                }
                $data['releases'][$version]['terms'] = [];
                if ($release->terms) {
                    foreach ($release->terms->children() as $term) {
                        if (!isset($data['releases'][$version]['terms'][(string) $term->name])) {
                            $data['releases'][$version]['terms'][(string) $term->name] = [];
                        }
                        $data['releases'][$version]['terms'][(string) $term->name][] = (string) $term->value;
                    }
                }
            }
        }
        return $data;
    }

    protected function update_process_project_info($projectDataJson) {
        $projectData = [
          'type' => str_replace('drupal-', '', $projectDataJson['type']),
          'project' => str_replace('drupal/', '', $projectDataJson['name']),
        ];

        // Assume an official release until we see otherwise.
        $install_type = 'official';

        if($projectData['project'] === 'core') {
            $projectData['project'] = 'drupal';
            $projectData['version'] = $projectData['existing_version'] = $projectDataJson['version'];
            $projectData['existing_major'] = $projectData['core_major'] = substr($projectData['version'], 0, 1);
            $projectData['core_major'] .= '.x';
            $projectData['install_type'] = $install_type;
        }
        else {
            if (isset($projectDataJson['dist'])) {
                $projectData['version'] = $projectDataJson['extra']['drupal']['version'];
                $projectData['datestamp'] = $projectDataJson['extra']['drupal']['datestamp'];
            }

            if (isset($projectData['version'])) {
                // Check for development snapshots
                if (preg_match('@(dev|HEAD)@', $projectData['version'])) {
                    $install_type = 'dev';
                }

                // Figure out what the currently installed major version is. We need
                // to handle both contribution (e.g. "5.x-1.3", major = 1) and core
                // (e.g. "5.1", major = 5) version strings.
                $matches = [];
                if (preg_match(
                  '/^(\d+\.x-)?(\d+)\..*$/',
                  $projectData['version'],
                  $matches
                )) {
                    $projectData['core_major'] = $matches[1];
                    $projectData['major'] = $matches[2];
                } elseif (!isset($projectData['major'])) {
                    // This would only happen for version strings that don't follow the
                    // drupal.org convention. We let contribs define "major" in their
                    // .info.yml in this case, and only if that's missing would we hit this.
                    $projectData['major'] = -1;
                }
            } else {
                if (isset($projectDataJson['extra']['drupal']['version']) && preg_match(
                  '/^(\d+\.x-)?(\d+)\..*$/',
                    $projectDataJson['extra']['drupal']['version'],
                  $matches
                )) {
                    $projectData['core_major'] = $matches[1];
                }
                // No version info available at all.
                $install_type = 'unknown';
                $projectData['version'] = 'Unknown';
                $projectData['major'] = -1;
            }

            // Finally, save the results we care about into the $projects array.
            $projectData['existing_version'] = $projectData['version'];
            $projectData['existing_major'] = $projectData['major'];
            $projectData['install_type'] = $install_type;
        }
        return $projectData;
    }

    protected function update_calculate_project_update_status(&$project_data, $available) {
        // If the project status is marked as something bad, there's nothing else
        // to consider.
        if (isset($available['project_status'])) {
            switch ($available['project_status']) {
                case 'insecure':
                    $project_data['status'] = self::NOT_SECURE;
                    if (empty($project_data['extra'])) {
                        $project_data['extra'] = [];
                    }
                    $project_data['extra'][] = [
                      'label' =>'Project not secure',
                      'data' =>'This project has been labeled insecure by the Drupal security team, and is no longer available for download. Immediately disabling everything included by this project is strongly recommended!',
                    ];
                    break;
                case 'unpublished':
                case 'revoked':
                    $project_data['status'] = self::REVOKED;
                    if (empty($project_data['extra'])) {
                        $project_data['extra'] = [];
                    }
                    $project_data['extra'][] = [
                      'label' =>'Project revoked',
                      'data' =>'This project has been revoked, and is no longer available for download. Disabling everything included by this project is strongly recommended!',
                    ];
                    break;
                case 'unsupported':
                    $project_data['status'] = self::NOT_SUPPORTED;
                    if (empty($project_data['extra'])) {
                        $project_data['extra'] = [];
                    }
                    $project_data['extra'][] = [
                      'label' =>'Project not supported',
                      'data' =>'This project is no longer supported, and is no longer available for download. Disabling everything included by this project is strongly recommended!',
                    ];
                    break;
                case 'not-fetched':
                    $project_data['status'] = self::NOT_FETCHED;
                    $project_data['reason'] ='Failed to get available update data.';
                    break;

                default:
                    // Assume anything else (e.g. 'published') is valid and we should
                    // perform the rest of the logic in this function.
                    break;
            }
        }

        if (!empty($project_data['status'])) {
            // We already know the status for this project, so there's nothing else to
            // compute. Record the project status into $project_data and we're done.
            $project_data['project_status'] = $available['project_status'];
            return;
        }

        // Figure out the target major version.
        $existing_major = $project_data['existing_major'];
        $supported_majors = [];
        if (isset($available['supported_majors'])) {
            $supported_majors = explode(',', $available['supported_majors']);
        }
        elseif (isset($available['default_major'])) {
            // Older release history XML file without supported or recommended.
            $supported_majors[] = $available['default_major'];
        }

        if (in_array($existing_major, $supported_majors)) {
            // Still supported, stay at the current major version.
            $target_major = $existing_major;
        }
        elseif (isset($available['recommended_major'])) {
            // Since 'recommended_major' is defined, we know this is the new XML
            // format. Therefore, we know the current release is unsupported since
            // its major version was not in the 'supported_majors' list. We should
            // find the best release from the recommended major version.
            $target_major = $available['recommended_major'];
            $project_data['status'] = self::NOT_SUPPORTED;
        }
        elseif (isset($available['default_major'])) {
            // Older release history XML file without recommended, so recommend
            // the currently defined "default_major" version.
            $target_major = $available['default_major'];
        }
        else {
            // Malformed XML file? Stick with the current version.
            $target_major = $existing_major;
        }

        // Make sure we never tell the admin to downgrade. If we recommended an
        // earlier version than the one they're running, they'd face an
        // impossible data migration problem, since Drupal never supports a DB
        // downgrade path. In the unfortunate case that what they're running is
        // unsupported, and there's nothing newer for them to upgrade to, we
        // can't print out a "Recommended version", but just have to tell them
        // what they have is unsupported and let them figure it out.
        $target_major = max($existing_major, $target_major);

        $release_patch_changed = '';
        $patch = '';

        // If the project is marked as UPDATE_FETCH_PENDING, it means that the
        // data we currently have (if any) is stale, and we've got a task queued
        // up to (re)fetch the data. In that case, we mark it as such, merge in
        // whatever data we have (e.g. project title and link), and move on.
        if (!empty($available['fetch_status']) && $available['fetch_status'] == self::FETCH_PENDING) {
            $project_data['status'] = self::FETCH_PENDING;
            $project_data['reason'] ='No available update data';
            $project_data['fetch_status'] = $available['fetch_status'];
            return;
        }

        // Defend ourselves from XML history files that contain no releases.
        if (empty($available['releases'])) {
            $project_data['status'] = self::UNKNOWN;
            $project_data['reason'] ='No available releases found';
            return;
        }
        foreach ($available['releases'] as $version => $release) {
            // First, if this is the existing release, check a few conditions.
            if ($project_data['existing_version'] === $version) {
                if (isset($release['terms']['Release type']) &&
                  in_array('Insecure', $release['terms']['Release type'])) {
                    $project_data['status'] = self::NOT_SECURE;
                }
                elseif ($release['status'] == 'unpublished') {
                    $project_data['status'] = self::REVOKED;
                    if (empty($project_data['extra'])) {
                        $project_data['extra'] = [];
                    }
                    $project_data['extra'][] = [
                      'class' => ['release-revoked'],
                      'label' =>'Release revoked',
                      'data' =>'Your currently installed release has been revoked, and is no longer available for download. Disabling everything included in this release or upgrading is strongly recommended!',
                    ];
                }
                elseif (isset($release['terms']['Release type']) &&
                  in_array('Unsupported', $release['terms']['Release type'])) {
                    $project_data['status'] = self::NOT_SUPPORTED;
                    if (empty($project_data['extra'])) {
                        $project_data['extra'] = [];
                    }
                    $project_data['extra'][] = [
                      'class' => ['release-not-supported'],
                      'label' =>'Release not supported',
                      'data' =>'Your currently installed release is now unsupported, and is no longer available for download. Disabling everything included in this release or upgrading is strongly recommended!',
                    ];
                }
            }

            // Otherwise, ignore unpublished, insecure, or unsupported releases.
            if ($release['status'] == 'unpublished' ||
              (isset($release['terms']['Release type']) &&
                (in_array('Insecure', $release['terms']['Release type']) ||
                  in_array('Unsupported', $release['terms']['Release type'])))) {
                continue;
            }

            // See if this is a higher major version than our target and yet still
            // supported. If so, record it as an "Also available" release.
            // Note: Some projects have a HEAD release from CVS days, which could
            // be one of those being compared. They would not have version_major
            // set, so we must call isset first.
            if (isset($release['version_major']) && $release['version_major'] > $target_major) {
                if (in_array($release['version_major'], $supported_majors)) {
                    if (!isset($project_data['also'])) {
                        $project_data['also'] = [];
                    }
                    if (!isset($project_data['also'][$release['version_major']])) {
                        $project_data['also'][$release['version_major']] = $version;
                        $project_data['releases'][$version] = $release;
                    }
                }
                // Otherwise, this release can't matter to us, since it's neither
                // from the release series we're currently using nor the recommended
                // release. We don't even care about security updates for this
                // branch, since if a project maintainer puts out a security release
                // at a higher major version and not at the lower major version,
                // they must remove the lower version from the supported major
                // versions at the same time, in which case we won't hit this code.
                continue;
            }

            // Look for the 'latest version' if we haven't found it yet. Latest is
            // defined as the most recent version for the target major version.
            if (!isset($project_data['latest_version'])
              && $release['version_major'] == $target_major) {
                $project_data['latest_version'] = $version;
                $project_data['releases'][$version] = $release;
            }

            // Look for the development snapshot release for this branch.
            if (!isset($project_data['dev_version'])
              && $release['version_major'] == $target_major
              && isset($release['version_extra'])
              && $release['version_extra'] == 'dev') {
                $project_data['dev_version'] = $version;
                $project_data['releases'][$version] = $release;
            }

            // Look for the 'recommended' version if we haven't found it yet (see
            // phpdoc at the top of this function for the definition).
            if (!isset($project_data['recommended'])
              && $release['version_major'] == $target_major
              && isset($release['version_patch'])) {
                if ($patch != $release['version_patch']) {
                    $patch = $release['version_patch'];
                    $release_patch_changed = $release;
                }
                if (empty($release['version_extra']) && $patch == $release['version_patch']) {
                    $project_data['recommended'] = $release_patch_changed['version'];
                    $project_data['releases'][$release_patch_changed['version']] = $release_patch_changed;
                }
            }

            // Stop searching once we hit the currently installed version.
            if ($project_data['existing_version'] === $version) {
                break;
            }

            // If we're running a dev snapshot and have a timestamp, stop
            // searching for security updates once we hit an official release
            // older than what we've got. Allow 100 seconds of leeway to handle
            // differences between the datestamp in the .info.yml file and the
            // timestamp of the tarball itself (which are usually off by 1 or 2
            // seconds) so that we don't flag that as a new release.
            if ($project_data['install_type'] == 'dev') {
                if (empty($project_data['datestamp'])) {
                    // We don't have current timestamp info, so we can't know.
                    continue;
                }
                elseif (isset($release['date']) && ($project_data['datestamp'] + 100 > $release['date'])) {
                    // We're newer than this, so we can skip it.
                    continue;
                }
            }

            // See if this release is a security update.
            if (isset($release['terms']['Release type'])
              && in_array('Security update', $release['terms']['Release type'])) {
                $project_data['security updates'][] = $release;
            }
        }

        // If we were unable to find a recommended version, then make the latest
        // version the recommended version if possible.
        if (!isset($project_data['recommended']) && isset($project_data['latest_version'])) {
            $project_data['recommended'] = $project_data['latest_version'];
        }

        if (isset($project_data['status'])) {
            // If we already know the status, we're done.
            return;
        }

        // If we don't know what to recommend, there's nothing we can report.
        // Bail out early.
        if (!isset($project_data['recommended'])) {
            $project_data['status'] = self::UNKNOWN;
            $project_data['reason'] ='No available releases found';
            return;
        }

        // If we're running a dev snapshot, compare the date of the dev snapshot
        // with the latest official version, and record the absolute latest in
        // 'latest_dev' so we can correctly decide if there's a newer release
        // than our current snapshot.
        if ($project_data['install_type'] == 'dev') {
            if (isset($project_data['dev_version']) && $available['releases'][$project_data['dev_version']]['date'] > $available['releases'][$project_data['latest_version']]['date']) {
                $project_data['latest_dev'] = $project_data['dev_version'];
            }
            else {
                $project_data['latest_dev'] = $project_data['latest_version'];
            }
        }

        // Figure out the status, based on what we've seen and the install type.
        switch ($project_data['install_type']) {
            case 'official':
                if ($project_data['existing_version'] === $project_data['recommended'] || $project_data['existing_version'] === $project_data['latest_version']) {
                    $project_data['status'] = self::CURRENT;
                }
                else {
                    $project_data['status'] = self::NOT_CURRENT;
                }
                break;

            case 'dev':
                $latest = $available['releases'][$project_data['latest_dev']];
                if (empty($project_data['datestamp'])) {
                    $project_data['status'] = self::NOT_CHECKED;
                    $project_data['reason'] ='Unknown release date';
                }
                elseif (($project_data['datestamp'] + 100 > $latest['date'])) {
                    $project_data['status'] = self::CURRENT;
                }
                else {
                    $project_data['status'] = self::NOT_CURRENT;
                }
                break;

            default:
                $project_data['status'] = self::UNKNOWN;
                $project_data['reason'] ='Invalid info';
        }
    }

    protected function stopAnalyse(Analyse $analyse, $state = 'success') {
        $analyse->setIsRunning(false);
        $analyse->setState($state);
        $this->entityManager->flush();
    }

    protected function needRunAnalyse(Project $project): bool
    {
        if(!$project->hasCron() || (!$project->getLastAnalyse())) {
            return true;
        }
        $currentDate = new \DateTime();
        $cronHelper = new CronExpression($project->getCronFrequency());
        return $cronHelper->getNextRunDate() >= $currentDate;
    }

    protected function isRunning(Project $project): ?bool
    {
        return $project->getLastAnalyse() && $project->getLastAnalyse()->isRunning();
    }

    protected function httpClient(): Client {
        if(!$this->httpClient) {
            $default_config = [
              'verify' => TRUE,
              'timeout' => 30,
//              'headers' => [
//                'User-Agent' => 'Drupal/8.x (+https://www.drupal.org/) ' . Utils::defaultUserAgent(),
//              ],
              'handler' => HandlerStack::create(),
                // Security consideration: prevent Guzzle from using environment variables
                // to configure the outbound proxy.
              'proxy' => [
                'http' => NULL,
                'https' => NULL,
                'no' => [],
              ],
            ];

            $this->httpClient = new Client($default_config);
        }
        return $this->httpClient;
    }
}