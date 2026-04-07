<?php
namespace app\console\commands;

use mako\application\Application;
use mako\file\FileSystem;
use mako\reactor\attributes\CommandDescription;
use mako\reactor\attributes\CommandName;
use mako\reactor\Command;

use function preg_replace;

/**
 * Command that updates the .gitignore file after setting up the project.
 */
#[CommandName('git:update-ignore')]
#[CommandDescription('Updates the .gitignore file after setting up the project.')]
class UpdateGitIgnore extends Command
{
	/**
	 * Lines to remove from the .gitignore file.
	 */
	protected const REMOVE = [
		'/composer.lock',
		'/package-lock.json',
	];

	/**
	 * Executes the command.
	 *
	 * @return int|void
	 */
	public function execute(Application $application, FileSystem $fs)
	{
		$gitIgnoreFile = "{$application->getPath()}/../.gitignore";

		if (!$fs->isWritable($gitIgnoreFile)) {
			$this->error('Unable to update the .gitignore file. Make sure that the [ ../.gitignore ] file is writable.');

			return static::STATUS_ERROR;
		}

		$contents = $fs->get($gitIgnoreFile);

		foreach (static::REMOVE as $line) {
			$contents = preg_replace('/^' . preg_quote($line, '/') . '\s*\n?/m', '', $contents);
		}

		$fs->put($gitIgnoreFile, $contents);

		$this->write('The .gitignore file has been updated.');
	}
}
