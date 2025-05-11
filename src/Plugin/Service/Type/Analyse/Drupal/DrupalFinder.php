<?php

namespace App\Plugin\Service\Type\Analyse\Drupal;

use App\Plugin\Exception\Analyse;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

class DrupalFinder
{
    protected readonly TranslatorInterface $translator;
    protected LoggerInterface $logger;

    protected string $type;
    protected string $version;
    protected string $compat;
    protected string $extension;
    protected array $directories = [];
    protected array $items;

    protected Filesystem $filesystem;

    const DRUPAL_TYPE_KEY = [
        'core' => 'type:drupal-core',
        'modules' => 'type:drupal-module',
        'profiles' => 'type:drupal-profile',
        'themes' => 'type:drupal-theme',
    ];

    public function __construct(
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->translator = $translator;
        $this->logger = $logger;
        $this->filesystem = new Filesystem();
        $this->items = array_fill_keys(array_keys(self::DRUPAL_TYPE_KEY), []);
    }

    public function find(string $path, string $type) {
        $this->type = $type;
        $this->logger->debug($this->translator->trans('Init Drupal search in "{path}".', ['path' => $path]));
        if ($this->filesystem->exists($path.'/composer.json')) {
            $composerJson = file_get_contents($path.'/composer.json');
            $composerJson = json_decode($composerJson, true);
            if (!empty($composerJson['extra']['installer-paths'])) {
                foreach ($composerJson['extra']['installer-paths'] as $dir => $types) {
                    foreach (self::DRUPAL_TYPE_KEY as $extraType => $extraTypeDef) {
                        if (!in_array($extraTypeDef, $types)) {
                            continue;
                        }
                        $currentPath = $path.'/'.str_replace('/{$name}','', $dir);
                        if (!$this->filesystem->exists($currentPath)) {
                            $this->logger->debug($this->translator->trans('Init {extraType} with "{path}" ignored because directory doesn\'t exist.', ['extraType' => $extraType, 'path' => $currentPath]));
                            continue;
                        }
                        $this->directories[$extraType] = $currentPath;
                        $this->logger->debug($this->translator->trans('Init {extraType} with "{path}".', ['extraType' => $extraType, 'path' => $currentPath]));
                    }
                }
            }
        }
        foreach (self::DRUPAL_TYPE_KEY as $extraType => $extraTypeDef) {
            if (empty($this->directories[$extraType]) || !$this->filesystem->exists($this->directories[$extraType])) {
                $this->findDir($extraType, $path);
            }
        }
        if (empty($this->directories['core'])) {
            throw new Analyse($this->translator->trans('Cannot find Drupal in "{path}".', ['path' => $path]));
        }
        $this->findCoreData();
        $this->findItems();
    }

    protected function findDir(string $dirType, string $path) {
        if ($dirType === 'core') {
            if ($this->type === 'drupal8' && $this->filesystem->exists($path.'/core/lib/Drupal.php')) {
                $this->directories[$dirType] = $path.'/core';
            } elseif ($this->type === 'drupal7' && $this->filesystem->exists($path.'/includes/bootstrap.inc')) {
                $this->directories[$dirType] = $path;
            }
        }
        elseif ($this->type === 'drupal8') {
            if ($this->filesystem->exists($path.'/' . $dirType . '/contrib')) {
                $this->directories[$dirType] = $path.'/' . $dirType . '/contrib';
            }
            elseif ($this->filesystem->exists($path.'/' . $dirType)) {
                $this->directories[$dirType] = $path.'/' . $dirType;
            }
        }
        elseif ($this->type === 'drupal7') {
            if ($this->filesystem->exists($path.'/sites/all/' . $dirType . '/contrib')) {
                $this->directories[$dirType] = $path.'/sites/all/' . $dirType . '/contrib';
            }
            elseif ($this->filesystem->exists($path.'/sites/all/' . $dirType)) {
                $this->directories[$dirType] = $path.'/sites/all/' . $dirType;
            }
        }
    }

    protected function findCoreData() {
        if ($this->type === 'drupal8' && $this->filesystem->exists($this->directories['core'].'/lib/Drupal.php')) {
            $drupalClass = file_get_contents($this->directories['core'].'/lib/Drupal.php');
            preg_match('/const CORE_COMPATIBILITY = \'([0-9a-z\.]+)\';/i', $drupalClass, $matches);
            $this->compat = $matches[1];
            preg_match('/const VERSION = \'([0-9a-z\.\-]+)\';/i', $drupalClass, $matches);
            $this->version = $matches[1];
            $this->extension = '.info.yml';
        }
        elseif ($this->type === 'drupal7' && $this->filesystem->exists($this->directories['core'].'/includes/bootstrap.inc')) {
            $bootstrapInc = file_get_contents($this->directories['core'].'/includes/bootstrap.inc');
            preg_match('/define\(\'DRUPAL_CORE_COMPATIBILITY\', \'([0-9a-z\.]+)\'\);/i', $bootstrapInc, $matches);
            $this->compat = $matches[1];
            preg_match('/define\(\'VERSION\', \'([0-9a-z\.\-]+)\'\);/i', $bootstrapInc, $matches);
            $this->version = $matches[1];
            $this->extension = '.info';
        }

        if (empty($this->version)) {
            throw new Analyse($this->translator->trans('Cannot determine Drupal version.'));
        }

        if (preg_match('/^([0-9]+)/', $this->version, $matches) && substr($this->compat, 0, 1) < $matches[1]) {
            $this->compat = 'current';
        }
    }

    public function getCompat() {
        return $this->compat;
    }

    protected function findItems() {
        foreach ($this->directories as $dirType => $dir) {
            if ($dirType === 'core') {
                $this->items['core']['drupal'] = [
                    'name' => 'drupal',
                    'info' => [
                        'name' => 'Drupal core',
                        'type' => 'core',
                        'description' => 'Drupal core',
                        'version' => $this->version,
                        'core' => $this->compat,
                    ],
                    'project_type' => 'core',
                ];
                continue;
            }

            $this->scanDirectory($dir, $dirType);
            $this->logger->debug(print_r($this->items[$dirType], true));
        }
    }

    public function scanDirectory($dir, $dirType) {
        list(, $cond) = explode(':', self::DRUPAL_TYPE_KEY[$dirType]);
        $finder = (new Finder())
            ->directories()
            ->depth(0)
            ->sortByName()
            ->in($dir);
        foreach ($finder as $currentDir) {
            $composerFinder = (new Finder())
                ->files()
                ->name('composer.json')
                ->contains('/"type": "'.$cond.'"/')
                ->depth(0)
                ->in($currentDir->getRealPath());
            $infoFinder = (new Finder())
                ->files()
                ->name('*' . $this->extension)
                ->depth(0)
                ->in($currentDir->getRealPath());
            $subInfoFinder = (new Finder())
                ->files()
                ->name('*' . $this->extension)
                ->depth('> 0')
                ->in($currentDir->getRealPath());
            if (
                $infoFinder->count() > 0
            ) {
                $this->buildInfo($dirType, $currentDir, $infoFinder);
            }
            elseif (
                $composerFinder->count() > 0 &&
                $subInfoFinder->count() > 0
            ) {
                $this->buildInfo($dirType, $currentDir, $subInfoFinder);
            }
            else {
                $this->scanDirectory($currentDir->getRealPath(), $dirType);
            }
        }
    }

    public function buildInfo($dirType, SplFileInfo $currentDir, Finder $finder) {
        $iterator = $finder->getIterator();
        $iterator->rewind();
        $itemInfo = $iterator->current();
        $entry = $currentDir->getBasename();
        $this->items[$dirType][$entry] = [
            'name' => $entry,
        ];
        switch ($this->extension) {
            case '.info.yml':
                $this->items[$dirType][$entry]['info'] = Yaml::parse($itemInfo->getContents());
                break;
            case '.info':
                $this->items[$dirType][$entry]['info'] = $this->drupalParseInfoFile($itemInfo->getContents());
                break;
        }
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
    protected function drupalParseInfoFile($data)
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

    public function getItems(): array {
        return $this->items;
    }

}