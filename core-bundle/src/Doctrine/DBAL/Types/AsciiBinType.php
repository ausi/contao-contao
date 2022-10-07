<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

/**
 * Alias for ['type' => 'string', 'customSchemaOptions' => ['collation' => 'ascii_bin']].
 */
class AsciiBinType extends StringType
{
    final public const NAME = 'ascii_bin';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $charset = $platform->getColumnCharsetDeclarationSQL('ascii');
        $collation = $platform->getColumnCollationDeclarationSQL('ascii_bin');

        return parent::getSQLDeclaration($column, $platform)." $charset $collation";
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
