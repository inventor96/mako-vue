<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use app\models\User;
use mako\database\midgard\ORM;
use mako\database\midgard\relations\ManyToMany;
use mako\gatekeeper\entities\group\Group;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function test_get_name_returns_trimmed_full_name(): void
    {
        $user = $this->newUserWithoutConstructor();
        $user->first_name = 'Jane';
        $user->last_name = 'Doe';

        $this->assertSame('Jane Doe', $user->getName());
    }

    public function test_get_name_returns_null_when_names_are_empty(): void
    {
        $user = $this->newUserWithoutConstructor();
        $user->first_name = '';
        $user->last_name = ' ';

        $this->assertNull($user->getName());
    }

    public function test_get_email_returns_email_property(): void
    {
        $user = $this->newUserWithoutConstructor();
        $user->email = 'person@example.test';

        $this->assertSame('person@example.test', $user->getEmail());
    }

    public function test_update_from_calls_require_and_assign_then_save_and_returns_self(): void
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['requireAndAssign', 'save'])
            ->getMock();

        $user->expects($this->once())
            ->method('requireAndAssign')
            ->with(['first_name' => 'Jane'])
            ->willReturnSelf();

        $user->expects($this->once())
            ->method('save')
            ->willReturn(true);

        $fields = ['first_name' => 'Jane'];
        $result = $user->updateFrom($fields);

        $this->assertSame($user, $result);
    }

    public function test_sync_groups_removes_groups_not_in_target_list(): void
    {
        $keepGroup = new class {
            public int $id = 1;
            public int $removeCalls = 0;

            public function removeUser(object $user): void
            {
                $this->removeCalls++;
            }
        };

        $removeGroup = new class {
            public int $id = 2;
            public int $removeCalls = 0;

            public function removeUser(object $user): void
            {
                $this->removeCalls++;
            }
        };

        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['groups'])
            ->getMock();

        $relation = $this->createStub(ManyToMany::class);
        $relation->method('count')->willReturn(2);

        $user->expects($this->atLeastOnce())->method('groups')->willReturn($relation);

        $this->setRelatedGroups($user, [$keepGroup, $removeGroup]);

        $result = $user->syncGroups([1, 0, '', null]);

        $this->assertSame($user, $result);
        $this->assertSame(0, $keepGroup->removeCalls);
        $this->assertSame(1, $removeGroup->removeCalls);
    }

    public function test_sync_groups_keeps_all_when_ids_match(): void
    {
        $groupOne = new class {
            public int $id = 1;
            public int $removeCalls = 0;

            public function removeUser(object $user): void
            {
                $this->removeCalls++;
            }
        };

        $groupTwo = new class {
            public int $id = 2;
            public int $removeCalls = 0;

            public function removeUser(object $user): void
            {
                $this->removeCalls++;
            }
        };

        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['groups'])
            ->getMock();

        $relation = $this->createStub(ManyToMany::class);
        $relation->method('count')->willReturn(2);

        $user->expects($this->atLeastOnce())->method('groups')->willReturn($relation);

        $this->setRelatedGroups($user, [$groupOne, $groupTwo]);

        $result = $user->syncGroups(['1', '2']);

        $this->assertSame($user, $result);
        $this->assertSame(0, $groupOne->removeCalls);
        $this->assertSame(0, $groupTwo->removeCalls);
    }

    public function test_sync_groups_with_no_current_groups_and_empty_target_is_noop(): void
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['groups'])
            ->getMock();

        $relation = $this->createStub(ManyToMany::class);
        $relation->method('count')->willReturn(0);

        $user->expects($this->once())->method('groups')->willReturn($relation);

        $result = $user->syncGroups([]);

        $this->assertSame($user, $result);
    }

    public function test_sync_groups_adds_new_groups_from_target_list(): void
    {
        $relation = $this->createStub(ManyToMany::class);
        $relation->method('count')->willReturn(0);

        $group = $this->getMockBuilder(Group::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addUser'])
            ->getMock();

        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['groups', 'resolveGroupById'])
            ->getMock();

        $user->method('groups')->willReturn($relation);

        $user->expects($this->once())
            ->method('resolveGroupById')
            ->with(7)
            ->willReturn($group);

        $group->expects($this->once())
            ->method('addUser')
            ->with($user)
            ->willReturn(true);

        $result = $user->syncGroups([7]);

        $this->assertSame($user, $result);
    }

    private function newUserWithoutConstructor(): User
    {
        $reflection = new \ReflectionClass(User::class);

        return $reflection->newInstanceWithoutConstructor();
    }

    private function setRelatedGroups(User $user, array $groups): void
    {
        $reflection = new \ReflectionProperty(ORM::class, 'related');
        $reflection->setValue($user, ['groups' => $groups]);
    }
}
