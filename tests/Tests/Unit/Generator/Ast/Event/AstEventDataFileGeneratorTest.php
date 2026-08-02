<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Tests\Unit\Generator\Ast\Event;

use PhpParser\Node\Scalar\String_;
use Sindri\Generator\Ast\Event\AstEventDataFileGenerator;
use Sindri\Generator\Enum\GenerateStatus;
use Sindri\Tests\Unit\Abstract\TestCase;

use function sys_get_temp_dir;
use function unlink;

final class AstEventDataFileGeneratorTest extends TestCase
{
    public function testGenerateClassContentsWithEmptyListenersContainsDataClass(): void
    {
        $generator = new AstEventDataFileGenerator();
        $contents  = $generator->generateClassContents([]);

        self::assertStringContainsString('EventData', $contents);
    }

    public function testGenerateClassContentsWithEmptyListenersContainsEventsKey(): void
    {
        $generator = new AstEventDataFileGenerator();
        $contents  = $generator->generateClassContents([]);

        self::assertStringContainsString('events:', $contents);
    }

    public function testGenerateClassContentsWithEmptyListenersContainsListenersKey(): void
    {
        $generator = new AstEventDataFileGenerator();
        $contents  = $generator->generateClassContents([]);

        self::assertStringContainsString('listeners:', $contents);
    }

    public function testGenerateClassContentsWithListenerContainsListenerKey(): void
    {
        $generator = new AstEventDataFileGenerator();
        $contents  = $generator->generateClassContents([
            'test-listener' => new String_('listener-expr'),
        ]);

        self::assertStringContainsString("'test-listener'", $contents);
    }

    public function testGenerateFileReturnsSuccessOnNewFile(): void
    {
        $directory = sys_get_temp_dir();
        $className = 'AppEventDataTest' . __FUNCTION__;
        $filePath  = $directory . '/' . $className . '.php';

        $generator = new AstEventDataFileGenerator();
        $status    = $generator->generateFile(
            directory: $directory,
            className: $className,
            namespace: 'App\\Data',
            listeners: [],
        );

        @unlink($filePath);

        self::assertSame(GenerateStatus::SUCCESS, $status);
    }

    public function testGenerateFileReturnsSkippedOnSameContent(): void
    {
        $directory = sys_get_temp_dir();
        $className = 'AppEventDataSkip' . __FUNCTION__;
        $filePath  = $directory . '/' . $className . '.php';

        $generator = new AstEventDataFileGenerator();
        $generator->generateFile(
            directory: $directory,
            className: $className,
            namespace: 'App\\Data',
            listeners: [],
        );

        $status = $generator->generateFile(
            directory: $directory,
            className: $className,
            namespace: 'App\\Data',
            listeners: [],
        );

        @unlink($filePath);

        self::assertSame(GenerateStatus::SKIPPED, $status);
    }
}
