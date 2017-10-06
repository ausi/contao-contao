<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Framework;

use Contao\Config;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Exception\IncompleteInstallationException;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\Fixtures\Adapter\LegacyClass;
use Contao\CoreBundle\Tests\Fixtures\Adapter\LegacySingletonClass;
use Contao\CoreBundle\Tests\TestCase;
use Contao\RequestToken;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

/**
 * @preserveGlobalState disabled
 */
class ContaoFrameworkTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $framework = $this->mockContaoFramework(
            new RequestStack(),
            $this->mockRouter('/')
        );

        $this->assertInstanceOf('Contao\CoreBundle\Framework\ContaoFramework', $framework);
        $this->assertInstanceOf('Contao\CoreBundle\Framework\ContaoFrameworkInterface', $framework);
    }

    /**
     * @runInSeparateProcess
     */
    public function testInitializesTheFrameworkWithAFrontEndRequest(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $container = $this->mockContainerWithContaoScopes();
        $container->get('request_stack')->push($request);

        $framework = $this->mockContaoFramework($container->get('request_stack'), $this->mockRouter('/index.html'));
        $framework->setContainer($container);
        $framework->initialize();

        $this->assertTrue(\defined('TL_MODE'));
        $this->assertTrue(\defined('TL_START'));
        $this->assertTrue(\defined('TL_ROOT'));
        $this->assertTrue(\defined('TL_REFERER_ID'));
        $this->assertTrue(\defined('TL_SCRIPT'));
        $this->assertFalse(\defined('BE_USER_LOGGED_IN'));
        $this->assertFalse(\defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('TL_PATH'));
        $this->assertSame('FE', TL_MODE);
        $this->assertSame($this->getRootDir(), TL_ROOT);
        $this->assertSame('', TL_REFERER_ID);
        $this->assertSame('index.html', TL_SCRIPT);
        $this->assertSame('', TL_PATH);
        $this->assertSame('en', $GLOBALS['TL_LANGUAGE']);
        $this->assertInstanceOf('Contao\CoreBundle\Session\Attribute\ArrayAttributeBag', $_SESSION['BE_DATA']);
        $this->assertInstanceOf('Contao\CoreBundle\Session\Attribute\ArrayAttributeBag', $_SESSION['FE_DATA']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testInitializesTheFrameworkWithABackEndRequest(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);
        $request->attributes->set('_contao_referer_id', 'foobar');
        $request->setLocale('de');

        $container = $this->mockContainerWithContaoScopes();
        $container->get('request_stack')->push($request);

        $framework = $this->mockContaoFramework($container->get('request_stack'), $this->mockRouter('/contao/login'));
        $framework->setContainer($container);
        $framework->initialize();

        $this->assertTrue(\defined('TL_MODE'));
        $this->assertTrue(\defined('TL_START'));
        $this->assertTrue(\defined('TL_ROOT'));
        $this->assertTrue(\defined('TL_REFERER_ID'));
        $this->assertTrue(\defined('TL_SCRIPT'));
        $this->assertTrue(\defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('TL_PATH'));
        $this->assertSame('BE', TL_MODE);
        $this->assertSame($this->getRootDir(), TL_ROOT);
        $this->assertSame('foobar', TL_REFERER_ID);
        $this->assertSame('contao/login', TL_SCRIPT);
        $this->assertSame('', TL_PATH);
        $this->assertSame('de', $GLOBALS['TL_LANGUAGE']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testInitializesTheFrameworkWithoutARequest(): void
    {
        $container = $this->mockContainerWithContaoScopes();
        $container->set('request_stack', new RequestStack());

        $framework = $this->mockContaoFramework($container->get('request_stack'), $this->mockRouter('/contao/login'));
        $framework->setContainer($container);
        $framework->initialize();

        $this->assertTrue(\defined('TL_MODE'));
        $this->assertTrue(\defined('TL_START'));
        $this->assertTrue(\defined('TL_ROOT'));
        $this->assertTrue(\defined('TL_REFERER_ID'));
        $this->assertTrue(\defined('TL_SCRIPT'));
        $this->assertTrue(\defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('TL_PATH'));
        $this->assertNull(TL_MODE);
        $this->assertSame($this->getRootDir(), TL_ROOT);
        $this->assertNull(TL_REFERER_ID);
        $this->assertSame(null, TL_SCRIPT);
        $this->assertNull(TL_PATH);
    }

    /**
     * @runInSeparateProcess
     */
    public function testInitializesTheFrameworkWithoutARoute(): void
    {
        $request = new Request();
        $request->setLocale('de');

        $routingLoader = $this->createMock(LoaderInterface::class);

        $routingLoader
            ->method('load')
            ->willReturn(new RouteCollection())
        ;

        $container = $this->mockContainerWithContaoScopes();
        $container->get('request_stack')->push($request);
        $container->set('routing.loader', $routingLoader);

        $framework = $this->mockContaoFramework($container->get('request_stack'), new Router($container, []));
        $framework->setContainer($container);
        $framework->initialize();

        $this->assertTrue(\defined('TL_MODE'));
        $this->assertTrue(\defined('TL_START'));
        $this->assertTrue(\defined('TL_ROOT'));
        $this->assertTrue(\defined('TL_REFERER_ID'));
        $this->assertTrue(\defined('TL_SCRIPT'));
        $this->assertTrue(\defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('TL_PATH'));
        $this->assertSame(null, TL_MODE);
        $this->assertSame($this->getRootDir(), TL_ROOT);
        $this->assertSame('', TL_REFERER_ID);
        $this->assertSame(null, TL_SCRIPT);
        $this->assertSame('', TL_PATH);
        $this->assertSame('de', $GLOBALS['TL_LANGUAGE']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testInitializesTheFrameworkWithoutAScope(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_contao_referer_id', 'foobar');

        $container = $this->mockContainerWithContaoScopes();
        $container->get('request_stack')->push($request);

        $framework = $this->mockContaoFramework($container->get('request_stack'), $this->mockRouter('/contao/login'));
        $framework->setContainer($container);
        $framework->initialize();

        $this->assertTrue(\defined('TL_MODE'));
        $this->assertTrue(\defined('TL_START'));
        $this->assertTrue(\defined('TL_ROOT'));
        $this->assertTrue(\defined('TL_REFERER_ID'));
        $this->assertTrue(\defined('TL_SCRIPT'));
        $this->assertTrue(\defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('TL_PATH'));
        $this->assertNull(TL_MODE);
        $this->assertSame($this->getRootDir(), TL_ROOT);
        $this->assertSame('foobar', TL_REFERER_ID);
        $this->assertSame('contao/login', TL_SCRIPT);
        $this->assertSame('', TL_PATH);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDoesNotInitializeTheFrameworkTwice(): void
    {
        $request = new Request();
        $request->attributes->set('_contao_referer_id', 'foobar');

        $container = $this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);
        $container->setParameter('contao.csrf_token_name', 'dummy_token');
        $container->set('security.csrf.token_manager', new CsrfTokenManager());

        // Ensure to use the fixtures class
        Config::preload();

        $framework = $this->createMock(ContaoFramework::class);

        $framework
            ->method('isInitialized')
            ->willReturnOnConsecutiveCalls(false, true)
        ;

        $framework
            ->method('getAdapter')
            ->with($this->equalTo(Config::class))
            ->willReturn($this->mockConfigAdapter())
        ;

        $framework->setContainer($container);
        $framework->initialize();
        $framework->initialize();

        $this->addToAssertionCount(1);  // does not throw an exception
    }

    /**
     * @runInSeparateProcess
     */
    public function testOverridesTheErrorLevel(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_contao_referer_id', 'foobar');

        $container = $this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $framework = $this->mockContaoFramework($container->get('request_stack'), $this->mockRouter('/contao/login'));
        $framework->setContainer($container);

        $errorReporting = error_reporting();
        error_reporting(E_ALL ^ E_USER_NOTICE);

        $this->assertNotSame(
            $errorReporting,
            error_reporting(),
            'Test is invalid, error level has not changed.'
        );

        $framework->initialize();

        $this->assertSame($errorReporting, error_reporting());

        error_reporting($errorReporting);
    }

    /**
     * @runInSeparateProcess
     */
    public function testValidatesTheRequestToken(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_token_check', true);
        $request->setMethod('POST');
        $request->request->set('REQUEST_TOKEN', 'foobar');

        $container = $this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $framework = $this->mockContaoFramework(
            $container->get('request_stack'),
            $this->mockRouter('/contao/login')
        );

        $framework->setContainer($container);
        $framework->initialize();

        $this->addToAssertionCount(1);  // does not throw an exception
    }

    /**
     * @runInSeparateProcess
     */
    public function testFailsIfTheRequestTokenIsInvalid(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_token_check', true);
        $request->setMethod('POST');
        $request->request->set('REQUEST_TOKEN', 'invalid');

        $container = $this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $adapter = $this->createMock(Adapter::class);

        $adapter
            ->method('__call')
            ->willReturn(false)
        ;

        $framework = $this->mockContaoFramework(
            $container->get('request_stack'),
            null,
            [RequestToken::class => $adapter]
        );

        $this->expectException(InvalidRequestTokenException::class);

        $framework->setContainer($container);
        $framework->initialize();
    }

    /**
     * @runInSeparateProcess
     */
    public function testDoesNotValidateTheRequestTokenUponAjaxRequests(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_token_check', true);
        $request->setMethod('POST');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $container = $this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $adapter = $this->createMock(Adapter::class);

        $adapter
            ->expects($this->never())
            ->method('__call')
        ;

        $framework = $this->mockContaoFramework(
            $container->get('request_stack'),
            null,
            [RequestToken::class => $adapter]
        );

        $framework->setContainer($container);
        $framework->initialize();

        $this->addToAssertionCount(1);  // does not throw an exception
    }

    /**
     * @runInSeparateProcess
     */
    public function testDoesNotValidateTheRequestTokenIfTheRequestAttributeIsFalse(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_token_check', false);
        $request->setMethod('POST');
        $request->request->set('REQUEST_TOKEN', 'foobar');

        $container = $this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $adapter = $this->createMock(Adapter::class);

        $adapter
            ->expects($this->never())
            ->method('__call')
            ->with('validate')
        ;

        $adapter
            ->method('__call')
            ->willReturnCallback(
                function (string $key): ?string {
                    if ('get' === $key) {
                        return 'foobar';
                    }

                    return null;
                }
            )
        ;

        $framework = $this->mockContaoFramework(
            $container->get('request_stack'),
            null,
            [RequestToken::class => $adapter]
        );

        $framework->setContainer($container);
        $framework->initialize();
    }

    /**
     * @runInSeparateProcess
     */
    public function testFailsIfTheInstallationIsIncomplete(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');

        $container = $this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $adapter = $this->createMock(Adapter::class);

        $adapter
            ->method('__call')
            ->willReturnCallback(
                function (string $key, array $params) {
                    if ('isComplete' === $key) {
                        return false;
                    }

                    if ('get' === $key) {
                        switch ($params[0]) {
                            case 'characterSet':
                                return 'UTF-8';

                            case 'timeZone':
                                return 'Europe/Berlin';
                        }
                    }

                    return null;
                }
            )
        ;

        $framework = $this->mockContaoFramework(
            $container->get('request_stack'),
            $this->mockRouter('/contao/login'),
            [Config::class => $adapter]
        );

        $this->expectException(IncompleteInstallationException::class);

        $framework->setContainer($container);
        $framework->initialize();
    }

    /**
     * @param string $route
     *
     * @runInSeparateProcess
     * @dataProvider getInstallRoutes
     */
    public function testAllowsTheInstallationToBeIncompleteInTheInstallTool($route): void
    {
        $request = new Request();
        $request->attributes->set('_route', $route);

        $container = $this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $adapter = $this->createMock(Adapter::class);

        $adapter
            ->method('__call')
            ->willReturnCallback(
                function (string $key, array $params) {
                    if ('isComplete' === $key) {
                        return false;
                    }

                    if ('get' === $key) {
                        switch ($params[0]) {
                            case 'characterSet':
                                return 'UTF-8';

                            case 'timeZone':
                                return 'Europe/Berlin';
                        }
                    }

                    return null;
                }
            )
        ;

        $framework = $this->mockContaoFramework(
            $container->get('request_stack'),
            $this->mockRouter('/contao/install'),
            [Config::class => $adapter]
        );

        $framework->setContainer($container);
        $framework->initialize();

        $this->addToAssertionCount(1);  // does not throw an exception
    }

    /**
     * @return array
     */
    public function getInstallRoutes(): array
    {
        return [
            'contao_install' => ['contao_install'],
            'contao_install_redirect' => ['contao_install_redirect'],
        ];
    }

    /**
     * @runInSeparateProcess
     */
    public function testFailsIfTheContainerIsNotSet(): void
    {
        $framework = $this->mockContaoFramework(
            new RequestStack(),
            $this->mockRouter('/contao/login')
        );

        $this->expectException('LogicException');

        $framework->setContainer();
        $framework->initialize();
    }

    public function testCreatesAnObjectInstance(): void
    {
        $reflection = new \ReflectionClass(ContaoFramework::class);
        $framework = $reflection->newInstanceWithoutConstructor();

        $class = LegacyClass::class;
        $instance = $framework->createInstance($class, [1, 2]);

        $this->assertInstanceOf($class, $instance);
        $this->assertSame([1, 2], $instance->constructorArgs);
    }

    public function testCreateASingeltonObjectInstance(): void
    {
        $reflection = new \ReflectionClass(ContaoFramework::class);
        $framework = $reflection->newInstanceWithoutConstructor();

        $class = LegacySingletonClass::class;
        $instance = $framework->createInstance($class, [1, 2]);

        $this->assertInstanceOf($class, $instance);
        $this->assertSame([1, 2], $instance->constructorArgs);
    }

    public function testCreatesAdaptersForLegacyClasses(): void
    {
        $class = LegacyClass::class;

        $reflection = new \ReflectionClass(ContaoFramework::class);
        $framework = $reflection->newInstanceWithoutConstructor();
        $adapter = $framework->getAdapter($class);

        $this->assertInstanceOf('Contao\CoreBundle\Framework\Adapter', $adapter);

        $ref = new \ReflectionClass($adapter);
        $prop = $ref->getProperty('class');
        $prop->setAccessible(true);

        $this->assertSame($class, $prop->getValue($adapter));
    }
}
