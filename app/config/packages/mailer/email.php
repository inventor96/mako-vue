<?php
return [
	/**
	 * The name of the sender.
	 */
	'from_name' => 'MakoVue',

	/**
	 * The email address of the sender.
	 */
	'from_email' => 'noreply@makovue.test',

	/**
	 * The email adapter class to use.
	 * Must implement `inventor96\MakoMailer\interfaces\EmailSenderInterface`.
	 */
	'adapter' => inventor96\MakoMailer\adapters\PHPMailerAdapter::class,

	/**
	 * Whether to use SMTP for sending emails.
	 * Set to `false` to use the mail() function.
	 */
	'use_smtp' => true,

	/**
	 * SMTP server host.
	 */
	'host' => '',

	/**
	 * SMTP server port.
	 */
	'port' => 465,

	/**
	 * Encryption method to use.
	 * Set to empty string to disable encryption.
	 */
	//'encryption' => PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS,

	/**
	 * Whether to use SMTP authentication.
	 */
	//'auth' => true,

	/**
	 * SMTP username.
	 */
	//'username' => 'noreply@example.com',

	/**
	 * SMTP password.
	 */
	//'password' => 'MySecurePassword123!',
];