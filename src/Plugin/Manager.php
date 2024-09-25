<?php

namespace App\Plugin;

use App\Plugin\Entity\PluginInterface;
use App\Plugin\Entity\Type\TypeInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class Manager
{
    protected CacheInterface $cache;

    /**
     * @var PluginInfo[]
     */
    protected array $plugins;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getPluginInfo(string $id): ?PluginInfo
    {
        return $this->getPluginInfos()[$id] ?? null;
    }

    public function getTypeInfo(string $type, string $id): ?TypeInfo
    {
        $plugin = $this->getPluginInfos()[$type] ?? null;
        if (null === $plugin) {
            return null;
        }

        return $plugin->getType($id);
    }

    /**
     * @return PluginInfo[]
     */
    public function getPluginInfos(): array
    {
        if (!empty($this->plugins)) {
            return $this->plugins;
        }

        $this->plugins = $this->cache->get('source_plugin_data', function (ItemInterface $item): array {
            $finder = new Finder();
            $finder
                ->in(__DIR__.'/Entity/')
                ->files()
                ->name('*.php')
            ;
            $data = [];
            foreach ($finder as $file) {
                $className = $file->getBasename('.php');
                $plugin = static::buildInfo($className);
                $subDir = __DIR__.'/Entity/Type/'.$className;
                if (!($plugin instanceof PluginInfo) || !is_dir($subDir)) {
                    continue;
                }
                $data[$plugin->getId()] = $plugin;

                $subFinder = new Finder();
                $subFinder
                    ->in($subDir)
                    ->files()
                    ->name('*.php')
                ;
                foreach ($subFinder as $subFile) {
                    $subClassName = $subFile->getBasename('.php');
                    $type = static::buildInfo($subClassName, $className);
                    if (!($type instanceof TypeInfo)) {
                        continue;
                    }
                    $plugin->addType($type);
                }

                $data[$plugin->getId()] = $plugin;
            }

            return $data;
        });

        return $this->plugins;
    }

    protected static function buildInfo(string $className, ?string $type = null): PluginInfo|TypeInfo|null
    {
        $entityClass = 'App\\Plugin\\Entity\\'.($type ? 'Type\\'.$type.'\\' : '').$className;
        if (!class_exists($entityClass)) {
            return null;
        }

        $reflector = new \ReflectionClass($entityClass);
        if (
            !$reflector->implementsInterface($type ? TypeInterface::class : PluginInterface::class)
            || $reflector->isAbstract()
            || $reflector->isInterface()
        ) {
            return null;
        }

        $idPlugin = mb_strtolower($className);
        if ($type) {
            $returnClass = new TypeInfo($idPlugin, $className);
        } else {
            $returnClass = new PluginInfo($idPlugin, $className);
        }

        $returnClass
            ->setEntity($entityClass);

        foreach (['Form', 'Repository'] as $entityType) {
            $entityTypeClass = 'App\\Plugin\\'.$entityType.'\\'.($type ? 'Type\\'.$type.'\\' : '').$className;
            if (!class_exists($entityClass)) {
                continue;
            }
            $returnClass->{'set'.$entityType}($entityTypeClass);
        }

        return $returnClass;
    }
}
