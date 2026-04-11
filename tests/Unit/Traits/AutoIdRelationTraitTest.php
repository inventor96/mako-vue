<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use app\traits\AutoIdRelationTrait;
use mako\database\midgard\Query;
use mako\database\midgard\relations\BelongsTo;
use mako\database\midgard\relations\Relation;
use PHPUnit\Framework\TestCase;

class AutoIdRelationTraitTest extends TestCase
{
    public function test_assign_converts_related_model_to_foreign_key_id(): void
    {
        $subject = new class extends AutoIdRelationTraitBase {
            use AutoIdRelationTrait;

            public array $assignable = ['name'];
            public array $captured = [];

            public function group(): BelongsTo
            {
                return AutoIdRelationTraitTest::makeBelongsTo('group_id', RelatedStub::class);
            }

            public function assignBase(array $columns, bool $raw = false, bool $whitelist = true): static
            {
                $this->captured = [$columns, $raw, $whitelist];

                return $this;
            }
        };

        $related = new RelatedStub(42);
        $result = $subject->assign(['group' => $related, 'name' => 'Jane'], true, false);

        $this->assertSame($subject, $result);
        $this->assertSame([
            ['name' => 'Jane', 'group_id' => 42],
            true,
            false,
        ], $subject->captured);
        $this->assertSame(['name', 'group_id'], $subject->assignable);
    }

    public function test_assign_uses_scalar_value_when_relation_input_is_not_model_instance(): void
    {
        $subject = new class extends AutoIdRelationTraitBase {
            use AutoIdRelationTrait;

            public array $assignable = [];
            public array $captured = [];

            public function group(): BelongsTo
            {
                return AutoIdRelationTraitTest::makeBelongsTo('group_id', RelatedStub::class);
            }

            public function assignBase(array $columns, bool $raw = false, bool $whitelist = true): static
            {
                $this->captured = [$columns, $raw, $whitelist];

                return $this;
            }
        };

        $subject->assign(['group' => 7]);

        $this->assertSame([['group_id' => 7], false, true], $subject->captured);
        $this->assertSame(['group_id'], $subject->assignable);
    }

    public function test_assign_does_not_duplicate_assignable_field(): void
    {
        $subject = new class extends AutoIdRelationTraitBase {
            use AutoIdRelationTrait;

            public array $assignable = ['group_id'];
            public array $captured = [];

            public function group(): BelongsTo
            {
                return AutoIdRelationTraitTest::makeBelongsTo('group_id', RelatedStub::class);
            }

            public function assignBase(array $columns, bool $raw = false, bool $whitelist = true): static
            {
                $this->captured = [$columns, $raw, $whitelist];

                return $this;
            }
        };

        $subject->assign(['group' => 8]);

        $this->assertSame(['group_id'], $subject->assignable);
        $this->assertSame([['group_id' => 8], false, true], $subject->captured);
    }

    public function test_assign_leaves_columns_unchanged_when_relation_method_is_missing_or_not_belongs_to(): void
    {
        $subject = new class extends AutoIdRelationTraitBase {
            use AutoIdRelationTrait;

            public array $captured = [];

            public function role(): object
            {
                return new \stdClass();
            }

            public function assignBase(array $columns, bool $raw = false, bool $whitelist = true): static
            {
                $this->captured = [$columns, $raw, $whitelist];

                return $this;
            }
        };

        $subject->assign(['role' => 3, 'unknown' => 4]);

        $this->assertSame([['role' => 3, 'unknown' => 4], false, true], $subject->captured);
    }

    public static function makeBelongsTo(string $foreignKey, string $modelClass): BelongsTo
    {
        $reflection = new \ReflectionClass(BelongsTo::class);
        $relation = $reflection->newInstanceWithoutConstructor();

        $foreignKeyProperty = new \ReflectionProperty(Relation::class, 'foreignKey');
        $foreignKeyProperty->setValue($relation, $foreignKey);

        $modelClassProperty = new \ReflectionProperty(Query::class, 'modelClass');
        $modelClassProperty->setValue($relation, $modelClass);

        return $relation;
    }
}

class RelatedStub
{
    public function __construct(public int $id)
    {
    }
}

abstract class AutoIdRelationTraitBase
{
    public function assign(array $columns, bool $raw = false, bool $whitelist = true): static
    {
        return $this->assignBase($columns, $raw, $whitelist);
    }

    abstract public function assignBase(array $columns, bool $raw = false, bool $whitelist = true): static;
}
