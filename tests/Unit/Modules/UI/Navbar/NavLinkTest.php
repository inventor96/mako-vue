<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\UI\Navbar;

use app\modules\ui\navbar\NavLink;
use PHPUnit\Framework\TestCase;

class NavLinkTest extends TestCase
{
    public function test_new_link_is_not_dropdown_by_default(): void
    {
        $link = new NavLink('Home', 'bi-house', '/home', true);

        $this->assertFalse($link->isDropdown());
        $this->assertSame([], $link->getDropdowns());
    }

    public function test_dropdown_appends_children_and_returns_self(): void
    {
        $root = new NavLink('More', 'bi-list', '#', false);
        $one = new NavLink('One', 'bi-1-circle', '/one', false);
        $two = new NavLink('Two', 'bi-2-circle', '/two', true);

        $result = $root->dropdown($one, $two);

        $this->assertSame($root, $result);
        $this->assertTrue($root->isDropdown());
        $this->assertCount(2, $root->getDropdowns());
        $this->assertSame($one, $root->getDropdowns()[0]);
        $this->assertSame($two, $root->getDropdowns()[1]);
    }
}
