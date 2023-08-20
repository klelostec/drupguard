<?php

namespace App\Service;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

trait ExecutableFinderTrait
{
    protected function getPhpBinary(): string|null {
        $phpExecutableFinder = new PhpExecutableFinder();
        return $phpExecutableFinder->find(false) ?? null;
    }

    protected function getComposerBinary(int $composerVersion): string|null {
        $executableFinder = new ExecutableFinder();
        foreach (['', $composerVersion] as $v) {
            if ($composerPath = $executableFinder->find('composer' . $v)) {
                $composer = new Process([$composerPath, '--version']);
                try {
                    $composer->setTimeout(5);
                    $composer->run();
                    $output = $composer->getOutput();
                    if (
                        preg_match('/^Composer version (\d)\..*$/i', $output ?? '', $matches) &&
                        (string) $composerVersion === $matches[1]
                    ) {
                        return $composerPath;
                    }
                }
                catch (\Exception $e) {
                    // Only catch exception to prevent fatal errors during binaries detection
                }
            }
        }

        return null;
    }
}
