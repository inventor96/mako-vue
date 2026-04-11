<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use app\traits\AssignRequireTrait;
use mako\validator\exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

class AssignRequireTraitTest extends TestCase
{
    public function test_require_fields_only_checks_present_fields_when_only_set_is_true(): void
    {
        $subject = new class {
            use AssignRequireTrait;

            protected array $required_fields = ['name', 'email'];

            public function email(): void
            {
            }
        };

        $subject->requireFields(['name' => 'Caleb'], true);

        $this->assertTrue(true);
    }

    public function test_require_fields_requires_all_fields_when_only_set_is_false(): void
    {
        $subject = new class {
            use AssignRequireTrait;

            protected array $required_fields = ['name', 'email'];

            public function email(): void
            {
            }
        };

        try {
            $subject->requireFields(['name' => 'Caleb'], false);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertSame(['The email field cannot be empty.'], $e->getErrors());
        }
    }

    public function test_require_fields_accepts_relation_id_alternative(): void
    {
        $subject = new class {
            use AssignRequireTrait;

            protected array $required_fields = ['role'];

            public function role(): void
            {
            }
        };

        $subject->requireFields(['role_id' => 4], true);

        $this->assertTrue(true);
    }

    public function test_require_fields_throws_for_empty_required_field(): void
    {
        $subject = new class {
            use AssignRequireTrait;

            protected array $required_fields = ['name'];
        };

        try {
            $subject->requireFields(['name' => ''], true);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertSame(['The name field cannot be empty.'], $e->getErrors());
        }
    }

    public function test_require_fields_collects_multiple_errors(): void
    {
        $subject = new class {
            use AssignRequireTrait;

            protected array $required_fields = ['first_name', 'last_name'];
        };

        try {
            $subject->requireFields(['first_name' => '', 'last_name' => ''], true);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertSame(
                [
                    'The first_name field cannot be empty.',
                    'The last_name field cannot be empty.',
                ],
                $e->getErrors()
            );
        }
    }

    public function test_require_fields_uses_explicit_fields_override(): void
    {
        $subject = new class {
            use AssignRequireTrait;

            protected array $required_fields = ['ignored'];
        };

        try {
            $subject->requireFields(['name' => ''], true, ['name']);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertSame(['The name field cannot be empty.'], $e->getErrors());
        }
    }

    public function test_require_and_assign_uses_is_persisted_when_only_set_is_null(): void
    {
        $subject = new class {
            use AssignRequireTrait;

            public bool $persisted = true;
            public array $require_fields_args = [];
            public array $assign_args = [];

            public function requireFields(array $input, bool $only_set = true, ?array $fields = null): void
            {
                $this->require_fields_args = [$input, $only_set, $fields];
            }

            public function isPersisted(): bool
            {
                return $this->persisted;
            }

            public function assign(array $columns, bool $raw = false, bool $whitelist = true): self
            {
                $this->assign_args = [$columns, $raw, $whitelist];

                return $this;
            }
        };

        $result = $subject->requireAndAssign(['name' => 'Caleb'], null, ['name'], true, false);

        $this->assertSame($subject, $result);
        $this->assertSame([['name' => 'Caleb'], true, ['name']], $subject->require_fields_args);
        $this->assertSame([['name' => 'Caleb'], true, false], $subject->assign_args);
    }

    public function test_require_and_assign_respects_explicit_only_set_argument(): void
    {
        $subject = new class {
            use AssignRequireTrait;

            public array $require_fields_args = [];

            public function requireFields(array $input, bool $only_set = true, ?array $fields = null): void
            {
                $this->require_fields_args = [$input, $only_set, $fields];
            }

            public function isPersisted(): bool
            {
                return true;
            }

            public function assign(array $columns, bool $raw = false, bool $whitelist = true): self
            {
                return $this;
            }
        };

        $subject->requireAndAssign(['name' => 'Caleb'], false);

        $this->assertSame([['name' => 'Caleb'], false, null], $subject->require_fields_args);
    }
}
