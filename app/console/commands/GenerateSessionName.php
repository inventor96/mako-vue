<?php
namespace app\console\commands;

use mako\application\Application;
use mako\file\FileSystem;
use mako\reactor\Command;
use mako\utility\Str;

use function preg_replace;

/**
 * Command that generates a new session name.
 */
class GenerateSessionName extends Command
{
	/**
	 * {@inheritDoc}
	 */
	protected string $command = 'session:generate-name';

	/**
	 * {@inheritDoc}
	 */
	protected string $description = 'Generates a new session name.';

	/**
	 * Executes the command.
	 *
	 * @return int|void
	 */
	public function execute(Application $application, FileSystem $fs)
	{
		$configFile = "{$application->getPath()}/config/session.php";

		if (!$fs->isWritable($configFile)) {
			$this->error('Unable to generate a new session name. Make sure that the [ app/config/session.php ] file is writable.');

			return static::STATUS_ERROR;
		}

		$name = Str::random(Str::ALNUM, 16);

		$contents = $fs->get($configFile);

		$contents = preg_replace("/'session_name'(\s*)=>(\s*)'(.*)',/", "'session_name'$1=>$2'{$name}',", $contents);

		$fs->put($configFile, $contents);

		$this->write('A new session name has been generated.');
	}
}
