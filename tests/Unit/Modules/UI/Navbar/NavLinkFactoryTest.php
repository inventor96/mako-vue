<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\UI\Navbar;

use app\modules\ui\navbar\NavLinkFactory;
use mako\http\Request;
use mako\http\routing\Route;
use mako\http\routing\URLBuilder;
use PHPUnit\Framework\TestCase;

class NavLinkFactoryTest extends TestCase
{
    public function test_create_from_route_sets_path_and_active_state(): void
    {
        $request = $this->createStub(Request::class);
        $route = $this->createStub(Route::class);
        $builder = $this->createMock(URLBuilder::class);

        $request->method('getRoute')->willReturn($route);
        $route->method('getName')->willReturn('auth:login');
        $builder->expects($this->once())
            ->method('toRoute')
            ->with('auth:login', [], [], '&', true)
            ->willReturn('/login');

        $factory = new NavLinkFactory($request, $builder);
        $link = $factory->createFromRoute('Log In', 'bi-box-arrow-in-right', 'auth:login');

        $this->assertSame('Log In', $link->name);
        $this->assertSame('/login', $link->path);
        $this->assertTrue($link->active);
    }

    public function test_create_dropdown_from_routes_marks_root_active_when_child_is_active(): void
    {
        $request = $this->createStub(Request::class);
        $route = $this->createStub(Route::class);
        $builder = $this->createStub(URLBuilder::class);

        $request->method('getRoute')->willReturn($route);
        $route->method('getName')->willReturn('auth:login');
        $builder->method('toRoute')->willReturnCallback(
            static fn (string $routeName): string => '/' . str_replace(':', '/', $routeName)
        );

        $factory = new NavLinkFactory($request, $builder);

        $root = $factory->createDropdownFromRoutes('Auth', 'bi-person', [
            ['Sign Up', 'bi-person-plus', 'auth:signup'],
            ['Log In', 'bi-box-arrow-in-right', 'auth:login'],
        ]);

        $this->assertTrue($root->isDropdown());
        $this->assertTrue($root->active);
        $this->assertCount(2, $root->getDropdowns());
        $this->assertSame('/auth/signup', $root->getDropdowns()[0]->path);
        $this->assertTrue($root->getDropdowns()[1]->active);
    }
}
