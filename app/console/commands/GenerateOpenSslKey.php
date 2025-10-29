<?php
namespace app\console\commands;

use mako\application\Application;
use mako\file\FileSystem;
use mako\reactor\Command;
use mako\security\Key;

use function preg_replace;

/**
 * Command that generates a new OpenSSL key.
 */
class GenerateOpenSslKey extends Command
{
	/**
	 * {@inheritDoc}
	 */
	protected string $command = 'openssl:generate-key';

	/**
	 * {@inheritDoc}
	 */
	protected string $description = 'Generates a new OpenSSL key.';

	/**
	 * Executes the command.
	 *
	 * @return int|void
	 */
	public function execute(Application $application, FileSystem $fs)
	{
		$configFile = "{$application->getPath()}/config/crypto.php";

		if (!$fs->isWritable($configFile)) {
			$this->error('Unable to generate a new key. Make sure that the [ app/config/crypto.php ] file is writable.');

			return static::STATUS_ERROR;
		}

		$key = Key::generateEncoded();

		$contents = $fs->get($configFile);

		$contents = preg_replace("/'key'(\s*)=>(\s*)'(.*)',/", "'key'$1=>$2'{$key}',", $contents);

		$fs->put($configFile, $contents);

		$this->write('A new OpenSSL key has been generated.');
	}
}
