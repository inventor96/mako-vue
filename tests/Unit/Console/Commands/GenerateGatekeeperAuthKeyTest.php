<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use app\console\commands\GenerateGatekeeperAuthKey;
use mako\application\Application;
use mako\cli\input\Input;
use mako\cli\output\Output;
use mako\file\FileSystem;
use PHPUnit\Framework\TestCase;

class GenerateGatekeeperAuthKeyTest extends TestCase
{
    public function test_execute_returns_error_when_config_not_writable(): void
    {
        $application = $this->createStub(Application::class);
        $fs = $this->createMock(FileSystem::class);

        $application->method('getPath')->willReturn('/var/www/html/app');
        $fs->expects($this->once())
            ->method('isWritable')
            ->with('/var/www/html/app/config/gatekeeper.php')
            ->willReturn(false);

        $command = new class($this->createStub(Input::class), $this->createStub(Output::class)) extends GenerateGatekeeperAuthKey {
            public array $errors = [];

            protected function error(string $string): void
            {
                $this->errors[] = $string;
            }
        };

        $result = $command->execute($application, $fs);

        $this->assertSame(GenerateGatekeeperAuthKey::STATUS_ERROR, $result);
        $this->assertCount(1, $command->errors);
    }

    public function test_execute_replaces_auth_key_and_writes_file(): void
    {
        $application = $this->createStub(Application::class);
        $fs = new FileSystem();

        $tmpRoot = sys_get_temp_dir() . '/mako-gatekeeper-key-' . uniqid('', true);
        mkdir($tmpRoot . '/app/config', 0777, true);
        $path = $tmpRoot . '/app/config/gatekeeper.php';
        file_put_contents($path, "<?php\nreturn ['auth_key' => 'OLDKEY1234567890',];\n");

        $application->method('getPath')->willReturn($tmpRoot . '/app');

        $command = new class($this->createStub(Input::class), $this->createStub(Output::class)) extends GenerateGatekeeperAuthKey {
            public array $writes = [];

            protected function write(string $string, int $writer = Output::STANDARD): void
            {
                $this->writes[] = $string;
            }
        };

        $command->execute($application, $fs);
        $updated = file_get_contents($path);

        $this->assertSame(['A new Gatekeeper auth key has been generated.'], $command->writes);
        $this->assertMatchesRegularExpression("/'auth_key'\\s*=>\\s*'[A-Za-z0-9]{16}',/", (string) $updated);
        $this->assertStringNotContainsString('OLDKEY1234567890', (string) $updated);

        unlink($path);
        rmdir($tmpRoot . '/app/config');
        rmdir($tmpRoot . '/app');
        rmdir($tmpRoot);
    }
}
