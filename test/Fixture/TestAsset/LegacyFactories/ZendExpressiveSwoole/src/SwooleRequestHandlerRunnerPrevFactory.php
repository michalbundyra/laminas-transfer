<?php

declare(strict_types=1);

namespace Zend\Expressive\Swoole;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Server as SwooleHttpServer;
use Zend\Expressive\ApplicationPipeline;
use Zend\Expressive\Response\ServerRequestErrorResponseGenerator;

class SwooleRequestHandlerRunnerPrevFactory
{
    public function __invoke(ContainerInterface $container) : SwooleRequestHandlerRunner
    {
        $logger = $container->has(Log\AccessLogInterface::class)
            ? $container->get(Log\AccessLogInterface::class)
            : null;

        return new SwooleRequestHandlerRunner(
            $container->get(ApplicationPipeline::class),
            $container->get(ServerRequestInterface::class),
            $container->get(ServerRequestErrorResponseGenerator::class),
            $container->get(PidManager::class),
            $container->get(SwooleHttpServer::class),
            $this->retrieveStaticResourceHandler($container),
            $logger
        );
    }

    private function retrieveStaticResourceHandler(ContainerInterface $container) :? StaticResourceHandlerInterface
    {
        $config = $container->get('config')['zend-expressive-swoole']['swoole-http-server']['static-files'];
        $enabled = isset($config['enable']) && true === $config['enable'];
        if ($enabled && $container->has(StaticResourceHandlerInterface::class)) {
            return $container->get(StaticResourceHandlerInterface::class);
        }
        return null;
    }
}
