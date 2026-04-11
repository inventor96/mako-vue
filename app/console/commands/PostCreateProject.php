<?php
namespace app\console\commands;

use mako\application\Application;
use mako\cli\output\Output;
use mako\file\FileSystem;
use mako\reactor\attributes\CommandDescription;
use mako\reactor\attributes\CommandName;
use mako\reactor\Command;

/**
 * Post create project command.
 */
#[CommandName('post-create-project')]
#[CommandDescription('Runs post-create-project tasks.')]
class PostCreateProject extends Command
{
	/**
	 * Checks if a shell command exists on the current system.
	 *
	 * @codeCoverageIgnore
	 */
	protected function commandExists(string $command): bool
	{
		return trim(shell_exec('which ' . escapeshellarg($command)) ?? '') !== '';
	}

	/**
	 * Runs a shell command and returns output lines with exit status.
	 *
	 * @return array{output: array<int, string>, code: int}
	 * @codeCoverageIgnore
	 */
	protected function runShellCommand(string $command): array
	{
		$output = [];
		$returnCode = 0;
		exec($command . ' 2>&1', $output, $returnCode);

		return ['output' => $output, 'code' => $returnCode];
	}

	/**
	 * Executes the command.
	 */
	public function execute(Application $app, FileSystem $fs)
	{
		// intro
		$this->nl();
		$this->write('<blue>+++++ Let\'s get this project started! +++++</blue>');
		$this->writeBlock('This script will help you configure VScode files and the network settings for a Docker-based project. Our approach will enable multiple simultaneous projects to run without port conflicts. Here\'s what we\'ll do:');
		$this->ol([
			'Set up the <green>.env</green> file with appropriate network settings.',
			'Set up VSCode tasks and launch configurations for convenience and debugging with Xdebug.',
			'Guide you to add an entry to <green>/etc/hosts</green> for local development.',
			'Facilitate the creation of a local HTTPS certificate using mkcert (if desired).',
		]);
		$this->nl();
		$this->write('Things to be aware of:');
		$this->ol([
			'We will be using a loopback IP address above 127.0.0.1 for each project to avoid conflicts.',
			'The bound ports will be 80 and 443 by default, so you may need to adjust your Docker configuration to allow binding to these ports on your host system.',
			'You will be responsible for ensuring that the chosen Xdebug port is available on your host system, and open appropriately in your firewall (if necessary).',
		]);

		// enviroment check
		$is_docker = $app->getEnvironment() === 'docker';
		if ($is_docker) {
			$this->nl();
			$this->write('<yellow>It looks like you are running this in the Docker environment. Some tasks may require manual intervention on your host.</yellow>');
		}

		// copy tasks.json if it doesn't exist
		$tasks_src = $app->getPath() . '/../.vscode/tasks.json.example';
		$tasks_dst = $app->getPath() . '/../.vscode/tasks.json';
		if (!($had_tasks = $fs->has($tasks_dst))) {
			// prompt to copy tasks.json
			$this->nl();
			$this->write('<yellow>tasks.json not found.</yellow>');
			$copy_tasks = $this->confirm('Would you like to create <green>.vscode/tasks.json</green> from the example?', 'y');
			if ($copy_tasks) {
				$fs->copy($tasks_src, $tasks_dst);
				$this->write('Created <green>.vscode/tasks.json</green>.');
			}
		}

		// copy launch.json if it doesn't exist
		$launch_src = $app->getPath() . '/../.vscode/launch.json.example';
		$launch_dst = $app->getPath() . '/../.vscode/launch.json';
		if (!($had_launch = $fs->has($launch_dst))) {
			// prompt to copy launch.json
			$this->nl();
			$this->write('<yellow>launch.json not found.</yellow>');
			$copy_launch = $this->confirm('Would you like to create <green>.vscode/launch.json</green> from the example?', 'y');
			if ($copy_launch) {
				$fs->copy($launch_src, $launch_dst);
				$this->write('Created <green>.vscode/launch.json</green>.');
			}
		}

		// copy .env if it doesn't exist
		$env_src = $app->getPath() . '/../.env.example';
		$env_dst = $app->getPath() . '/../.env';
		if (!($had_env = $fs->has($env_dst))) {
			// prompt to copy .env
			$this->nl();
			$this->write('<yellow>.env not found.</yellow>');
			$copy_env = $this->confirm('Would you like to create <green>.env</green> from the example?', 'y');
			if ($copy_env) {
				$fs->copy($env_src, $env_dst);
				$this->write('Created <green>.env</green>.');
			}
		}

		// read .env variables
		$env_contents = $fs->get($env_dst);
		$settings = [
			'LISTEN_IP'     => '',
			'LISTEN_DOMAIN' => '',
			'XDEBUG_PORT'   => '',
		];
		foreach ($settings as $key => $value) {
			preg_match('/^' . $key . '\s*=\s*(.*)$/m', $env_contents, $matches);
			$settings[$key] = trim($matches[1] ?? '');
		}

		// get launch.json Xdebug port
		$launch_contents = $fs->get($launch_dst);
		$launch_json = json_decode($launch_contents, true);
		$launch_config_i = null;
		$launch_port = '';
		if (isset($launch_json['configurations']) && is_array($launch_json['configurations'])) {
			foreach ($launch_json['configurations'] as $i => $config) {
				if (
					($config['type'] ?? '') === 'php'
					&& ($config['request'] ?? '') === 'launch'
					&& isset($config['pathMappings']['/var/www/html'])
				) {
					$launch_port = $config['port'] ?? '';
					$launch_config_i = $i;
					break;
				}
			}
		}

		// parse hosts file
		$hostnames = [];
		$loopback_ips = [ip2long('127.0.0.1')];
		$hosts_file = $is_docker ? '/tmp/hosts' : '/etc/hosts';
		$hosts_contents = $fs->get($hosts_file);
		$hosts_lines = explode("\n", $hosts_contents);
		$min_ip_long = ip2long('127.0.0.1');
		$max_ip_long = ip2long('127.255.255.254');
		foreach ($hosts_lines as $line) {
			// skip comments
			$line = explode('#', $line)[0];
			if (empty(trim($line))) {
				continue;
			}

			// skip invalid lines
			$parts = preg_split('/\s+/', trim($line));
			if (count($parts) < 2) {
				continue;
			}

			// record hostnames
			$hostnames = array_merge($hostnames, $parts);

			// record loopback IPs
			if (filter_var($parts[0], FILTER_VALIDATE_IP) === false) {
				continue;
			}
			$ip_long = ip2long($parts[0]);
			if ($ip_long < $min_ip_long || $ip_long > $max_ip_long) {
				continue;
			}
			$loopback_ips[] = ip2long($parts[0]);
		}

		// report existing settings
		$this->nl();
		$this->write('Current environment settings:');
		$this->write('  LISTEN_IP:     ' . ($settings['LISTEN_IP'] ?: '<yellow>not set</yellow>'));
		$this->write('  LISTEN_DOMAIN: ' . ($settings['LISTEN_DOMAIN'] ?: '<yellow>not set</yellow>'));
		$this->write('  XDEBUG_PORT:   ' . ($settings['XDEBUG_PORT'] ?: '<yellow>not set</yellow>'));

		// domain name info
		$this->nl();
		$this->writeBlock("The domain name is used to access your project in the browser. It should be a locally-unique name ending in an applicable top-level domain (e.g., <blue>.test</blue>, <blue>.local</blue>, <blue>.dev</blue>). The script will help ensure the domain is not already in use in your hosts file. Some considerations to keep in mind:");
		$this->ol([
			'<blue>*.local</blue> can interfere with mDNS on some systems',
			'<blue>*.localhost</blue> can cause the browser to give the site special treatment, especially around HTTPS. This may be undesirable when aiming for production parity.',
			'<blue>*.dev</blue> is a real TLD owned by Google and enforces HTTPS in modern browsers via HSTS.',
			'<blue>*.test</blue> is reserved for testing and is generally a safe choice for local development.',
		]);

		// ask for domain name
		$this->nl();
		$domain_name = (empty($settings['LISTEN_DOMAIN']) || !$had_env || $settings['LISTEN_DOMAIN'] === 'localhost')
			? basename(realpath($app->getPath() . '/../')) . '.dev'
			: $settings['LISTEN_DOMAIN'];
		$try_again = true;
		do {
			$input_domain = $this->input("Enter the local development domain name for the project [{$domain_name}]:", $domain_name);

			// require a domain name
			if (empty(trim($input_domain))) {
				$this->write('<red>Please enter a valid domain name.</red>');
				continue;
			}

			// report if domain exists, unless it's the current domain
			if (in_array($input_domain, $hostnames) && $input_domain !== $settings['LISTEN_DOMAIN']) {
				$this->write("<red>The domain '{$input_domain}' is already in the hosts file.</red>");
				continue;
			}

			// all good
			$settings['LISTEN_DOMAIN'] = $input_domain;
			$try_again = false;
		} while ($try_again);

		// use existing listen IP, or find a new one
		$listen_ip = trim($settings['LISTEN_IP']);
		if (empty($listen_ip) || !$had_env || $listen_ip === '127.0.0.1') {
			// get next available loopback IP
			$max_ip = ip2long('127.255.255.254');
			$try_again = true;
			do {
				$max = max($loopback_ips);
				$next_ip_long = $max + 1;

				// out of IPs
				if ($next_ip_long > $max_ip) {
					$this->write('<red>No available loopback IP addresses found in the range 127.0.0.1 - 127.255.255.254. Manual configuration required.</red>');
					return static::STATUS_ERROR;
				}

				// skip network and broadcast addresses
				$next_ip = long2ip($next_ip_long);
				$parts = explode('.', $next_ip);
				if ($parts[3] === '0' || $parts[3] === '255') {
					$loopback_ips[] = $next_ip_long;
					continue;
				}

				// good to go
				$listen_ip = $next_ip;
				$try_again = false;
			} while ($try_again);
		}

		// ask for listen IP
		$this->nl();
		$try_again = true;
		do {
			$input_ip = $this->input("Enter the local development IP address for the project [{$listen_ip}]:", $listen_ip);

			// validate IP
			if (filter_var($input_ip, FILTER_VALIDATE_IP) === false) {
				$this->write('<red>Please enter a valid IP address.</red>');
				continue;
			}

			// check if IP is in loopback range
			$ip_long = ip2long($input_ip);
			if ($ip_long < ip2long('127.0.0.1') || $ip_long > ip2long('127.255.255.254')) {
				$this->write('<red>Please enter a loopback IP address in the range 127.0.0.1 - 127.255.255.254.</red>');
				continue;
			}

			// check if the IP is already in /etc/hosts
			if (in_array($ip_long, $loopback_ips) && $input_ip !== $settings['LISTEN_IP']) {
				$this->write("<red>The IP address '{$input_ip}' is already in the hosts file.</red>");
				continue;
			}

			// all good
			$settings['LISTEN_IP'] = $input_ip;
			$try_again = false;
		} while ($try_again);

		// calculate launch.json Xdebug port
		$launch_port = (int)trim($settings['XDEBUG_PORT']);
		if (empty($launch_port) || !$had_launch || $launch_port === 9003) {
			$ip_parts = explode('.', $settings['LISTEN_IP']);
			$launch_port = 9000 + ((int)$ip_parts[2] * 256) + (int)$ip_parts[3];
		}
		$this->nl();
		$this->write("<yellow>Since this script cannot determine what ports are already in use on your host, it is your responsibility to ensure the chosen port for Xdebug is available. A best guess is made here based on the loopback IP address.</yellow>");
		$try_again = true;
		do {
			$input_launch_port = $this->input("Enter the Xdebug port for VSCode launch.json [{$launch_port}]:", $launch_port);

			// validate port
			if (!is_numeric($input_launch_port) || (int)$input_launch_port < 1 || (int)$input_launch_port > 65535) {
				$this->write('<red>Please enter a valid port number between 1 and 65535.</red>');
				continue;
			}

			// all good
			$settings['XDEBUG_PORT'] = $input_launch_port;
			$try_again = false;

			// privileged port warning
			if ((int)$launch_port < 1024) {
				$this->write('<yellow>Note: Using a privileged port (<1024) may require additional configuration on your host to allow VSCode to bind to it.</yellow>');
			}
		} while ($try_again);

		// summarize changes
		$this->nl();
		$this->write('The following changes will be made:');
		$this->write("  LISTEN_IP:     {$settings['LISTEN_IP']}");
		$this->write("  LISTEN_DOMAIN: {$settings['LISTEN_DOMAIN']}");
		$this->write("  XDEBUG_PORT:   {$settings['XDEBUG_PORT']}");

		$confirm = $this->confirm('Apply these changes?', 'y');
		if (!$confirm) {
			$this->write('<yellow>No changes were made.</yellow>');
			return static::STATUS_SUCCESS;
		}

		// apply changes to .env
		$this->nl();
		foreach ($settings as $key => $value) {
			$env_contents = preg_replace(
				'/^' . $key . '\s*=\s*.*$/m',
				$key . '=' . $value,
				$env_contents
			);
		}
		$fs->put($env_dst, $env_contents);
		$this->write('Updated <green>.env</green>.');

		// apply changes to launch.json
		if ($launch_config_i !== null) {
			$launch_json['configurations'][$launch_config_i]['port'] = (int)$settings['XDEBUG_PORT'];
			$launch_contents = json_encode($launch_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
			$fs->put($launch_dst, $launch_contents);
			$this->write('Updated <green>.vscode/launch.json</green>.');
		}

		// mkcert details
		$cert_file = realpath($app->getPath() . '/../docker/caddy/certs') . '/_wildcard.' . $settings['LISTEN_DOMAIN'] . '.pem';
		$key_file = realpath($app->getPath() . '/../docker/caddy/certs') . '/_wildcard.' . $settings['LISTEN_DOMAIN'] . '-key.pem';
		$mkcert_cmd = "mkcert -cert-file '{$cert_file}' -key-file '{$key_file}' '{$settings['LISTEN_DOMAIN']}' '*.{$settings['LISTEN_DOMAIN']}'";

		// check if cert files already exist
		$recreate_certs = false;
		if ($cert_files_exist = ($fs->has($cert_file) || $fs->has($key_file))) {
			$this->nl();
			$recreate_certs = $this->confirm('<yellow>HTTPS certificate files already exist.</yellow> Would you like to recreate them using mkcert? This will overwrite the existing files.', 'n');
		}

		// delete existing cert files if needed
		if ($recreate_certs) {
			if ($fs->has($cert_file)) {
				$fs->remove($cert_file);
			}
			if ($fs->has($key_file)) {
				$fs->remove($key_file);
			}
			$this->write('Deleted existing HTTPS certificate files.');
			$cert_files_exist = false;
		}

		// create mkcert certs if they don't exist
		if (!$cert_files_exist) {
			// check for mkcert
			$create_cert = false;
			$try_again = false;
			$this->nl();
			do {
				$has_mkcert = $this->commandExists('mkcert');
				if ($has_mkcert) {
					$this->write('<green>mkcert is installed.</green>');
					$this->write("mkcert command to be run:");
					$this->write("  <yellow>{$mkcert_cmd}</yellow>");
					$create_cert = $this->confirm('Would you like to create a local HTTPS certificate for the domain?', 'y');
				} else {
					$this->write('<yellow>mkcert is not detected.</yellow> You can install mkcert from <blue>https://github.com/FiloSottile/mkcert</blue> to create local HTTPS certificates easily. After it is installed, you can retry detection and create the certificate.');
					$try_again = $this->confirm('Do you want to retry mkcert detection? Enter "n" to skip automatic HTTPS certificate creation.', 'y');
				}
			} while ($try_again);

			// create mkcert certificate
			if ($create_cert) {
				$this->nl();
				$this->write("Creating HTTPS certificate using mkcert for <green>{$settings['LISTEN_DOMAIN']}</green>...");
				$mkcert_result = $this->runShellCommand($mkcert_cmd);
				if ($mkcert_result['code'] !== 0) {
					$this->write('<red>Failed to create HTTPS certificate using mkcert. Please run the following command manually:</red>');
					$this->write("  <yellow>{$mkcert_cmd}</yellow>");
					$this->write('mkcert output:');
					foreach ($mkcert_result['output'] as $line) {
						$this->write("  <red>{$line}</red>");
					}
				} else {
					$this->write('<green>HTTPS certificate created successfully.</green>');
				}
			} else {
				$this->write('<yellow>Skipping HTTPS certificate creation.</yellow> Please create the certificate files manually so the Caddy container can start correctly:');
				$this->write("  Cert file: <blue>{$cert_file}</blue>");
				$this->write("  Key file:  <blue>{$key_file}</blue>");
			}
		}

		// instruct user to update /etc/hosts
		$hosts_line = "{$settings['LISTEN_IP']} {$settings['LISTEN_DOMAIN']} db.{$settings['LISTEN_DOMAIN']} mail.{$settings['LISTEN_DOMAIN']}";
		$hosts_regex = '/^' . preg_quote($settings['LISTEN_IP'], '/') . '\s+' . preg_quote($settings['LISTEN_DOMAIN'], '/') . '(\s+db\.' . preg_quote($settings['LISTEN_DOMAIN'], '/') . ')?(\s+mail\.' . preg_quote($settings['LISTEN_DOMAIN'], '/') . ')?$/m';
		if ($hosts_needs_update = !preg_match($hosts_regex, $hosts_contents)) {
			$this->nl();
			$this->write('Please add the following entry to your <green>/etc/hosts</green> file:');
			$this->write("  {$hosts_line}");
			$this->write('This requires administrative privileges on your host. e.g.:');
			$this->write("  <yellow>sudo sh -c 'echo \"{$hosts_line}\" >> /etc/hosts'</yellow>");
		}

		// instruct user to run docker-compose down/up
		$this->nl();
		$this->write(($hosts_needs_update ? 'After updating <green>/etc/hosts</green>, please' : 'Please') . ' (re)start the Docker containers to apply the new settings:');
		$this->write('  <yellow>docker compose down && docker compose up --build</yellow>');

		// done
		$this->nl();
		$this->write('<green>Post-create-project tasks completed successfully!</green>');
		return static::STATUS_SUCCESS;
	}

	/**
	 * Writes a block of text wrapped to the specified width.
	 * @param string $text   The text to write.
	 * @param int    $width  The maximum width of each line.
	 * @param int    $writer The output writer to use.
	 */
	protected function writeBlock(string $text, int $width = 80, int $writer = Output::STANDARD): void
	{
		// split intro into `$width` character parts, preserving words
		$parts = [];
		$words = explode(' ', $text);
		$line = '';
		foreach ($words as $word) {
			if (strlen(strip_tags($line . ' ' . $word)) > $width) {
				$parts[] = trim($line);
				$line = $word . ' ';
			} else {
				$line .= $word . ' ';
			}
		}
		if (!empty($line)) {
			$parts[] = trim($line);
		}
		foreach ($parts as $part) {
			$this->write($part, $writer);
		}
	}
}
