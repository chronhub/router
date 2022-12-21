<?php

declare(strict_types=1);

namespace Chronhub\Message\Router\Tests\Unit;

use Generator;
use Chronhub\Testing\UnitTestCase;
use Chronhub\Message\Router\Router;
use Illuminate\Container\Container;
use Chronhub\Message\Router\GroupFactory;
use Chronhub\Message\Router\Exceptions\RouterRuleViolation;

final class RouterTest extends UnitTestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();

        $container = fn (): Container => Container::getInstance();
        $this->router = new Router(new GroupFactory($container));
    }

    /**
     * @test
     */
    public function it_can_be_instantiated(): void
    {
        $this->assertEmpty($this->router->all());
    }

    /**
     * @test
     * @dataProvider provideGroupType
     */
    public function it_create_domain_group(string $type): void
    {
        $group = $this->router->make($type, 'default');

        $this->assertSame($group, $this->router->get($type, 'default'));

        $this->assertEquals($type, $group->getType());
        $this->assertEquals('default', $group->getName());
        $this->assertCount(1, $this->router->all());
    }

    /**
     * @test
     * @dataProvider provideGroupType
     */
    public function it_merge_with_existing_domain_type(string $type): void
    {
        $group1 = $this->router->make($type, 'default');
        $group2 = $this->router->make($type, 'another');

        $this->assertSame($group1, $this->router->get($type, 'default'));
        $this->assertSame($group2, $this->router->get($type, 'another'));

        $this->assertCount(1, $this->router->all());
    }

    /**
     * @test
     * @dataProvider provideGroupType
     */
    public function it_check_if_domain_type_and_name_exists(string $type): void
    {
        $this->assertFalse($this->router->has($type, 'default'));
        $this->assertFalse($this->router->has($type, 'another'));

        $group1 = $this->router->make($type, 'default');
        $group2 = $this->router->make($type, 'another');

        $this->assertSame($group1, $this->router->get($type, 'default'));
        $this->assertSame($group2, $this->router->get($type, 'another'));

        $this->assertTrue($this->router->has($type, 'default'));
        $this->assertTrue($this->router->has($type, 'another'));
    }

    /**
     * @test
     */
    public function it_clone_groups_as_collection(): void
    {
        $this->router->make('command', 'default');
        $this->router->make('command', 'another');

        $cloneCollection = $this->router->all();
        $this->assertNotSame($cloneCollection, $this->router->all());
        $this->assertEquals($cloneCollection, $this->router->all());

        $this->router->make('command', 'foo_bar');

        $this->assertNotSame($cloneCollection, $this->router->all());
        $this->assertNotEquals($cloneCollection, $this->router->all());
    }

    /**
     * @test
     * @dataProvider provideGroupType
     */
    public function it_raise_exception_when_domain_type_has_duplicate_name(string $type): void
    {
        $this->expectException(RouterRuleViolation::class);
        $this->expectExceptionMessage("$type Reporter already exists with name default");

        $this->router->make($type, 'default');
        $this->router->make($type, 'default');
    }

    public function provideGroupType(): Generator
    {
        yield ['command'];
        yield ['event'];
        yield ['query'];
    }
}
