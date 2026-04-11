<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use app\console\commands\UpdateGitIgnore;
use mako\application\Application;
use mako\cli\input\Input;
use mako\cli\output\Output;
use mako\file\FileSystem;
use PHPUnit\Framework\TestCase;

class UpdateGitIgnoreTest extends TestCase
{
    public function test_execute_returns_error_when_file_is_not_writable(): void
    {
        $application = $this->createStub(Application::class);
        $fs = $this->createMock(FileSystem::class);

        $application->method('getPath')->willReturn('/var/www/html/app');
        $fs->expects($this->once())
            ->method('isWritable')
            ->with('/var/www/html/app/../.gitignore')
            ->willReturn(false);

        $command = new class($this->createStub(Input::class), $this->createStub(Output::class)) extends UpdateGitIgnore {
            public array $errors = [];

            protected function error(string $string): void
            {
                $this->errors[] = $string;
            }
        };

        $result = $command->execute($application, $fs);

        $this->assertSame(UpdateGitIgnore::STATUS_ERROR, $result);
        $this->assertCount(1, $command->errors);
    }

    public function test_execute_removes_expected_lines_and_writes_file(): void
    {
        $application = $this->createStub(Application::class);
        $fs = new FileSystem();

        $tmpRoot = sys_get_temp_dir() . '/mako-update-ignore-' . uniqid('', true);
        mkdir($tmpRoot . '/app', 0777, true);
        $path = $tmpRoot . '/.gitignore';
        file_put_contents($path, "/vendor\n/composer.lock\n/node_modules\n/package-lock.json\n");

        $application->method('getPath')->willReturn($tmpRoot . '/app');

        $command = new class($this->createStub(Input::class), $this->createStub(Output::class)) extends UpdateGitIgnore {
            public array $writes = [];

            protected function write(string $string, int $writer = Output::STANDARD): void
            {
                $this->writes[] = $string;
            }
        };

        $result = $command->execute($application, $fs);
        $updated = file_get_contents($path);

        $this->assertNull($result);
        $this->assertSame(['The .gitignore file has been updated.'], $command->writes);
        $this->assertFalse(str_contains($updated, '/composer.lock'));
        $this->assertFalse(str_contains($updated, '/package-lock.json'));
        $this->assertTrue(str_contains($updated, '/vendor'));
        $this->assertTrue(str_contains($updated, '/node_modules'));

        unlink($path);
        rmdir($tmpRoot . '/app');
        rmdir($tmpRoot);
    }
}
