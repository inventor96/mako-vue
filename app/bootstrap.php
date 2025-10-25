<?php

/**
 * @var \mako\application\Application $app
 * @var \mako\syringe\Container       $container
 */

use app\validator\rules\Boolean;
use mako\validator\ValidatorFactory;

// This file gets included at the end of the application boot sequence

// override error display setting based on config
$display_errors = $container->get('config')->get('application.error_handler.display_errors', false);
ini_set('display_errors', $display_errors);

// validation extensions
$validator = $container->get(ValidatorFactory::class)
	->extend('boolean', Boolean::class)
;