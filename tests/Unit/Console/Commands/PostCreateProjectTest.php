<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use app\console\commands\PostCreateProject;
use mako\application\Application;
use mako\cli\input\Input;
use mako\cli\output\Output;
use mako\file\FileSystem;
use PHPUnit\Framework\TestCase;

class PostCreateProjectTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('MAKO_SKIP_AUTOMATIC_MKCERT');
        putenv('COMPOSER_ALLOW_SUPERUSER');
        putenv('COMPOSER_HOME');

        parent::tearDown();
    }

    public function test_should_skip_automatic_mkcert_when_env_var_is_enabled(): void
    {
        putenv('MAKO_SKIP_AUTOMATIC_MKCERT=true');
        putenv('COMPOSER_ALLOW_SUPERUSER');
        putenv('COMPOSER_HOME');

        $command = new class($this->createStub(Input::class), $this->createStub(Output::class)) extends PostCreateProject {
            public function shouldSkipAutomaticMkcertPublic(): bool
            {
                return $this->shouldSkipAutomaticMkcert();
            }
        };

        $this->assertTrue($command->shouldSkipAutomaticMkcertPublic());
    }

    public function test_should_skip_automatic_mkcert_in_official_composer_environment(): void
    {
        putenv('MAKO_SKIP_AUTOMATIC_MKCERT');
        putenv('COMPOSER_ALLOW_SUPERUSER=1');
        putenv('COMPOSER_HOME=/tmp');

        $command = new class($this->createStub(Input::class), $this->createStub(Output::class)) extends PostCreateProject {
            public function shouldSkipAutomaticMkcertPublic(): bool
            {
                return $this->shouldSkipAutomaticMkcert();
            }
        };

        $this->assertTrue($command->shouldSkipAutomaticMkcertPublic());
    }

    public function test_queue_mkcert_request_writes_hidden_file_for_valid_domain(): void
    {
        $tmpRoot = sys_get_temp_dir() . '/mako-post-create-queue-' . uniqid('', true);
        mkdir($tmpRoot . '/app', 0777, true);

        $application = $this->createStub(Application::class);
        $application->method('getPath')->willReturn($tmpRoot . '/app');

        $command = new class($this->createStub(Input::class), $this->createStub(Output::class)) extends PostCreateProject {
            public function queueMkcertRequestPublic(Application $app, FileSystem $fs, string $domain): bool
            {
                return $this->queueMkcertRequest($app, $fs, $domain);
            }
        };

        $result = $command->queueMkcertRequestPublic($application, new FileSystem(), 'good.dev');

        $this->assertTrue($result);
        $this->assertSame("good.dev\n", file_get_contents($tmpRoot . '/.mkcert-request'));

        unlink($tmpRoot . '/.mkcert-request');
        rmdir($tmpRoot . '/app');
        rmdir($tmpRoot);
    }

    public function test_queue_mkcert_request_rejects_invalid_domain(): void
    {
        $tmpRoot = sys_get_temp_dir() . '/mako-post-create-invalid-queue-' . uniqid('', true);
        mkdir($tmpRoot . '/app', 0777, true);

        $application = $this->createStub(Application::class);
        $application->method('getPath')->willReturn($tmpRoot . '/app');

        $command = new class($this->createStub(Input::class), $this->createStub(Output::class)) extends PostCreateProject {
            public function queueMkcertRequestPublic(Application $app, FileSystem $fs, string $domain): bool
            {
                return $this->queueMkcertRequest($app, $fs, $domain);
            }
        };

        $result = $command->queueMkcertRequestPublic($application, new FileSystem(), 'bad domain');

        $this->assertFalse($result);
        $this->assertFileDoesNotExist($tmpRoot . '/.mkcert-request');

        rmdir($tmpRoot . '/app');
        rmdir($tmpRoot);
    }

    public function test_execute_returns_success_when_user_declines_to_apply_changes(): void
    {
        $application = $this->createStub(Application::class);
        $fs = $this->createMock(FileSystem::class);

        $appPath = '/var/www/html/app';
        $tasksPath = '/var/www/html/app/../.vscode/tasks.json';
        $launchPath = '/var/www/html/app/../.vscode/launch.json';
        $envPath = '/var/www/html/app/../.env';

        $application->method('getPath')->willReturn($appPath);
        $application->method('getEnvironment')->willReturn('docker');

        $fs->method('has')->willReturnMap([
            [$tasksPath, true],
            [$launchPath, true],
            [$envPath, true],
        ]);

        $fs->method('get')->willReturnMap([
            [$envPath, "LISTEN_IP=127.0.0.1\nLISTEN_DOMAIN=localhost\nXDEBUG_PORT=9003\n"],
            [$launchPath, json_encode([
                'configurations' => [[
                    'type' => 'php',
                    'request' => 'launch',
                    'pathMappings' => ['/var/www/html' => '${workspaceFolder}'],
                    'port' => 9003,
                ]],
            ])],
            ['/tmp/hosts', "127.0.0.1 localhost\n"],
        ]);

        $fs->expects($this->never())->method('put');

        $command = new class($this->createStub(Input::class), $this->createStub(Output::class)) extends PostCreateProject {
            private array $confirmQueue = [false];
            private array $inputQueue = ['mako-vue.dev', '127.0.0.2', '9514'];

            protected function nl(int $lines = 1, int $writer = Output::STANDARD): void
            {
            }

            protected function write(string $string, int $writer = Output::STANDARD): void
            {
            }

            protected function writeBlock(string $text, int $width = 80, int $writer = Output::STANDARD): void
            {
            }

            protected function ol(array $items, string $marker = '<yellow>%s</yellow>.', int $writer = Output::STANDARD): void
            {
            }

            protected function confirm(
                string $question,
                bool $default = false,
                string $trueLabel = 'Yes',
                string $falseLabel = 'No',
                \mako\cli\input\components\confirmation\Theme $theme = new \mako\cli\input\components\confirmation\Theme('<green>%s</green>', '<red>%s</red>', '<purple><bold>%s</bold></purple>')
            ): bool {
                return array_shift($this->confirmQueue) ?? false;
            }

            protected function input(string $prompt, mixed $default = null, string $inputPrefix = '<purple><bold>></bold></purple>'): mixed
            {
                return array_shift($this->inputQueue) ?? $default;
            }

            protected function commandExists(string $command): bool
            {
                return false;
            }

            protected function runShellCommand(string $command): array
            {
                return ['output' => [], 'code' => 0];
            }
        };

        $result = $command->execute($application, $fs);

        $this->assertSame(PostCreateProject::STATUS_SUCCESS, $result);
    }

    public function test_write_block_wraps_text_to_requested_width(): void
    {
        $command = new class($this->createStub(Input::class), $this->createStub(Output::class)) extends PostCreateProject {
            public array $writes = [];

            protected function write(string $string, int $writer = Output::STANDARD): void
            {
                $this->writes[] = $string;
            }

            public function writeBlockPublic(string $text, int $width): void
            {
                $this->writeBlock($text, $width);
            }
        };

        $command->writeBlockPublic('alpha beta gamma delta', 11);

        $this->assertSame(['alpha beta', 'gamma', 'delta'], $command->writes);
    }

    public function test_execute_can_copy_missing_files_and_apply_changes(): void
    {
        $tmpRoot = sys_get_temp_dir() . '/mako-post-create-' . uniqid('', true);
        mkdir($tmpRoot . '/app', 0777, true);
        mkdir($tmpRoot . '/.vscode', 0777, true);
        mkdir($tmpRoot . '/docker/caddy/certs', 0777, true);

        file_put_contents($tmpRoot . '/.vscode/tasks.json.example', "{\n  \"version\": \"2.0.0\"\n}\n");
        file_put_contents(
            $tmpRoot . '/.vscode/launch.json.example',
            json_encode(
                [
                    'configurations' => [[
                        'type' => 'php',
                        'request' => 'launch',
                        'pathMappings' => ['/var/www/html' => '${workspaceFolder}'],
                        'port' => 9003,
                    ]],
                ],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );
        file_put_contents(
            $tmpRoot . '/.env.example',
            "LISTEN_IP=127.0.0.1\nLISTEN_DOMAIN=localhost\nXDEBUG_PORT=9003\n"
        );

        file_put_contents($tmpRoot . '/docker/caddy/certs/_wildcard.mako-vue.dev.pem', 'cert');
        file_put_contents($tmpRoot . '/docker/caddy/certs/_wildcard.mako-vue.dev-key.pem', 'key');

        $application = $this->createStub(Application::class);
        $application->method('getPath')->willReturn($tmpRoot . '/app');
        $application->method('getEnvironment')->willReturn('development');

        $fs = new FileSystem();

        $command = new class($this->createStub(Input::class), $this->createStub(Output::class)) extends PostCreateProject {
            private array $confirmQueue = [true, true, true, true, false];
            private array $inputQueue = ['mako-vue.dev', '127.250.250.250', '9555'];

            protected function nl(int $lines = 1, int $writer = Output::STANDARD): void
            {
            }

            protected function write(string $string, int $writer = Output::STANDARD): void
            {
            }

            protected function writeBlock(string $text, int $width = 80, int $writer = Output::STANDARD): void
            {
            }

            protected function ol(array $items, string $marker = '<yellow>%s</yellow>.', int $writer = Output::STANDARD): void
            {
            }

            protected function confirm(
                string $question,
                bool $default = false,
                string $trueLabel = 'Yes',
                string $falseLabel = 'No',
                \mako\cli\input\components\confirmation\Theme $theme = new \mako\cli\input\components\confirmation\Theme('<green>%s</green>', '<red>%s</red>', '<purple><bold>%s</bold></purple>')
            ): bool {
                return array_shift($this->confirmQueue) ?? false;
            }

            protected function input(string $prompt, mixed $default = null, string $inputPrefix = '<purple><bold>></bold></purple>'): mixed
            {
                return array_shift($this->inputQueue) ?? $default;
            }

            protected function commandExists(string $command): bool
            {
                return false;
            }

            protected function runShellCommand(string $command): array
            {
                return ['output' => [], 'code' => 0];
            }
        };

        $result = $command->execute($application, $fs);

        $this->assertSame(PostCreateProject::STATUS_SUCCESS, $result);
        $this->assertFileExists($tmpRoot . '/.vscode/tasks.json');
        $this->assertFileExists($tmpRoot . '/.vscode/launch.json');
        $this->assertFileExists($tmpRoot . '/.env');

        $env = file_get_contents($tmpRoot . '/.env');
        $this->assertStringContainsString('LISTEN_IP=127.250.250.250', (string) $env);
        $this->assertStringContainsString('LISTEN_DOMAIN=mako-vue.dev', (string) $env);
        $this->assertStringContainsString('XDEBUG_PORT=9555', (string) $env);

        $launch = json_decode((string) file_get_contents($tmpRoot . '/.vscode/launch.json'), true);
        $this->assertSame(9555, $launch['configurations'][0]['port']);

        unlink($tmpRoot . '/.vscode/tasks.json.example');
        unlink($tmpRoot . '/.vscode/launch.json.example');
        unlink($tmpRoot . '/.vscode/tasks.json');
        unlink($tmpRoot . '/.vscode/launch.json');
        unlink($tmpRoot . '/.env.example');
        unlink($tmpRoot . '/.env');
        unlink($tmpRoot . '/docker/caddy/certs/_wildcard.mako-vue.dev.pem');
        unlink($tmpRoot . '/docker/caddy/certs/_wildcard.mako-vue.dev-key.pem');
        rmdir($tmpRoot . '/docker/caddy/certs');
        rmdir($tmpRoot . '/docker/caddy');
        rmdir($tmpRoot . '/docker');
        rmdir($tmpRoot . '/.vscode');
        rmdir($tmpRoot . '/app');
        rmdir($tmpRoot);
    }

    public function test_execute_reprompts_for_empty_and_reused_domain_name(): void
    {
        $application = $this->createStub(Application::class);
        $application->method('getPath')->willReturn('/var/www/html/app');
        $application->method('getEnvironment')->willReturn('docker');

        $fs = $this->createStub(FileSystem::class);
        $fs->method('has')->willReturnMap([
            ['/var/www/html/app/../.vscode/tasks.json', true],
            ['/var/www/html/app/../.vscode/launch.json', true],
            ['/var/www/html/app/../.env', true],
        ]);
        $fs->method('get')->willReturnMap([
            ['/var/www/html/app/../.env', "LISTEN_IP=127.0.0.1\nLISTEN_DOMAIN=localhost\nXDEBUG_PORT=9003\n"],
            ['/var/www/html/app/../.vscode/launch.json', json_encode([
                'configurations' => [[
                    'type' => 'php',
                    'request' => 'launch',
                    'pathMappings' => ['/var/www/html' => '${workspaceFolder}'],
                    'port' => 9003,
                ]],
            ])],
            ['/tmp/hosts', "127.0.0.1 localhost\n127.0.0.2 taken.dev\n"],
        ]);

        $command = $this->makeStubbedCommand(
            ['', 'taken.dev', 'good.dev', '127.0.0.9', '9555'],
            [false]
        );

        $result = $command->execute($application, $fs);

        $this->assertSame(PostCreateProject::STATUS_SUCCESS, $result);
        $this->assertTrue(
            in_array('<red>Please enter a valid domain name.</red>', $command->writes, true)
        );
        $this->assertTrue(
            in_array("<red>The domain 'taken.dev' is already in the hosts file.</red>", $command->writes, true)
        );
    }

    public function test_execute_returns_error_when_all_loopback_ips_are_exhausted(): void
    {
        $application = $this->createStub(Application::class);
        $application->method('getPath')->willReturn('/var/www/html/app');
        $application->method('getEnvironment')->willReturn('docker');

        $fs = $this->createStub(FileSystem::class);
        $fs->method('has')->willReturnMap([
            ['/var/www/html/app/../.vscode/tasks.json', true],
            ['/var/www/html/app/../.vscode/launch.json', true],
            ['/var/www/html/app/../.env', true],
        ]);
        $fs->method('get')->willReturnMap([
            ['/var/www/html/app/../.env', "LISTEN_IP=127.0.0.1\nLISTEN_DOMAIN=localhost\nXDEBUG_PORT=9003\n"],
            ['/var/www/html/app/../.vscode/launch.json', json_encode([
                'configurations' => [[
                    'type' => 'php',
                    'request' => 'launch',
                    'pathMappings' => ['/var/www/html' => '${workspaceFolder}'],
                    'port' => 9003,
                ]],
            ])],
            ['/tmp/hosts', "127.0.0.1 localhost\n127.255.255.254 max.loopback\n"],
        ]);

        $command = $this->makeStubbedCommand(['good.dev'], []);

        $result = $command->execute($application, $fs);

        $this->assertSame(PostCreateProject::STATUS_ERROR, $result);
        $this->assertTrue(
            in_array(
                '<red>No available loopback IP addresses found in the range 127.0.0.1 - 127.255.255.254. Manual configuration required.</red>',
                $command->writes,
                true
            )
        );
    }

    public function test_execute_reprompts_for_invalid_ip_and_port_inputs(): void
    {
        $application = $this->createStub(Application::class);
        $application->method('getPath')->willReturn('/var/www/html/app');
        $application->method('getEnvironment')->willReturn('docker');

        $fs = $this->createStub(FileSystem::class);
        $fs->method('has')->willReturnMap([
            ['/var/www/html/app/../.vscode/tasks.json', true],
            ['/var/www/html/app/../.vscode/launch.json', true],
            ['/var/www/html/app/../.env', true],
        ]);
        $fs->method('get')->willReturnMap([
            ['/var/www/html/app/../.env', "LISTEN_IP=127.0.0.1\nLISTEN_DOMAIN=localhost\nXDEBUG_PORT=9003\n"],
            ['/var/www/html/app/../.vscode/launch.json', json_encode([
                'configurations' => [[
                    'type' => 'php',
                    'request' => 'launch',
                    'pathMappings' => ['/var/www/html' => '${workspaceFolder}'],
                    'port' => 9003,
                ]],
            ])],
            ['/tmp/hosts', "127.0.0.1 localhost\n127.0.0.2 busy.dev\n"],
        ]);

        $command = $this->makeStubbedCommand(
            ['good.dev', 'not-ip', '10.0.0.1', '127.0.0.2', '127.0.0.9', '0', '70000', 'abc', '9555'],
            [false]
        );

        $result = $command->execute($application, $fs);

        $this->assertSame(PostCreateProject::STATUS_SUCCESS, $result);
        $this->assertTrue(in_array('<red>Please enter a valid IP address.</red>', $command->writes, true));
        $this->assertTrue(
            in_array('<red>Please enter a loopback IP address in the range 127.0.0.1 - 127.255.255.254.</red>', $command->writes, true)
        );
        $this->assertTrue(
            in_array("<red>The IP address '127.0.0.2' is already in the hosts file.</red>", $command->writes, true)
        );
        $invalidPortCount = count(array_filter(
            $command->writes,
            static fn (string $line): bool => $line === '<red>Please enter a valid port number between 1 and 65535.</red>'
        ));
        $this->assertSame(3, $invalidPortCount);
    }

    public function test_execute_warns_when_using_privileged_xdebug_port(): void
    {
        $application = $this->createStub(Application::class);
        $application->method('getPath')->willReturn('/var/www/html/app');
        $application->method('getEnvironment')->willReturn('docker');

        $fs = $this->createStub(FileSystem::class);
        $fs->method('has')->willReturnMap([
            ['/var/www/html/app/../.vscode/tasks.json', true],
            ['/var/www/html/app/../.vscode/launch.json', true],
            ['/var/www/html/app/../.env', true],
        ]);
        $fs->method('get')->willReturnMap([
            ['/var/www/html/app/../.env', "LISTEN_IP=127.0.0.9\nLISTEN_DOMAIN=good.dev\nXDEBUG_PORT=80\n"],
            ['/var/www/html/app/../.vscode/launch.json', json_encode([
                'configurations' => [[
                    'type' => 'php',
                    'request' => 'launch',
                    'pathMappings' => ['/var/www/html' => '${workspaceFolder}'],
                    'port' => 80,
                ]],
            ])],
            ['/tmp/hosts', "127.0.0.1 localhost\n"],
        ]);

        $command = $this->makeStubbedCommand(
            ['good.dev', '127.0.0.9', '80'],
            [false]
        );

        $result = $command->execute($application, $fs);

        $this->assertSame(PostCreateProject::STATUS_SUCCESS, $result);
        $this->assertTrue(
            in_array(
                '<yellow>Note: Using a privileged port (<1024) may require additional configuration on your host to allow VSCode to bind to it.</yellow>',
                $command->writes,
                true
            )
        );
    }

    public function test_execute_recreates_existing_cert_files_when_confirmed(): void
    {
        $tmpRoot = sys_get_temp_dir() . '/mako-post-create-recreate-' . uniqid('', true);
        mkdir($tmpRoot . '/app', 0777, true);
        mkdir($tmpRoot . '/.vscode', 0777, true);
        mkdir($tmpRoot . '/docker/caddy/certs', 0777, true);

        file_put_contents($tmpRoot . '/.env', "LISTEN_IP=127.0.0.9\nLISTEN_DOMAIN=good.dev\nXDEBUG_PORT=9555\n");
        file_put_contents(
            $tmpRoot . '/.vscode/launch.json',
            json_encode([
                'configurations' => [[
                    'type' => 'php',
                    'request' => 'launch',
                    'pathMappings' => ['/var/www/html' => '${workspaceFolder}'],
                    'port' => 9555,
                ]],
            ])
        );
        file_put_contents($tmpRoot . '/.vscode/tasks.json', "{}\n");

        $certFile = $tmpRoot . '/docker/caddy/certs/_wildcard.good.dev.pem';
        $keyFile = $tmpRoot . '/docker/caddy/certs/_wildcard.good.dev-key.pem';
        file_put_contents($certFile, 'cert');
        file_put_contents($keyFile, 'key');

        $application = $this->createStub(Application::class);
        $application->method('getPath')->willReturn($tmpRoot . '/app');
        $application->method('getEnvironment')->willReturn('development');

        $command = $this->makeStubbedCommand(
            ['good.dev', '127.0.0.9', '9555'],
            [true, true, false]
        );

        $result = $command->execute($application, new FileSystem());

        $this->assertSame(PostCreateProject::STATUS_SUCCESS, $result);
        $this->assertFileDoesNotExist($certFile);
        $this->assertFileDoesNotExist($keyFile);
        $this->assertTrue(
            in_array('Deleted existing HTTPS certificate files.', $command->writes, true)
        );

        unlink($tmpRoot . '/.env');
        unlink($tmpRoot . '/.vscode/launch.json');
        unlink($tmpRoot . '/.vscode/tasks.json');
        rmdir($tmpRoot . '/docker/caddy/certs');
        rmdir($tmpRoot . '/docker/caddy');
        rmdir($tmpRoot . '/docker');
        rmdir($tmpRoot . '/.vscode');
        rmdir($tmpRoot . '/app');
        rmdir($tmpRoot);
    }

    public function test_execute_handles_mkcert_failure_without_real_shell_call(): void
    {
        $tmpRoot = sys_get_temp_dir() . '/mako-post-create-mkcert-fail-' . uniqid('', true);
        mkdir($tmpRoot . '/app', 0777, true);
        mkdir($tmpRoot . '/.vscode', 0777, true);
        mkdir($tmpRoot . '/docker/caddy/certs', 0777, true);

        file_put_contents($tmpRoot . '/.env', "LISTEN_IP=127.0.0.9\nLISTEN_DOMAIN=good.dev\nXDEBUG_PORT=9555\n");
        file_put_contents(
            $tmpRoot . '/.vscode/launch.json',
            json_encode([
                'configurations' => [[
                    'type' => 'php',
                    'request' => 'launch',
                    'pathMappings' => ['/var/www/html' => '${workspaceFolder}'],
                    'port' => 9555,
                ]],
            ])
        );
        file_put_contents($tmpRoot . '/.vscode/tasks.json', "{}\n");

        $application = $this->createStub(Application::class);
        $application->method('getPath')->willReturn($tmpRoot . '/app');
        $application->method('getEnvironment')->willReturn('development');

        $command = $this->makeStubbedCommand(
            ['good.dev', '127.0.0.9', '9555'],
            [true, true],
            [true],
            [['output' => ['mkcert failed'], 'code' => 1]]
        );

        $result = $command->execute($application, new FileSystem());

        $this->assertSame(PostCreateProject::STATUS_SUCCESS, $result);
        $this->assertTrue(
            in_array(
                '<red>Failed to create HTTPS certificate using mkcert. Please run the following command manually:</red>',
                $command->writes,
                true
            )
        );
        $this->assertTrue(
            in_array('  <red>mkcert failed</red>', $command->writes, true)
        );

        unlink($tmpRoot . '/.env');
        unlink($tmpRoot . '/.vscode/launch.json');
        unlink($tmpRoot . '/.vscode/tasks.json');
        rmdir($tmpRoot . '/docker/caddy/certs');
        rmdir($tmpRoot . '/docker/caddy');
        rmdir($tmpRoot . '/docker');
        rmdir($tmpRoot . '/.vscode');
        rmdir($tmpRoot . '/app');
        rmdir($tmpRoot);
    }

    public function test_execute_queues_host_mkcert_request_when_automatic_handling_is_skipped(): void
    {
        putenv('MAKO_SKIP_AUTOMATIC_MKCERT=1');

        $tmpRoot = sys_get_temp_dir() . '/mako-post-create-mkcert-queue-flow-' . uniqid('', true);
        mkdir($tmpRoot . '/app', 0777, true);
        mkdir($tmpRoot . '/.vscode', 0777, true);
        mkdir($tmpRoot . '/docker/caddy/certs', 0777, true);

        file_put_contents($tmpRoot . '/.env', "LISTEN_IP=127.0.0.9\nLISTEN_DOMAIN=good.dev\nXDEBUG_PORT=9555\n");
        file_put_contents(
            $tmpRoot . '/.vscode/launch.json',
            json_encode([
                'configurations' => [[
                    'type' => 'php',
                    'request' => 'launch',
                    'pathMappings' => ['/var/www/html' => '${workspaceFolder}'],
                    'port' => 9555,
                ]],
            ])
        );
        file_put_contents($tmpRoot . '/.vscode/tasks.json', "{}\n");

        $application = $this->createStub(Application::class);
        $application->method('getPath')->willReturn($tmpRoot . '/app');
        $application->method('getEnvironment')->willReturn('development');

        $command = $this->makeStubbedCommand(
            ['good.dev', '127.0.0.9', '9555'],
            [true, true],
            [true],
            [['output' => [], 'code' => 0]]
        );

        $result = $command->execute($application, new FileSystem());

        $this->assertSame(PostCreateProject::STATUS_SUCCESS, $result);
        $this->assertFileExists($tmpRoot . '/.mkcert-request');
        $this->assertSame("good.dev\n", file_get_contents($tmpRoot . '/.mkcert-request'));
        $this->assertTrue(
            in_array('<green>Queued host-side HTTPS certificate creation for setup.sh.</green>', $command->writes, true)
        );
        $this->assertFalse(
            in_array('<green>HTTPS certificate created successfully.</green>', $command->writes, true)
        );

        unlink($tmpRoot . '/.mkcert-request');
        unlink($tmpRoot . '/.env');
        unlink($tmpRoot . '/.vscode/launch.json');
        unlink($tmpRoot . '/.vscode/tasks.json');
        rmdir($tmpRoot . '/docker/caddy/certs');
        rmdir($tmpRoot . '/docker/caddy');
        rmdir($tmpRoot . '/docker');
        rmdir($tmpRoot . '/.vscode');
        rmdir($tmpRoot . '/app');
        rmdir($tmpRoot);
    }

    private function makeStubbedCommand(
        array $inputQueue,
        array $confirmQueue,
        array $commandExistsQueue = [],
        array $shellResultQueue = []
    ): PostCreateProject
    {
        return new class(
            $this->createStub(Input::class),
            $this->createStub(Output::class),
            $inputQueue,
            $confirmQueue,
            $commandExistsQueue,
            $shellResultQueue
        ) extends PostCreateProject {
            public array $writes = [];

            public function __construct(
                Input $input,
                Output $output,
                private array $inputQueue,
                private array $confirmQueue,
                private array $commandExistsQueue,
                private array $shellResultQueue
            ) {
                parent::__construct($input, $output);
            }

            protected function nl(int $lines = 1, int $writer = Output::STANDARD): void
            {
            }

            protected function write(string $string, int $writer = Output::STANDARD): void
            {
                $this->writes[] = $string;
            }

            protected function writeBlock(string $text, int $width = 80, int $writer = Output::STANDARD): void
            {
            }

            protected function ol(array $items, string $marker = '<yellow>%s</yellow>.', int $writer = Output::STANDARD): void
            {
            }

            protected function confirm(
                string $question,
                bool $default = false,
                string $trueLabel = 'Yes',
                string $falseLabel = 'No',
                \mako\cli\input\components\confirmation\Theme $theme = new \mako\cli\input\components\confirmation\Theme('<green>%s</green>', '<red>%s</red>', '<purple><bold>%s</bold></purple>')
            ): bool {
                return array_shift($this->confirmQueue) ?? false;
            }

            protected function input(string $prompt, mixed $default = null, string $inputPrefix = '<purple><bold>></bold></purple>'): mixed
            {
                $next = array_shift($this->inputQueue);
                return $next ?? $default;
            }

            protected function commandExists(string $command): bool
            {
                $next = array_shift($this->commandExistsQueue);

                return (bool) ($next ?? false);
            }

            protected function runShellCommand(string $command): array
            {
                $next = array_shift($this->shellResultQueue);

                return is_array($next) ? $next : ['output' => [], 'code' => 0];
            }
        };
    }
}
