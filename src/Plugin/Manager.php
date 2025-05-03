<?php

namespace App\Plugin;

use App\Entity\Plugin\PluginInterface;
use App\Entity\Plugin\Type\TypeInterface;
use App\Plugin\Annotation\PluginInfo;
use App\Plugin\Annotation\TypeInfo;
use App\Plugin\Exception\PluginNotFound;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class Manager
{
    protected CacheInterface $cache;

    /**
     * @var PluginInfo[]
     */
    protected array $plugins;

    /**
     * @var TypeInfo[]
     */
    protected array $types;

    protected array $mapClass;

    public function __construct(KernelInterface $appKernel, CacheInterface $cache)
    {
        $this->cache = $cache;
        $data = $this->cache->get('app_plugins_data', function (ItemInterface $item) use ($appKernel): array {
            $finder = new Finder();
            $finder
                ->in($appKernel->getProjectDir().'/src/Plugin/Service')
                ->files()
                ->name('*.php')
            ;

            $mapClass = [];

            /**
             * @var TypeInfo[]
             */
            $types = [];

            /**
             * @var PluginInfo[]
             */
            $plugins = [];
            foreach ($finder as $file) {
                $className = str_replace('/', '\\', 'App/'.preg_replace('#^.*/src/(.*)$#', '$1', $file->getPath())).'\\'.$file->getFilenameWithoutExtension();
                if (!class_exists($className)) {
                    continue;
                }

                $reflection = new \ReflectionClass($className);
                $pluginAttributes = $reflection->getAttributes(PluginInfo::class);
                $hasPluginAttributes = count($pluginAttributes) > 0;
                $typeAttributes = $reflection->getAttributes(TypeInfo::class);
                $hasTypeAttributes = count($typeAttributes) > 0;

                if (!$hasPluginAttributes && !$hasTypeAttributes) {
                    continue;
                }

                /*
                 * @var TypeInfo|PluginInfo $instance
                 */
                if ($hasTypeAttributes) {
                    $instance = $typeAttributes[0]->newInstance();
                    if (!class_implements($instance->getEntityClass(), TypeInterface::class)) {
                        continue;
                    }
                    $types[$instance->getId()] = $instance;
                } else {
                    $instance = $pluginAttributes[0]->newInstance();
                    if (!class_implements($instance->getEntityClass(), PluginInterface::class)) {
                        continue;
                    }
                    $plugins[$instance->getId()] = $instance;
                }
                $instance->setServiceClass($className);
                $mapClass[$instance->getEntityClass()] = $instance;
                $mapClass[$instance->getRepositoryClass()] = $instance;
                $mapClass[$instance->getFormClass()] = $instance;
            }

            foreach ($types as $instance) {
                if (empty($plugins[$instance->getType()])) {
                    throw new PluginNotFound($instance->getType());
                }
                $plugins[$instance->getType()]->addType($instance);
            }

            return [
                'plugins' => $plugins,
                'types' => $types,
                'mapClass' => $mapClass,
            ];
        });

        $this->plugins = $data['plugins'];
        $this->types = $data['types'];
        $this->mapClass = $data['mapClass'];
    }

    /**
     * @return PluginInfo[]
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * @return TypeInfo[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function getRelatedObject(string $className): TypeInfo|PluginInfo|null
    {
        return $this->mapClass[$className] ?? null;
    }
}
