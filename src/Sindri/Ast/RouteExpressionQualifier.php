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
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitor\NameResolver;

use function in_array;
use function strtolower;

class RouteExpressionQualifier extends NameResolver
{
    /** Whether every node of the last traversal is one the generated file can evaluate. */
    protected bool $isEvaluable = true;

    /**
     * @param array<string, string> $useMap      Alias or short name → fully-qualified class name
     * @param string                $namespace   The namespace of the file the expression comes from
     * @param string                $class       The fully-qualified name of the class `self` refers to
     * @param string                $parentClass The fully-qualified name of that class's parent
     */
    public function __construct(
        protected array $useMap,
        protected string $namespace,
        protected string $class,
        protected string $parentClass,
    ) {
        // A collecting handler keeps a malformed name in the expression. A throwing handler
        // would abort the whole generation run for one unusable route.
        parent::__construct(new Collecting());
    }

    /**
     * Report whether the generated file can evaluate every node of the last traversal.
     *
     * A caller drops an expression it cannot evaluate. The expression is written into a static
     * closure in another class and another namespace, so nothing that reads the declaring
     * provider's scope survives the move.
     */
    public function isEvaluable(): bool
    {
        return $this->isEvaluable;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function beforeTraverse(array $nodes): array|null
    {
        // The traversal starts at an expression, so no namespace or use statement reaches the
        // name context. This seeds it from the file the expression comes from.
        $nameContext = $this->getNameContext();

        $nameContext->startNamespace($this->namespace !== '' ? new Name($this->namespace) : null);

        foreach ($this->useMap as $alias => $fqn) {
            $nameContext->addAlias(new Name($fqn), $alias, Use_::TYPE_NORMAL);
        }

        $this->isEvaluable = true;

        return null;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function enterNode(Node $node)
    {
        $result = parent::enterNode($node);

        if (! $this->isEvaluableNode($node)) {
            $this->isEvaluable = false;
        }

        return $result;
    }

    /**
     * Report whether the generated file can evaluate one node of a route construction.
     *
     * Warning: an expression that reads the provider's scope evaluates to nothing in the
     * generated file, and the application then fails on the route instead of serving it. So a
     * node is listed here only when its value is the same wherever it is written, and every
     * other node, such as a variable, a property or a call, makes the whole expression
     * unevaluable.
     */
    protected function isEvaluableNode(Node $node): bool
    {
        if ($node instanceof ClassConstFetch) {
            return $node->name instanceof Identifier
                && ($node->name->toString() === 'class' || ! $this->namesOwnScope($node->class));
        }

        if ($node instanceof ConstFetch) {
            return in_array(strtolower($node->name->toString()), ['true', 'false', 'null'], true);
        }

        return $node instanceof Arg
            || $node instanceof Array_
            || $node instanceof ArrayItem
            || $node instanceof Float_
            || $node instanceof Identifier
            || $node instanceof Int_
            || $node instanceof Name
            || $node instanceof New_
            || $node instanceof String_
            || $node instanceof UnaryMinus;
    }

    /**
     * Report whether a class name is one whose non-public members the provider alone can read.
     *
     * Warning: this reader opens one file, so a constant a grandparent declares is out of
     * reach. Such a constant reads as a foreign one and the route is cached.
     */
    protected function namesOwnScope(Node $class): bool
    {
        if (! $class instanceof Name) {
            return false;
        }

        $name = $class->toString();

        return $name === $this->class || ($this->parentClass !== '' && $name === $this->parentClass);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function resolveClassName(Name $name): Name
    {
        $lowerName = strtolower($name->toString());

        if ($lowerName === 'self' || $lowerName === 'static') {
            return new FullyQualified($this->class, $name->getAttributes());
        }

        if ($lowerName === 'parent') {
            // `parent` names the provider's parent class, which the generated file's own parent
            // would then be read as instead.
            $this->isEvaluable = false;

            return $name;
        }

        return parent::resolveClassName($name);
    }
}
