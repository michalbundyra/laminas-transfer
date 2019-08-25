<?php

declare(strict_types=1);

namespace ZendTest\Expressive\Authorization\Rbac;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Authorization\Exception;
use Zend\Expressive\Authorization\Rbac\ZendRbac;
use Zend\Expressive\Authorization\Rbac\ZendRbacAssertionInterface;
use Zend\Expressive\Authorization\Rbac\ZendRbacFactory;

class ZendRbacFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    protected function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function testFactoryWithoutConfig()
    {
        $this->container->get('config')->willReturn([]);

        $factory = new ZendRbacFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $this->expectExceptionMessage('zend-expressive-authorization-rbac');
        $factory($this->container->reveal());
    }

    public function testFactoryWithoutZendRbacConfig()
    {
        $this->container->get('config')->willReturn(['zend-expressive-authorization-rbac' => []]);

        $factory = new ZendRbacFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $this->expectExceptionMessage('zend-expressive-authorization-rbac.roles');
        $factory($this->container->reveal());
    }

    public function testFactoryWithoutPermissions()
    {
        $this->container->get('config')->willReturn([
            'zend-expressive-authorization-rbac' => [
                'roles' => []
            ]
        ]);

        $factory = new ZendRbacFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $this->expectExceptionMessage('zend-expressive-authorization-rbac.permissions');
        $factory($this->container->reveal());
    }

    public function testFactoryWithEmptyRolesPermissionsWithoutAssertion()
    {
        $this->container->get('config')->willReturn([
            'zend-expressive-authorization-rbac' => [
                'roles' => [],
                'permissions' => []
            ]
        ]);
        $this->container->has(ZendRbacAssertionInterface::class)->willReturn(false);

        $factory = new ZendRbacFactory();
        $zendRbac = $factory($this->container->reveal());
        $this->assertInstanceOf(ZendRbac::class, $zendRbac);
    }

    public function testFactoryWithEmptyRolesPermissionsWithAssertion()
    {
        $this->container->get('config')->willReturn([
            'zend-expressive-authorization-rbac' => [
                'roles' => [],
                'permissions' => []
            ]
        ]);

        $assertion = $this->prophesize(ZendRbacAssertionInterface::class);
        $this->container->has(ZendRbacAssertionInterface::class)->willReturn(true);
        $this->container->get(ZendRbacAssertionInterface::class)->willReturn($assertion->reveal());

        $factory = new ZendRbacFactory();
        $zendRbac = $factory($this->container->reveal());
        $this->assertInstanceOf(ZendRbac::class, $zendRbac);
    }

    public function testFactoryWithoutAssertion()
    {
        $this->container->get('config')->willReturn([
            'zend-expressive-authorization-rbac' => [
                'roles' => [
                    'administrator' => [],
                    'editor'        => ['administrator'],
                    'contributor'   => ['editor'],
                ],
                'permissions' => [
                    'contributor' => [
                        'admin.dashboard',
                        'admin.posts',
                    ],
                    'editor' => [
                        'admin.publish',
                    ],
                    'administrator' => [
                        'admin.settings',
                    ],
                ],
            ],
        ]);
        $this->container->has(ZendRbacAssertionInterface::class)->willReturn(false);

        $factory = new ZendRbacFactory();
        $zendRbac = $factory($this->container->reveal());
        $this->assertInstanceOf(ZendRbac::class, $zendRbac);
    }

    public function testFactoryWithAssertion()
    {
        $this->container->get('config')->willReturn([
            'zend-expressive-authorization-rbac' => [
                'roles' => [
                    'administrator' => [],
                    'editor'        => ['administrator'],
                    'contributor'   => ['editor'],
                ],
                'permissions' => [
                    'contributor' => [
                        'admin.dashboard',
                        'admin.posts',
                    ],
                    'editor' => [
                        'admin.publish',
                    ],
                    'administrator' => [
                        'admin.settings',
                    ],
                ],
            ],
        ]);
        $assertion = $this->prophesize(ZendRbacAssertionInterface::class);
        $this->container->has(ZendRbacAssertionInterface::class)->willReturn(true);
        $this->container->get(ZendRbacAssertionInterface::class)->willReturn($assertion->reveal());

        $factory = new ZendRbacFactory();
        $zendRbac = $factory($this->container->reveal());
        $this->assertInstanceOf(ZendRbac::class, $zendRbac);
    }

    public function testFactoryWithInvalidRole()
    {
        $this->container->get('config')->willReturn([
            'zend-expressive-authorization-rbac' => [
                'roles' => [
                    1 => [],
                ],
                'permissions' => [],
            ],
        ]);
        $this->container->has(ZendRbacAssertionInterface::class)->willReturn(false);

        $factory = new ZendRbacFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $factory($this->container->reveal());
    }

    public function testFactoryWithUnknownRole()
    {
        $this->container->get('config')->willReturn([
            'zend-expressive-authorization-rbac' => [
                'roles' => [
                    'administrator' => [],
                ],
                'permissions' => [
                    'contributor' => [
                        'admin.dashboard',
                        'admin.posts',
                    ]
                ]
            ]
        ]);
        $this->container->has(ZendRbacAssertionInterface::class)->willReturn(false);

        $factory = new ZendRbacFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $factory($this->container->reveal());
    }
}
