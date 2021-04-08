<?php

namespace App\Service;

use Cz\Git\GitRepository;

class GitHelper extends GitRepository
{
    /**
     * @param  string
     * @param  array|NULL
     * @return bool
     */
    public static function getRemoteBranchesWithoutCheckout($url, array $refs = NULL)
    {
        $env = '';

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
            $refs,
          )) . ' 2>&1', $output, $returnCode);

        $branches = [];
        if($returnCode === 0) {
            foreach($output as $current) {
                if(preg_match('#refs/heads/(.*)$#', $current, $matches)) {
                    $branches[$matches[1]] = $matches[1];
                }
            }
        }
        return $branches;
    }
}