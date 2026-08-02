<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Generator\Abstract;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use Sindri\Generator\Enum\GenerateStatus;
use Throwable;

use function file_get_contents;
use function file_put_contents;
use function is_file;
use function rtrim;
use function strpos;
use function substr;

abstract class AstFileGenerator
{
    /**
     * Build a `ClassName::CASE` ClassConstFetch node from a "FQN::CASE" string,
     * or a String_ node when the value contains no "::".
     */
    protected function buildEnumCaseExpr(string $fqnColonCase): Expr
    {
        $pos = strpos($fqnColonCase, '::');

        if ($pos === false) {
            return new String_($fqnColonCase);
        }

        $fqn  = substr($fqnColonCase, 0, $pos);
        $case = substr($fqnColonCase, $pos + 2);

        return new ClassConstFetch(new FullyQualified($fqn), new Identifier($case));
    }

    protected function writeFile(string $directory, string $className, string $data): GenerateStatus
    {
        $filePath = rtrim($directory, '/') . "/$className.php";

        try {
            $existing = is_file($filePath) ? file_get_contents($filePath) : false;

            if ($existing === $data) {
                return GenerateStatus::SKIPPED;
            }

            $result = file_put_contents($filePath, $data);

            if ($result !== false) {
                return GenerateStatus::SUCCESS;
            }
        } catch (Throwable) {
            // Fallthrough
        }

        return GenerateStatus::FAILURE;
    }
}
