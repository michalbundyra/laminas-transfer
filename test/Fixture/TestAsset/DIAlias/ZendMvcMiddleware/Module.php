<?php

namespace Zend\Mvc\Middleware;

use Zend\Mvc\MiddlewareListener as DeprecatedMiddlewareListener;
use Zend\ServiceManager\Factory\InvokableFactory;

class Module
{
    /**
     * @return array
     */
    public function getConfig()
    {
        return [
            'service_manager' => [
                'aliases' => [
                    DeprecatedMiddlewareListener::class => MiddlewareListener::class,
                ],
                'factories' => [
                    MiddlewareListener::class => InvokableFactory::class,
                ],
            ],
        ];
    }
}
