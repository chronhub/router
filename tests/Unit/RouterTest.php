<?php

declare(strict_types=1);

namespace Chronhub\Message\Router\Tests\Unit;

use Generator;
use Chronhub\Testing\UnitTest;
use Chronhub\Message\Router\Router;
use Illuminate\Container\Container;
use Chronhub\Message\Router\GroupFactory;
use Chronhub\Message\Router\Exceptions\RouterRuleViolation;

final class RouterTest extends UnitTest
{
    /**
     * @test
     */
    public function it_can_be_instantiated(): void
    {
        $router = new Router(new GroupFactory(Container::getInstance()));

        $this->assertEmpty($router->all());
    }

    /**
     * @test
     * @dataProvider provideGroupType
     */
    public function it_create_domain_group(string $type): void
    {
        $router = new Router(new GroupFactory(Container::getInstance()));

        $group = $router->make($type, 'default');

        $this->assertSame($group, $router->get($type, 'default'));

        $this->assertEquals($type, $group->getType());
        $this->assertEquals('default', $group->getName());
        $this->assertCount(1, $router->all());
    }

    /**
     * @test
     * @dataProvider provideGroupType
     */
    public function it_merge_with_existing_domain_type(string $type): void
    {
        $router = new Router(new GroupFactory(Container::getInstance()));

        $group1 = $router->make($type, 'default');
        $group2 = $router->make($type, 'another');

        $this->assertSame($group1, $router->get($type, 'default'));
        $this->assertSame($group2, $router->get($type, 'another'));

        $this->assertCount(1, $router->all());
    }

    /**
     * @test
     * @dataProvider provideGroupType
     */
    public function it_check_if_domain_type_and_name_exists(string $type): void
    {
        $router = new Router(new GroupFactory(Container::getInstance()));

        $this->assertFalse($router->has($type, 'default'));
        $this->assertFalse($router->has($type, 'another'));

        $group1 = $router->make($type, 'default');
        $group2 = $router->make($type, 'another');

        $this->assertSame($group1, $router->get($type, 'default'));
        $this->assertSame($group2, $router->get($type, 'another'));

        $this->assertTrue($router->has($type, 'default'));
        $this->assertTrue($router->has($type, 'another'));
    }

    /**
     * @test
     */
    public function it_clone_groups_as_collection(): void
    {
        $router = new Router(new GroupFactory(Container::getInstance()));

        $router->make('command', 'default');
        $router->make('command', 'another');

        $cloneCollection = $router->all();
        $this->assertNotSame($cloneCollection, $router->all());
        $this->assertEquals($cloneCollection, $router->all());

        $router->make('command', 'foo_bar');

        $this->assertNotSame($cloneCollection, $router->all());
        $this->assertNotEquals($cloneCollection, $router->all());
    }

    /**
     * @test
     * @dataProvider provideGroupType
     */
    public function it_raise_exception_when_domain_type_has_duplicate_name(string $type): void
    {
        $this->expectException(RouterRuleViolation::class);
        $this->expectExceptionMessage("$type Reporter already exists with name default");

        $router = new Router(new GroupFactory(Container::getInstance()));

        $router->make($type, 'default');
        $router->make($type, 'default');
    }

    public function provideGroupType(): Generator
    {
        yield ['command'];
        yield ['event'];
        yield ['query'];
    }
}
