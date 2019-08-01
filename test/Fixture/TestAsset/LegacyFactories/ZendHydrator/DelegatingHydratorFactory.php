<?php

declare(strict_types=1);

namespace Zend\Hydrator;

use Psr\Container\ContainerInterface;

class DelegatingHydratorFactory
{
    /**
     * Creates DelegatingHydrator
     */
    public function __invoke(ContainerInterface $container) : DelegatingHydrator
    {
        $container = $this->marshalHydratorPluginManager($container);
        return new DelegatingHydrator($container);
    }

    /**
     * Locate and return a HydratorPluginManager instance.
     */
    private function marshalHydratorPluginManager(ContainerInterface $container) : ContainerInterface
    {
        // Already one? Return it.
        if ($container instanceof HydratorPluginManagerInterface) {
            return $container;
        }

        // As typically registered with v3 (FQCN)
        if ($container->has(HydratorPluginManager::class)) {
            return $container->get(HydratorPluginManager::class);
        }

        // As registered by zend-mvc
        if ($container->has('HydratorManager')) {
            return $container->get('HydratorManager');
        }

        // Fallback: create one
        return new HydratorPluginManager($container);
    }
}
