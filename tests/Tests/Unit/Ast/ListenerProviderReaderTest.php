<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Unit\Ast;

use Sindri\Ast\ListenerProviderReader;
use Sindri\Tests\Fixtures\Event\TestListenerFixture;
use Sindri\Tests\Unit\Abstract\TestCase;

use function file_put_contents;
use function realpath;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class ListenerProviderReaderTest extends TestCase
{
    private static string $fixtureFile;

    public static function setUpBeforeClass(): void
    {
        /** @var non-empty-string $path */
        $path = realpath(__DIR__ . '/../../Fixtures/Event/Provider/TestListenerProviderFixture.php');

        self::$fixtureFile = $path;
    }

    public function testReadFileExtractsListenerClasses(): void
    {
        $result = new ListenerProviderReader()->readFile(self::$fixtureFile);

        self::assertSame([TestListenerFixture::class], $result->listenerClasses);
    }

    public function testReadFileExtractsEmptyListeners(): void
    {
        $result = new ListenerProviderReader()->readFile(self::$fixtureFile);

        self::assertSame([], $result->listeners);
    }

    public function testReadFileReturnsEmptyResultForFileWithNoClass(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'sindri_test') . '.php';
        file_put_contents($tmpFile, "<?php\ndeclare(strict_types=1);\nnamespace Test;\n");

        $result = new ListenerProviderReader()->readFile($tmpFile);

        @unlink($tmpFile);

        self::assertSame([], $result->listenerClasses);
        self::assertSame([], $result->listeners);
    }
}
