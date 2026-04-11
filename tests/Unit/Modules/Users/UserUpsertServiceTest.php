<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Users;

use app\models\User;
use app\modules\users\UserUpsertService;
use mako\database\exceptions\DatabaseException;
use mako\gatekeeper\adapters\Adapter;
use mako\gatekeeper\entities\user\User as GatekeeperUser;
use mako\validator\exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

class UserUpsertServiceTest extends TestCase
{
    public function test_persisted_user_is_updated_and_groups_are_synced(): void
    {
        $gatekeeper = $this->createStub(Adapter::class);

        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isPersisted', 'updateFrom', 'syncGroups'])
            ->getMock();

        $user->method('isPersisted')->willReturn(true);
        $user->expects($this->once())->method('updateFrom')->with(['email' => 'a@example.test', 'groups' => [1, 2]]);
        $user->expects($this->once())->method('syncGroups')->with([1, 2]);

        $service = new UserUpsertService($gatekeeper);
        $result = $service->createOrUpdateFrom($user, ['email' => 'a@example.test', 'groups' => [1, 2]]);

        $this->assertSame($user, $result);
    }

    public function test_new_user_creation_builds_extras_and_syncs_groups_on_created_user(): void
    {
        $createdUser = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['syncGroups'])
            ->getMock();

        $createdUser->expects($this->once())->method('syncGroups')->with([3, 4]);

        $gatekeeper = $this->createMock(Adapter::class);

        $gatekeeper->expects($this->once())
            ->method('createUser')
            ->with(
                'new@example.test',
                'new@example.test',
                'secret',
                false,
                ['first_name' => 'New', 'last_name' => 'User']
            )
            ->willReturn($createdUser);

        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isPersisted', 'requireFields', '__get'])
            ->getMock();

        $user->method('isPersisted')->willReturn(false);
        $user->expects($this->once())->method('requireFields')->with($this->isArray(), true);
        $user->method('__get')->willReturnCallback(
            static function (string $name): mixed {
                if ($name === 'assignable') {
                    return ['email', 'username', 'password', 'first_name', 'last_name'];
                }

                throw new \RuntimeException('Unexpected property access: ' . $name);
            }
        );

        $service = new UserUpsertService($gatekeeper);
        $result = $service->createOrUpdateFrom($user, [
            'email' => 'new@example.test',
            'password' => 'secret',
            'first_name' => 'New',
            'last_name' => 'User',
            'groups' => [3, 4],
        ]);

        $this->assertSame($createdUser, $result);
    }

    public function test_duplicate_email_database_exception_is_mapped_to_validation_exception(): void
    {
        $gatekeeper = $this->createStub(Adapter::class);

        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isPersisted', 'updateFrom'])
            ->getMock();

        $user->method('isPersisted')->willReturn(true);
        $user->expects($this->once())
            ->method('updateFrom')
            ->with(['email' => 'dup@example.test'])
            ->willThrowException(new DatabaseException("Duplicate entry 'dup@example.test' for key 'email'"));

        $service = new UserUpsertService($gatekeeper);

        try {
            $service->createOrUpdateFrom($user, ['email' => 'dup@example.test']);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertSame(
                ['email' => "A user already exists with the email 'dup@example.test'."],
                $e->getErrors()
            );
        }
    }

    public function test_non_duplicate_database_exception_is_rethrown(): void
    {
        $gatekeeper = $this->createStub(Adapter::class);

        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isPersisted', 'updateFrom'])
            ->getMock();

        $user->method('isPersisted')->willReturn(true);
        $user->expects($this->once())
            ->method('updateFrom')
            ->with(['email' => 'x@example.test'])
            ->willThrowException(new DatabaseException('Connection lost'));

        $service = new UserUpsertService($gatekeeper);

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Connection lost');

        $service->createOrUpdateFrom($user, ['email' => 'x@example.test']);
    }

    public function test_create_throws_runtime_exception_when_gatekeeper_returns_non_app_user(): void
    {
        $gatekeeper = $this->createStub(Adapter::class);

        $unexpectedUserType = $this->createStub(GatekeeperUser::class);

        $gatekeeper->method('createUser')->willReturn($unexpectedUserType);

        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isPersisted', 'requireFields', '__get'])
            ->getMock();

        $user->method('isPersisted')->willReturn(false);
        $user->expects($this->once())
            ->method('requireFields')
            ->with($this->isArray(), true);
        $user->method('__get')->willReturnCallback(
            static fn (string $name): mixed => $name === 'assignable'
                ? ['email', 'username', 'password']
                : throw new \RuntimeException('Unexpected property access: ' . $name)
        );

        $service = new UserUpsertService($gatekeeper);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Gatekeeper returned an unexpected user model type.');

        $service->createOrUpdateFrom($user, [
            'email' => 'mismatch@example.test',
            'password' => 'secret',
        ]);
    }
}
