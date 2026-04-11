<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use app\console\commands\GenerateOpenSslKey;
use mako\application\Application;
use mako\cli\input\Input;
use mako\cli\output\Output;
use mako\file\FileSystem;
use PHPUnit\Framework\TestCase;

class GenerateOpenSslKeyTest extends TestCase
{
    public function test_execute_returns_error_when_config_not_writable(): void
    {
        $application = $this->createStub(Application::class);
        $fs = $this->createMock(FileSystem::class);

        $application->method('getPath')->willReturn('/var/www/html/app');
        $fs->expects($this->once())
            ->method('isWritable')
            ->with('/var/www/html/app/config/crypto.php')
            ->willReturn(false);

        $command = new class($this->createStub(Input::class), $this->createStub(Output::class)) extends GenerateOpenSslKey {
            public array $errors = [];

            protected function error(string $string): void
            {
                $this->errors[] = $string;
            }
        };

        $result = $command->execute($application, $fs);

        $this->assertSame(GenerateOpenSslKey::STATUS_ERROR, $result);
        $this->assertCount(1, $command->errors);
    }

    public function test_execute_replaces_key_and_writes_file(): void
    {
        $application = $this->createStub(Application::class);
        $fs = new FileSystem();

        $tmpRoot = sys_get_temp_dir() . '/mako-openssl-key-' . uniqid('', true);
        mkdir($tmpRoot . '/app/config', 0777, true);
        $path = $tmpRoot . '/app/config/crypto.php';
        file_put_contents($path, "<?php\nreturn ['key' => 'OLDKEYVALUE',];\n");

        $application->method('getPath')->willReturn($tmpRoot . '/app');

        $command = new class($this->createStub(Input::class), $this->createStub(Output::class)) extends GenerateOpenSslKey {
            public array $writes = [];

            protected function write(string $string, int $writer = Output::STANDARD): void
            {
                $this->writes[] = $string;
            }
        };

        $command->execute($application, $fs);
        $updated = file_get_contents($path);

        $this->assertSame(['A new OpenSSL key has been generated.'], $command->writes);
        $this->assertMatchesRegularExpression("/'key'\\s*=>\\s*'[^']+',/", (string) $updated);
        $this->assertStringNotContainsString('OLDKEYVALUE', (string) $updated);

        unlink($path);
        rmdir($tmpRoot . '/app/config');
        rmdir($tmpRoot . '/app');
        rmdir($tmpRoot);
    }
}
