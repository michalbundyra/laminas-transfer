<?php

declare(strict_types=1);

namespace Zend\Expressive\Router;

use Psr\Container\ContainerInterface;

/**
 * Create and return a RouteCollector instance.
 *
 * This factory depends on one other service:
 *
 * - Zend\Expressive\Router\RouterInterface, which should resolve to
 *   a class implementing that interface.
 */
class RouteCollectorFactory
{
    /**
     * @throws Exception\MissingDependencyException if the RouterInterface service is
     *     missing.
     */
    public function __invoke(ContainerInterface $container) : RouteCollector
    {
        if (! $container->has(RouterInterface::class)) {
            throw Exception\MissingDependencyException::dependencyForService(
                RouterInterface::class,
                RouteCollector::class
            );
        }

        return new RouteCollector($container->get(RouterInterface::class));
    }
}
