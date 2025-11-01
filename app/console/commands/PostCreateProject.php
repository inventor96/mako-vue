<?php
namespace app\console\commands;

use mako\application\Application;
use mako\file\FileSystem;
use mako\reactor\Command;

/**
 * Post create project command.
 */
class PostCreateProject extends Command
{
	/**
	 * {@inheritDoc}
	 */
	protected string $command = 'post-create-project';

	/**
	 * {@inheritDoc}
	 */
	protected string $description = 'Runs post-create-project tasks.';

	/**
	 * Prints a greeting.
	 */
	public function execute(Application $app, FileSystem $fs)
	{
		// intro
		$this->nl();
		$this->write('<blue>+++++ Thank you for choosing Mako-Vue! +++++</blue>');
		$intro = "This script will help you configure VScode files and the network settings for a Docker-based project. It will setup the <green>.env</green> file and modify <green>/etc/hosts</green> to set up a local development domain using an available loopback address. This will enable you to access your project via a friendly domain name instead of localhost or an IP address. Because each project uses its own loopback IP, you can have multiple projects running simultaneously. This script also allows you to set the host listing port to 80 to avoid needing to specify a port in the URL. If you choose a privileged port (< 1024), ensure your host's Docker configuration allows binding to it. The script will also set up VSCode tasks and launch configurations for debugging. The launch configuration will be set to use Xdebug with a unique port based on the project IP to allow for simultaneous debugging of multiple projects.";

		// split intro into 80 character parts, preserving words
		$parts = [];
		$words = explode(' ', $intro);
		$line = '';
		foreach ($words as $word) {
			if (strlen(strip_tags($line . ' ' . $word)) > 80) {
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
			$this->write($part);
		}

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
			'BACKEND_PORT'  => '',
			'FRONTEND_PORT' => '',
		];
		foreach ($settings as $key => $value) {
			preg_match('/^' . $key . '\s*=\s*(.*)$/m', $env_contents, $matches);
			$settings[$key] = trim($matches[1]) ?? '';
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
		$this->write('  BACKEND_PORT:  ' . ($settings['BACKEND_PORT'] ?: '<yellow>not set</yellow>'));
		$this->write('  FRONTEND_PORT: ' . ($settings['FRONTEND_PORT'] ?: '<yellow>not set</yellow>'));
		$this->write('  Xdebug port:   ' . ($launch_port ?: '<yellow>not set</yellow>'));

		// ask for domain name
		$this->nl();
		$domain_name = (empty($settings['LISTEN_DOMAIN']) || !$had_env || $settings['LISTEN_DOMAIN'] === 'localhost')
			? basename(realpath($app->getPath() . '/../')) . '.test'
			: $settings['LISTEN_DOMAIN'];
		$try_again = true;
		do {
			$input_domain = $this->question("Enter the local development domain name for the project [{$domain_name}]:", $domain_name);

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
			$input_ip = $this->question("Enter the local development IP address for the project [{$listen_ip}]:", $listen_ip);

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

		// ask for backend port
		$this->nl();
		$backend_port = (empty($settings['BACKEND_PORT']) || !$had_env || $settings['BACKEND_PORT'] === '8080') ? '80' : $settings['BACKEND_PORT'];
		$try_again = true;
		do {
			$input_backend_port = $this->question("Enter the backend port for the project [{$backend_port}]:", $backend_port);

			// validate port
			if (!is_numeric($input_backend_port) || (int)$input_backend_port < 1 || (int)$input_backend_port > 65535) {
				$this->write('<red>Please enter a valid port number between 1 and 65535.</red>');
				continue;
			}

			// all good
			$settings['BACKEND_PORT'] = $input_backend_port;
			$try_again = false;

			// privileged port warning
			if ((int)$settings['BACKEND_PORT'] < 1024) {
				$this->write('<yellow>Note: Using a privileged port (< 1024) may require additional configuration on your host to allow Docker to bind to it.</yellow>');
			}
		} while ($try_again);

		// ask for frontend port
		$this->nl();
		$frontend_port = empty($settings['FRONTEND_PORT']) ? '5173' : $settings['FRONTEND_PORT'];
		$try_again = true;
		do {
			$input_frontend_port = $this->question("Enter the frontend port for the project [{$frontend_port}]:", $frontend_port);

			// validate port
			if (!is_numeric($input_frontend_port) || (int)$input_frontend_port < 1 || (int)$input_frontend_port > 65535) {
				$this->write('<red>Please enter a valid port number between 1 and 65535.</red>');
				continue;
			}

			// all good
			$settings['FRONTEND_PORT'] = $input_frontend_port;
			$try_again = false;

			// privileged port warning
			if ((int)$settings['FRONTEND_PORT'] < 1024) {
				$this->write('<yellow>Note: Using a privileged port (< 1024) may require additional configuration on your host to allow Docker to bind to it.</yellow>');
			}
		} while ($try_again);

		// calculate launch.json Xdebug port
		if (empty($launch_port) || !$had_launch || $launch_port === 9003) {
			$ip_parts = explode('.', $settings['LISTEN_IP']);
			$launch_port = 9000 + ((int)$ip_parts[2] * 256) + (int)$ip_parts[3];
		}
		$this->nl();
		$this->write("<yellow>Since this script cannot determine what ports are already in use on your host, it is your responsibility to ensure the chosen port for Xdebug is available. A best guess is made here based on the loopback IP address.</yellow>");
		$try_again = true;
		do {
			$input_launch_port = $this->question("Enter the Xdebug port for VSCode launch.json [{$launch_port}]:", $launch_port);

			// validate port
			if (!is_numeric($input_launch_port) || (int)$input_launch_port < 1 || (int)$input_launch_port > 65535) {
				$this->write('<red>Please enter a valid port number between 1 and 65535.</red>');
				continue;
			}

			// all good
			$launch_port = $input_launch_port;
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
		$this->write("  BACKEND_PORT:  {$settings['BACKEND_PORT']}");
		$this->write("  FRONTEND_PORT: {$settings['FRONTEND_PORT']}");
		$this->write("  Xdebug port:   {$launch_port}");

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
			$launch_json['configurations'][$launch_config_i]['port'] = (int)$launch_port;
			$launch_contents = json_encode($launch_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
			$fs->put($launch_dst, $launch_contents);
			$this->write('Updated <green>.vscode/launch.json</green>.');
		}

		// instruct user to update /etc/hosts
		$hosts_line = "{$settings['LISTEN_IP']} {$settings['LISTEN_DOMAIN']} db.{$settings['LISTEN_DOMAIN']} mail.{$settings['LISTEN_DOMAIN']}";
		$this->nl();
		$this->write('Please add the following entry to your <green>/etc/hosts</green> file:');
		$this->write("  {$hosts_line}");
		$this->write('This requires administrative privileges on your host. e.g.:');
		$this->write("  <yellow>sudo sh -c 'echo \"{$hosts_line}\" >> /etc/hosts'</yellow>");

		// instruct user to run docker-compose down/up
		$this->nl();
		$this->write('After updating <green>/etc/hosts</green>, please (re)start the Docker containers to apply the new settings:');
		$this->write('  <yellow>docker-compose down && docker-compose up --build</yellow>');

		// done
		$this->nl();
		$this->write('<green>Post-create-project tasks completed successfully!</green>');
		return static::STATUS_SUCCESS;
	}
}
