<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use app\console\commands\Greeting;
use mako\cli\input\Input;
use mako\cli\output\Output;
use PHPUnit\Framework\TestCase;

class GreetingTest extends TestCase
{
    public function test_execute_writes_greeting_message(): void
    {
        $command = new class($this->createStub(Input::class), $this->createStub(Output::class)) extends Greeting {
            public array $writes = [];

            protected function write(string $string, int $writer = Output::STANDARD): void
            {
                $this->writes[] = $string;
            }
        };

        $command->execute();

        $this->assertSame(['<blue>Hello, world!</blue>'], $command->writes);
    }
}
