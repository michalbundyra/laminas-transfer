<?php

declare(strict_types=1);

namespace Zend\Expressive\Container;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Expressive\Handler\NotFoundHandler;
use Zend\Expressive\Template\TemplateRendererInterface;

class NotFoundHandlerFactory
{
    public function __invoke(ContainerInterface $container) : NotFoundHandler
    {
        $config   = $container->has('config') ? $container->get('config') : [];
        $renderer = $container->has(TemplateRendererInterface::class)
            ? $container->get(TemplateRendererInterface::class)
            : null;
        $template = $config['zend-expressive']['error_handler']['template_404']
            ?? NotFoundHandler::TEMPLATE_DEFAULT;
        $layout   = $config['zend-expressive']['error_handler']['layout']
            ?? NotFoundHandler::LAYOUT_DEFAULT;

        return new NotFoundHandler(
            $container->get(ResponseInterface::class),
            $renderer,
            $template,
            $layout
        );
    }
}
