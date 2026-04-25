<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\UI\Navbar;

use app\models\User;
use app\modules\ui\navbar\NavLink;
use app\modules\ui\navbar\NavLinkFactory;
use app\modules\ui\navbar\NavLinks;
use PHPUnit\Framework\TestCase;

class NavLinksTest extends TestCase
{
    public function test_guest_right_links_include_signup_and_login(): void
    {
        $factory = $this->getMockBuilder(NavLinkFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createFromRoute'])
            ->getMock();

        $factory->expects($this->exactly(2))
            ->method('createFromRoute')
            ->willReturnCallback(
                static fn (string $name, string $icon, string $routeName): NavLink =>
                    new NavLink($name, $icon, '/' . str_replace(':', '/', $routeName), false)
            );

        $links = new NavLinks($factory);
        $right = $links->generateRightLinks(null);

        $this->assertCount(2, $right);
        $this->assertSame('Sign Up', $right[0]->name);
        $this->assertSame('Log In', $right[1]->name);
    }

    public function test_logged_in_right_links_include_account_and_logout(): void
    {
        $factory = $this->getMockBuilder(NavLinkFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createFromRoute'])
            ->getMock();

        $factory->expects($this->exactly(2))
            ->method('createFromRoute')
            ->willReturnCallback(
                static fn (string $name, string $icon, string $routeName): NavLink =>
                    new NavLink($name, $icon, '/' . str_replace(':', '/', $routeName), false)
            );

        $links = new NavLinks($factory);
        $right = $links->generateRightLinks($this->newUserWithoutConstructor());

        $this->assertCount(2, $right);
        $this->assertSame('Account', $right[0]->name);
        $this->assertSame('Log Out', $right[1]->name);
    }

    public function test_left_links_are_empty_for_guest_and_logged_in_user(): void
    {
        $factory = $this->createStub(NavLinkFactory::class);

        $guestLinks = (new NavLinks($factory))->generateLeftLinks(null);
        $authLinks = (new NavLinks($factory))->generateLeftLinks($this->newUserWithoutConstructor());

        $this->assertSame([], $guestLinks);
        $this->assertSame([], $authLinks);
    }

    private function newUserWithoutConstructor(): User
    {
        $reflection = new \ReflectionClass(User::class);

        return $reflection->newInstanceWithoutConstructor();
    }
}
