<?php
/**
 * Manage multiple applications with one kernel (install and main).
 * See https://symfony.com/doc/current/configuration/multiple_kernels.html.
 *
 * This Kernel manage the installation redirection if app has not been installed.
 */

namespace App;

use App\Exception\InstallException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Install\Entity\Install;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;
    use InstallerRedirectTrait;

    private string $id;
    private bool $preventInstallRedirect = false;

    public function __construct(string $environment, bool $debug, string $id)
    {
        parent::__construct($environment, $debug);
        $this->id = $id;
    }

    /**
     * @return bool
     */
    public function isPreventInstallRedirect(): bool
    {
        return $this->preventInstallRedirect;
    }

    /**
     * @param bool $preventInstallRedirect
     * @return Kernel
     */
    public function setPreventInstallRedirect(bool $preventInstallRedirect): Kernel
    {
        $this->preventInstallRedirect = $preventInstallRedirect;
        return $this;
    }

    public function getConfigDir(): string
    {
        return $this->getProjectDir().($this->id != 'main' ? '/apps/'.$this->id : '').'/config';
    }

    public function registerBundles(): iterable
    {
        $appBundles = require $this->getConfigDir().'/bundles.php';

        // load common bundles, such as the FrameworkBundle, as well as
        // specific bundles required exclusively for the app itself
        foreach ($appBundles as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    public function getCacheDir(): string
    {
        // divide cache for each application
        return ($_SERVER['APP_CACHE_DIR'] ?? $this->getProjectDir().'/var/cache').($this->id ? '/'.$this->id : '').'/'.$this->environment;
    }

    public function getLogDir(): string
    {
        // divide logs for each application
        return ($_SERVER['APP_LOG_DIR'] ?? $this->getProjectDir().'/var/log').($this->id ? '/'.$this->id : '');
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $this->doConfigureContainer($container, $this->getConfigDir());
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $this->doConfigureRoutes($routes, $this->getConfigDir());
    }

    private function doConfigureContainer(ContainerConfigurator $container, string $configDir): void
    {
        $container->import($configDir.'/{packages}/*.{php,yaml}');
        $container->import($configDir.'/{packages}/'.$this->environment.'/*.{php,yaml}');

        if (is_file($configDir.'/services.yaml')) {
            $container->import($configDir.'/services.yaml');
            $container->import($configDir.'/{services}_'.$this->environment.'.yaml');
        } else {
            $container->import($configDir.'/{services}.php');
        }
    }

    private function doConfigureRoutes(RoutingConfigurator $routes, string $configDir): void
    {
        $routes->import($configDir.'/{routes}/'.$this->environment.'/*.{php,yaml}');
        $routes->import($configDir.'/{routes}/*.{php,yaml}');

        if (is_file($configDir.'/routes.yaml')) {
            $routes->import($configDir.'/routes.yaml');
        } else {
            $routes->import($configDir.'/{routes}.php');
        }

        if (false !== ($fileName = (new \ReflectionObject($this))->getFileName())) {
            $routes->import($fileName, 'annotation');
        }
    }

    public function boot(): void
    {
        parent::boot();
        if (!$this->isCli() && !$this->isPreventInstallRedirect() && $this->container->has('database_connection')) {
            /**
             * @var Connection $connection
             */
            $connection = $this->container->get('database_connection');
            try {
                if(!$connection->createSchemaManager()->tablesExist([Install::TABLE_CHECK_INSTALLER])) {
                    throw new InstallException();
                }
            }
            catch (Exception $e) {
                throw new InstallException();
            }
        }

    }

    public function handle(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST, bool $catch = true): Response
    {
        try {
            return parent::handle($request, $type, $catch);
        }
        catch (\Exception $e) {
            if ($catch === FALSE) {
                throw $e;
            }

            return $this->handleException($e, $request, $type);
        }
    }

    /**
     * Converts an exception into a response.
     *
     * @param \Exception $e
     *   An exception
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   A Request instance
     * @param int $type
     *   The type of the request (one of HttpKernelInterface::MASTER_REQUEST or
     *   HttpKernelInterface::SUB_REQUEST)
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   A Response instance
     *
     * @throws \Exception
     *   If the passed in exception cannot be turned into a response.
     */
    protected function handleException(\Exception $e, $request, $type) {
        if ($this->shouldRedirectToInstaller($e)) {
            return new RedirectResponse($request->getBasePath() . '/install', 302, ['Cache-Control' => 'no-cache']);
        }

        throw $e;
    }
}
