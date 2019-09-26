<?php

namespace Zend\Mvc\I18n;

use Zend\Router\Http\TreeRouteStack;

class ConfigProvider
{
    /**
     * Provide dependency configuration for an application integrating i18n.
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

    /**
     * Provide dependency configuration for an application integrating i18n.
     *
     * @return array
     */
    public function getDependencyConfig()
    {
        return [
            'aliases' => [
                'MvcTranslator' => Translator::class,
            ],
            'delegators' => [
                'HttpRouter' => [ Router\HttpRouterDelegatorFactory::class ],
                TreeRouteStack::class => [ Router\HttpRouterDelegatorFactory::class ],
            ],
            'factories' => [
                Translator::class => TranslatorFactory::class,
            ],
        ];
    }
}
