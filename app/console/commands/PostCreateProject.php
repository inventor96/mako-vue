<?php
namespace app\console\commands;

use mako\application\Application;
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
	public function execute(Application $app): void
	{
		$this->nl();
		$this->write('<blue>+++++ Thank you for choosing Mako-Vue! +++++</blue>');
		$this->nl();
		$this->write('Just a few more steps to get your new project up and running...');
		$this->nl();
		$this->write('If you are running the project <blue>ON YOUR HOST</blue>:');
		$this->ol([
			'Run <green>npm install</green> to install JavaScript dependencies.',
			'Setup your database using the MySQL script at <green>app/migrations/starter.sql</green>.',
			'Setup your config files.',
			'In separate terminals (or asynchronously) run <green>npm run dev</green> and <green>php app/reactor server</green> to start the Vite and Mako development servers.',
		]);
		$this->nl();
		$this->write('If you are using <blue>DOCKER</blue>:');
		$this->ol([
			'Run <green>./docker/config_network.sh</green> <blue>ON YOUR HOST</blue> to setup your local domain name and non-conflicting networking details.',
			'Run <green>docker compose up --build</green> to build and start your containers.',
			'Run <green>npm install</green> (on your host) or <green>docker compose exec frontend npm install</green> (to run it inside Docker) to install JavaScript dependencies.',
		]);
		$this->nl();

		// prompt to run the network configuration script if not in docker
		if ($app->getEnvironment() !== 'docker') {
			$config_net = $this->confirm('Would you like to run <green>./docker/config_network.sh</green> now?', 'y');
			if ($config_net) {
				passthru('./docker/config_network.sh');
			}
		}
	}
}
