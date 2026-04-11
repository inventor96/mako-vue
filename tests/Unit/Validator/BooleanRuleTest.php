<?php

declare(strict_types=1);

namespace Tests\Unit\Validator;

use app\validator\rules\Boolean;
use PHPUnit\Framework\TestCase;

class BooleanRuleTest extends TestCase
{
    private Boolean $rule;

    protected function setUp(): void
    {
        $this->rule = new Boolean();
    }

    public function test_validate_when_empty_returns_true(): void
    {
        $this->assertTrue($this->rule->validateWhenEmpty());
    }

    public function test_validate_accepts_boolean_like_values(): void
    {
        $accepted = ['1', 1, true, '0', 0, false];

        foreach ($accepted as $value) {
            $this->assertTrue($this->rule->validate($value, 'enabled', []));
        }
    }

    public function test_validate_rejects_non_boolean_values(): void
    {
        $rejected = ['yes', 'no', 2, null, [], new \stdClass()];

        foreach ($rejected as $value) {
            $this->assertFalse($this->rule->validate($value, 'enabled', []));
        }
    }

    public function test_get_error_message_includes_field_name(): void
    {
        $this->assertSame(
            "The value of 'enabled' must be boolean.",
            $this->rule->getErrorMessage('enabled')
        );
    }
}
