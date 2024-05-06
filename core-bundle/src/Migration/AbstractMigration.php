<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration;

use Doctrine\DBAL\Connection;

abstract class AbstractMigration implements MigrationInterface
{
    public function getName(): string
    {
        return static::class;
    }

    protected function createResult(bool $successful, string $message = null): MigrationResult
    {
        $message ??= $this->getName().' '.($successful ? 'executed successfully' : 'execution failed');

        return new MigrationResult($successful, $message);
    }

    /**
     * @param list<string> $columns
     */
    protected function columnsExist(Connection $connection, string $table, array $columns): bool
    {
        $columns = array_map('strtolower', $columns);
        $existingColumns = $this->listColumns($connection, $table);

        return \count(array_intersect($columns, $existingColumns)) === \count($columns);
    }

    /**
     * @param list<string> $columns
     */
    protected function tableExistsWithoutColumns(Connection $connection, string $table, array $columns): bool
    {
        if (!$connection->createSchemaManager()->tablesExist([$table])) {
            return false;
        }

        $columns = array_map('strtolower', $columns);
        $existingColumns = $this->listColumns($connection, $table);

        return 0 === \count(array_intersect($columns, $existingColumns));
    }

    /**
     * @return list<string>
     */
    protected function listColumns(Connection $connection, string $table): array
    {
        $schemaColumns = $connection->createSchemaManager()->listTableColumns($table);
        $existingColumns = [];

        foreach ($schemaColumns as $schemaColumn) {
            $existingColumns[] = strtolower($schemaColumn->getName());
        }

        return $existingColumns;
    }
}
