<?php

/**
 * @var \mako\application\Application $app
 * @var \mako\syringe\Container       $container
 */

use app\interfaces\EmailSenderInterface;
use app\adapters\PHPMailerAdapter;
use app\validator\rules\Boolean;
use app\view\renderers\TemplateAddon;
use mako\validator\ValidatorFactory;
use mako\view\ViewFactory;

// This file gets included at the end of the application boot sequence

// override error display setting based on config
$display_errors = $container->get('config')->get('application.error_handler.display_errors', false);
ini_set('display_errors', $display_errors);

// date/time display format
define('DT_FMT_DISPLAY', 'D, j M Y g:i:s a T');

// validation extensions
$validator = $container->get(ValidatorFactory::class)
	->extend('boolean', Boolean::class)
;

// email sender
$container->register(EmailSenderInterface::class, PHPMailerAdapter::class);

// template renderer override for add-ons
$container->get(ViewFactory::class)->extend('.tpl.php', TemplateAddon::class);