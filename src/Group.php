<?php

declare(strict_types=1);

namespace Chronhub\Message\Router;

final class Group
{
    public readonly Builder $builder;

    public function __construct(public readonly string $type,
                                public readonly string $name,
                                public readonly Routes $routes)
    {
        $this->builder = new Builder();
    }

    public function builder(): Builder
    {
        return $this->builder;
    }

    public function routes(): Routes
    {
        return $this->routes;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
