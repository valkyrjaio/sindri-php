<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Ast;

use Override;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeTraverser;
use Sindri\Ast\Abstract\AstReader;
use Sindri\Ast\Contract\RouteProviderReaderContract;
use Sindri\Ast\Data\Result\RouteProviderResult;

/**
 * Reads a single CliRouteProviderContract or HttpRouteProviderContract source file
 * and extracts route data. Both provider types share this reader because their
 * contracts are structurally identical.
 *
 * Two sources of routes are read:
 *
 *   getControllerClasses() — returns class names of attributed controller classes;
 *                             a subsequent attribute-reader pass will scan each class
 *                             for #[Route] attributes and build full route data objects.
 *
 *   getRoutes()            — returns manually-defined route objects; each bare construction
 *                             the generated file can evaluate is kept verbatim, with each
 *                             imported, namespace-relative, `self` and `static` class name in
 *                             it made fully qualified.
 *
 * All methods and properties are protected to allow easy subclass customization.
 */
class RouteProviderReader extends AstReader implements RouteProviderReaderContract
{
    protected const string METHOD_CONTROLLER_CLASSES = 'getControllerClasses';
    protected const string METHOD_ROUTES             = 'getRoutes';

    /**
     * @inheritDoc
     */
    #[Override]
    public function readFile(string $filePath): RouteProviderResult
    {
        $classContext = $this->parseClassFile($filePath);

        if ($classContext === null) {
            return new RouteProviderResult();
        }

        [$classNode, $namespace, $useMap, $currentClass] = $classContext;

        $methods     = $this->indexMethods($classNode);
        $parentClass = $classNode->extends instanceof Name
            ? $this->nameToFqn($classNode->extends, $useMap, $namespace)
            : '';

        return new RouteProviderResult(
            controllerClasses: $this->extractClassListFromValues($methods[self::METHOD_CONTROLLER_CLASSES] ?? null, $useMap, $namespace),
            routes: $this->extractRoutes($methods[self::METHOD_ROUTES] ?? null, $useMap, $namespace, $currentClass, $parentClass),
        );
    }

    /**
     * Extract the manually-defined route constructions from the getRoutes() method.
     *
     * Only a bare construction the generated file can evaluate is kept.
     *
     * @param array<string, string> $useMap
     *
     * @return New_[]
     */
    protected function extractRoutes(
        ClassMethod|null $method,
        array $useMap,
        string $namespace,
        string $currentClass,
        string $parentClass,
    ): array {
        if ($method === null) {
            return [];
        }

        $array = $this->findReturnedArray($method);

        if ($array === null) {
            return [];
        }

        // The generated data file carries none of the provider file's use statements, so each
        // class name in the expression is made fully qualified before the generator prints it.
        $qualifier = new RouteExpressionQualifier($useMap, $namespace, $currentClass, $parentClass);
        $traverser = new NodeTraverser($qualifier);
        $routes    = [];

        foreach ($array->items as $item) {
            $route = $item?->value;

            if (! $route instanceof New_ || $this->hasUnreadableArgs($route)) {
                continue;
            }

            $traverser->traverse([$route]);

            if (! $qualifier->isEvaluable()) {
                continue;
            }

            $routes[] = $route;
        }

        return $routes;
    }

    /**
     * Report whether a construction's argument list cannot be read by position.
     */
    protected function hasUnreadableArgs(New_ $route): bool
    {
        foreach ($route->getRawArgs() as $arg) {
            // A placeholder leaves no argument to read, and a spread is skipped without
            // shifting the positions after it, so an argument that follows one reads as absent.
            if (! $arg instanceof Arg || $arg->unpack) {
                return true;
            }
        }

        return false;
    }
}
