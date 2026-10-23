<?php

declare(strict_types=1);

/*
 * This file is part of the Sindri package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Sindri\Ast\Abstract;

use PhpParser\Node\Stmt\ClassMethod;
use Sindri\Ast\Data\HandlerData;

abstract class RouteAttributeReader extends AstReader
{
    /**
     * Resolve the handler from #[Route\RouteHandler] or fall back to [CurrentClass::class, methodName].
     *
     * @param array<string, string> $useMap
     */
    protected function updateHandler(
        ClassMethod $method,
        array $useMap,
        string $namespace,
        string $currentClass,
    ): HandlerData {
        /** @var class-string $currentClass */
        return $this->getAttributedHandler($method, $useMap, $namespace, $currentClass)
            ?? new HandlerData(class: $currentClass, method: $method->name->toString());
    }

    /**
     * Resolve the handler a #[Route\RouteHandler] names, or null when the method carries none.
     *
     * A handler the reader cannot resolve to a class and a method name counts as none, because the
     * generated route holds the pair rather than the expression.
     *
     * @param array<string, string> $useMap
     */
    protected function getAttributedHandler(
        ClassMethod $method,
        array $useMap,
        string $namespace,
        string $currentClass,
    ): HandlerData|null {
        foreach ($this->findAttributesOnNode($method, $this->getRouteHandlerAttributeClass(), $useMap, $namespace) as $attr) {
            $raw = $this->extractExprValue($this->getAttrArg($attr->args, 'handler', 0), $useMap, $namespace, $currentClass);

            if ($raw instanceof HandlerData) {
                return $raw;
            }
        }

        return null;
    }

    /**
     * Return the fully-qualified class name of the #[RouteHandler] attribute used by this reader.
     *
     * @return class-string
     */
    abstract protected function getRouteHandlerAttributeClass(): string;
}
