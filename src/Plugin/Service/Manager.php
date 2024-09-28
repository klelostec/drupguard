<?php

namespace App\Plugin\Service;

use App\Entity\Plugin\PluginInterface;
use App\Entity\Plugin\Type\TypeInterface;
use App\Plugin\Exception\PluginNotFound;
use App\Plugin\PluginInfo;
use App\Plugin\TypeInfo;
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

    /**
     * @var array
     */
    protected array $mapClass;

    public function __construct(KernelInterface $appKernel, CacheInterface $cache)
    {
        $this->cache = $cache;
        $data = $this->cache->get('app_plugins_data', function (ItemInterface $item) use ($appKernel): array {
            $finder = new Finder();
            $finder
                ->in($appKernel->getProjectDir().'/src/Entity/Plugin')
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
                $className = str_replace('/', '\\', 'App/' . preg_replace('#^.*/src/(.*)$#', '$1', $file->getPath())) . '\\' . $file->getFilenameWithoutExtension();
                if (!class_exists($className)) {
                    continue;
                }

                $reflection = new \ReflectionClass($className);
                $pluginAttributes = $reflection->getAttributes(PluginInfo::class);
                $hasPluginAttributes = count($pluginAttributes) > 0;

                $typeAttributes = $reflection->getAttributes(TypeInfo::class);
                $hasTypeAttributes = count($typeAttributes) > 0;
                if (
                    !$reflection->isInstantiable() ||
                    (
                        !$reflection->implementsInterface(PluginInterface::class) &&
                        !$reflection->implementsInterface(TypeInterface::class)
                    ) ||
                    (
                        !$hasPluginAttributes &&
                        !$hasTypeAttributes
                    )
                ) {
                    continue;
                }

                /**
                 * @var TypeInfo|PluginInfo $instance
                 */
                if ($hasTypeAttributes) {
                    $instance = $typeAttributes[0]->newInstance();
                    $types[$instance->getId()] = $instance;
                }
                else {
                    $instance = $pluginAttributes[0]->newInstance();
                    $plugins[$instance->getId()] = $instance;
                }

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
    public function getPlugins(): array {
        return $this->plugins;
    }

    /**
     * @return TypeInfo[]
     */
    public function getTypes(): array {
        return $this->types;
    }

    public function getRelatedObject(string $className) :null|TypeInfo|PluginInfo {
        return $this->mapClass[$className] ?? null;
    }
}
