<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\Database;
use Contao\Database\Result;
use Contao\Database\Statement;
use Contao\Model\Collection;
use Contao\Model\Registry;
use Contao\PageModel;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Symfony\Component\Filesystem\Filesystem;

class PageModelTest extends ContaoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $platform = $this->createMock(AbstractPlatform::class);
        $platform
            ->method('getIdentifierQuoteCharacter')
            ->willReturn('\'')
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('getDatabasePlatform')
            ->willReturn($platform)
        ;

        $connection
            ->method('quoteIdentifier')
            ->willReturnArgument(0)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('database_connection', $connection);
        $container->set('contao.security.token_checker', $this->createMock(TokenChecker::class));
        $container->setParameter('contao.resources_paths', $this->getTempDir());

        (new Filesystem())->mkdir($this->getTempDir().'/languages/en');

        System::setContainer($container);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Registry::getInstance()->reset();

        // Reset database instance
        $property = (new \ReflectionClass(Database::class))->getProperty('arrInstances');
        $property->setAccessible(true);
        $property->setValue([]);
    }

    public function testCreatingEmptyPageModel(): void
    {
        $pageModel = new PageModel();

        $this->assertNull($pageModel->id);
        $this->assertNull($pageModel->alias);
    }

    public function testCreatingPageModelFromArray(): void
    {
        $pageModel = new PageModel(['id' => '1', 'alias' => 'alias']);

        $this->assertSame('1', $pageModel->id);
        $this->assertSame('alias', $pageModel->alias);
    }

    public function testCreatingPageModelFromDatabaseResult(): void
    {
        $pageModel = new PageModel(new Result([['id' => '1', 'alias' => 'alias']], 'SELECT * FROM tl_page WHERE id = 1'));

        $this->assertSame('1', $pageModel->id);
        $this->assertSame('alias', $pageModel->alias);
    }

    public function testFindPublishedSubpagesWithoutGuestsByPid(): void
    {
        $databaseResultData = [
            ['id' => '1', 'alias' => 'alias1', 'subpages' => '0'],
            ['id' => '2', 'alias' => 'alias2', 'subpages' => '3'],
            ['id' => '3', 'alias' => 'alias3', 'subpages' => '42'],
        ];

        $statement = $this->createMock(Statement::class);
        $statement
            ->method('execute')
            ->willReturn(new Result($databaseResultData, ''))
        ;

        $database = $this->createMock(Database::class);
        $database
            ->method('prepare')
            ->willReturn($statement)
        ;

        $this->mockDatabase($database);

        $pages = PageModel::findPublishedSubpagesWithoutGuestsByPid(1);

        $this->assertInstanceOf(Collection::class, $pages);
        $this->assertSame(3, $pages->count());

        $this->assertSame($databaseResultData[0]['id'], $pages->offsetGet(0)->id);
        $this->assertSame($databaseResultData[0]['alias'], $pages->offsetGet(0)->alias);
        $this->assertSame($databaseResultData[0]['subpages'], $pages->offsetGet(0)->subpages);
        $this->assertSame($databaseResultData[1]['id'], $pages->offsetGet(1)->id);
        $this->assertSame($databaseResultData[1]['alias'], $pages->offsetGet(1)->alias);
        $this->assertSame($databaseResultData[1]['subpages'], $pages->offsetGet(1)->subpages);
        $this->assertSame($databaseResultData[2]['id'], $pages->offsetGet(2)->id);
        $this->assertSame($databaseResultData[2]['alias'], $pages->offsetGet(2)->alias);
        $this->assertSame($databaseResultData[2]['subpages'], $pages->offsetGet(2)->subpages);

        // Get from model registry
        $page2 = PageModel::findByPk(2);

        $this->assertSame($databaseResultData[1]['id'], $page2->id);
        $this->assertSame($databaseResultData[1]['alias'], $page2->alias);
        $this->assertNull($page2->subpages, 'The subpages values should not be set in the model registry as it is contains generated data based on the query');
    }

    private function mockDatabase(Database $database): void
    {
        $property = (new \ReflectionClass($database))->getProperty('arrInstances');
        $property->setAccessible(true);
        $property->setValue([md5(implode('', [])) => $database]);

        $this->assertSame($database, Database::getInstance());
    }
}
