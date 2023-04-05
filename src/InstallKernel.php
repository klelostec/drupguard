<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class InstallKernel extends BaseKernel
{
    use MicroKernelTrait {
        getConfigDir as private getConfigDirTrait;
        getCacheDir as public getCacheDirTrait;
        getLogDir as public getLogDirTrait;
    }

    /**
     * Gets the path to the configuration directory.
     */
    private function getConfigDir(): string
    {
        return $this->getConfigDirTrait().'/install';
    }

    public function getCacheDir(): string
    {
        return $this->getCacheDirTrait().'/install';
    }

    public function getLogDir(): string
    {
        return $this->getLogDirTrait().'/install';
    }

}
