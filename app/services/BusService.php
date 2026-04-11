<?php

namespace app\services;

use mako\application\services\BusService as BaseBusService;

/**
 * {@inheritDoc}
 * @codeCoverageIgnore
 */
class BusService extends BaseBusService
{
	/**
	 * {@inheritDoc}
	 */
	protected function getCommandHandlers(): array
	{
		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getEventHandlers(): array
	{
		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getQueryHandlers(): array
	{
		return [];
	}
}
