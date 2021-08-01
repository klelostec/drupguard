<?php

namespace App\Service;

use Cz\Git\GitException;
use Cz\Git\GitRepository;

class GitHelper extends GitRepository
{
    /**
     * @param  string
     * @return array
     */
    public static function getRemoteBranchesWithoutCheckout($url)
    {
        if (DIRECTORY_SEPARATOR === '\\') { // Windows
            $env = 'set GIT_TERMINAL_PROMPT=0 &&';
        } else {
            $env = 'GIT_TERMINAL_PROMPT=0';
        }

        exec(self::processCommand(array(
                $env . ' git ls-remote',
                '--heads',
                '--quiet',
                '--exit-code',
                $url,
            )) . ' 2>&1', $output, $returnCode);

        $branches = [];
        if ($returnCode === 0) {
            foreach ($output as $current) {
                if (preg_match('#refs/heads/(.*)$#', $current, $matches)) {
                    $branches[$matches[1]] = $matches[1];
                }
            }
        } else {
            throw new GitException("An error occured during list remote branches in repository \"$url\" : \n" . implode(PHP_EOL, $output));
        }
        return $branches;
    }

    /**
     * @param  string
     * @return string
     */
    public static function getRemoteDefaultBrancheWithoutCheckout($url)
    {
        if (DIRECTORY_SEPARATOR === '\\') { // Windows
            $env = 'set GIT_TERMINAL_PROMPT=0 &&';
        } else {
            $env = 'GIT_TERMINAL_PROMPT=0';
        }

        exec(self::processCommand(array(
                $env . ' git ls-remote',
                '--symref',
                '--quiet',
                '--exit-code',
                $url,
                'HEAD'
            )) . ' 2>&1', $output, $returnCode);

        $branch = null;
        if ($returnCode === 0) {
            foreach ($output as $current) {
                if (preg_match('#refs/heads/(.*)\tHEAD$#', $current, $matches)) {
                    $branch = $matches[1];
                    break;
                }
            }
        } else {
            throw new GitException("An error occured during get default branch in repository \"$url\" : \n" . implode(PHP_EOL, $output));
        }
        return $branch;
    }

    public function reset($hard = false)
    {
        return $this->begin()
          ->run('git reset' . ($hard ? ' --hard' : ''))
          ->run('git clean -d -f')
          ->end();
    }
}
