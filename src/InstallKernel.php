<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class InstallKernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/var/cache/install/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir().'/var/log/install';
    }

    protected function configureContainer(ContainerConfigurator $containerConfigurator): void
    {
        $containerConfigurator->import('../config/install/{packages}/*.yaml');
        $containerConfigurator->import('../config/install/{packages}/'.$this->environment.'/*.yaml');

        if (is_file(\dirname(__DIR__).'/config/install/services.yaml')) {
            $containerConfigurator->import('../config/install/services.yaml');
            $containerConfigurator->import('../config/install/{services}_'.$this->environment.'.yaml');
        } else {
            $containerConfigurator->import('../config/install/{services}.php');
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../config/install/{routes}/'.$this->environment.'/*.yaml');
        $routes->import('../config/install/{routes}/*.yaml');
    }

    private function getBundlesPath(): string
    {
        return $this->getProjectDir().'/config/install/bundles.php';
    }

}
