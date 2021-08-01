<?php

namespace App\Twig;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private $container;
    private $publicDir;

    public function __construct(ContainerInterface $container, string $publicDir)
    {
        $this->container = $container;
        $this->publicDir = $publicDir;
    }

    public function getFunctions(): array
    {
        return [
          new TwigFunction('encore_entry_css_source', [$this, 'getEncoreEntryCssSource']),
        ];
    }

    public function getEncoreEntryCssSource(string $entryName): string
    {
        $files = $this->container
          ->get(EntrypointLookupInterface::class)
          ->getCssFiles($entryName);

        $source = '';
        foreach ($files as $file) {
            $source .= file_get_contents($this->publicDir.'/'.$file);
        }

        return $source;
    }

    public static function getSubscribedServices()
    {
        return [
          EntrypointLookupInterface::class,
        ];
    }
}
