<?php
namespace app\models;

use app\interfaces\EmailSenderInterface;
use mako\config\Config;
use mako\view\ViewFactory;

class Email {
	protected Config $config;
	protected ViewFactory $view;
	protected EmailSenderInterface $sender;

	public function __construct(Config $config, ViewFactory $view, EmailSenderInterface $sender) {
		$this->config = $config;
		$this->view = $view;
		$this->sender = $sender;
	}

	/**
	 * Sends an HTML email.
	 *
	 * @param EmailUser[] $recipients An array of users to send the email to.
	 * @param string $subject The subject line.
	 * @param string $html_body The HTML body of the email.
	 * @param EmailUser|null $from The sender address. Set to null to use the default in the email config.
	 */
	public function send(array $recipients, string $subject, string $html_body, ?EmailUser $from = null) {
		$mail = new Mail();
		$mail->setFrom($from ?? new EmailUser(
			$this->config->get('email.from_email'),
			$this->config->get('email.from_name')
		));
		$mail->setSubject($subject);
		array_map([$mail, 'addTo'], $recipients);
		$mail->addContent('text/html', $html_body);
		return $this->sender->send($mail);
	}

	/**
	 * Sends an HTML email using a template.
	 *
	 * @param EmailUser[] $recipients An array of users to send the email to.
	 * @param string $subject The subject line.
	 * @param string $template The name of the email template to use.
	 * @param array $params An associative array of params to pass into the email template.
	 * @param EmailUser|null $from The sender address. Set to null to use the default in the email config.
	 */
	public function sendTemplate(array $recipients, string $subject, string $template, array $params = [], ?EmailUser $from = null) {
		return $this->send($recipients, $subject, $this->view->render("emails.{$template}", $params), $from);
	}
}