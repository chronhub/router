<?php

declare(strict_types=1);

namespace Chronhub\Message\Router;

use Illuminate\Support\Collection;
use Chronhub\Message\Router\Exceptions\RouterRuleViolation;
use function array_merge;

final class Router
{
    private Collection $groups;

    public function __construct(private readonly GroupFactory $router)
    {
        $this->groups = new Collection();
    }

    public function make(string $domainType, string $name): Group
    {
        $group = $this->newGroupInstance($domainType, $name);

        if (! $this->groups->has($domainType)) {
            $this->groups->put($domainType, [$name => $group]);

            return $group;
        }

        return $this->mergeWithGroup($group);
    }

    // todo to move
    public function fromConfiguration(array $configuration): void
    {
        foreach ($configuration as $domainType => $config) {
            foreach ($config as $name => $router) {
                $this->assertUniqueDomainTypeAndName($domainType, $name);

                $group = $this->make($domainType, $name);

                $this->router->make($group, $router);
            }
        }
    }

    public function get(string $domainType, string $name): ?Group
    {
        return $this->groups[$domainType][$name] ?? null;
    }

    public function all(): Collection
    {
        return clone $this->groups;
    }

    public function has(string $domainType, string $name): bool
    {
        return $this->get($domainType, $name) instanceof Group;
    }

    private function mergeWithGroup(Group $group): Group
    {
        $domainType = $group->getType();
        $name = $group->getName();

        $this->assertUniqueDomainTypeAndName($domainType, $name);

        $this->groups->put($domainType, array_merge($this->groups[$domainType], [$name => $group]));

        return $group;
    }

    /**
     * Create a new group instance
     *
     * @param  string  $domainType
     * @param  string  $name
     * @return Group
     */
    private function newGroupInstance(string $domainType, string $name): Group
    {
        return new Group($domainType, $name, new Routes());
    }

    private function assertUniqueDomainTypeAndName(string $domainType, string $name): void
    {
        if (isset($this->groups[$domainType][$name])) {
            throw new RouterRuleViolation("$domainType Reporter already exists with name $name");
        }
    }
}
