<?php
namespace app\console\commands;

use mako\application\Application;
use mako\file\FileSystem;
use mako\reactor\Command;
use mako\utility\Str;

use function preg_replace;

/**
 * Command that generates a new Gatekeeper auth key.
 */
class GenerateGatekeeperAuthKey extends Command
{
	/**
	 * {@inheritDoc}
	 */
	protected string $command = 'gatekeeper:generate-auth-key';

	/**
	 * {@inheritDoc}
	 */
	protected string $description = 'Generates a new Gatekeeper auth key.';

	/**
	 * Executes the command.
	 *
	 * @return int|void
	 */
	public function execute(Application $application, FileSystem $fs)
	{
		$configFile = "{$application->getPath()}/config/gatekeeper.php";

		if (!$fs->isWritable($configFile)) {
			$this->error('Unable to generate a new key. Make sure that the [ app/config/gatekeeper.php ] file is writable.');

			return static::STATUS_ERROR;
		}

		$key = Str::random(Str::ALNUM, 16);

		$contents = $fs->get($configFile);

		$contents = preg_replace("/'auth_key'(\s*)=>(\s*)'(.*)',/", "'auth_key'$1=>$2'{$key}',", $contents);

		$fs->put($configFile, $contents);

		$this->write('A new Gatekeeper auth key has been generated.');
	}
}
