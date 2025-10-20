<?php
namespace app\adapters;

use app\interfaces\EmailSenderInterface;
use app\models\Mail;
use mako\config\Config;
use PHPMailer\PHPMailer\PHPMailer;

class PHPMailerAdapter implements EmailSenderInterface {
	protected PHPMailer $mailer_template;

	public function __construct(Config $config) {
		// create mailer template
		$conf = $config->get('email');
		$this->mailer_template = new PHPMailer(true);
		if ($conf['use_mail'] ?? false) {
			$this->mailer_template->isMail();
		} else {
			$this->mailer_template->isSMTP();
		}

		// set whitelisted settings
		foreach ([
			['host', 'Host'],
			['port', 'Port'],
			['encryption', 'SMTPSecure'],
			['auth', 'SMTPAuth'],
			['username', 'Username'],
			['password', 'Password'],
		] as $prop) {
			if (isset($conf[$prop[0]])) {
				$this->mailer_template->{$prop[1]} = $conf[$prop[0]];
			}
		}
	}

	public function send(Mail $mail) {
		// start new mailer
		$mailer = clone $this->mailer_template;

		// from
		$mailer->setFrom($mail->getFrom()->email, $mail->getFrom()->name ?? '');

		// to(s)
		foreach ($mail->getTos() as $to) {
			$mailer->addAddress($to->email, $to->name ?? '');
		}

		// subject
		$mailer->Subject = $mail->getSubject();

		// set content based on availability
		if (!empty($html = $mail->getContent(PHPMailer::CONTENT_TYPE_TEXT_HTML))) {
			// html content
			$mailer->isHTML(true);
			$mailer->Body = $html;

			// plain text as alt
			if (!empty($text = $mail->getContent(PHPMailer::CONTENT_TYPE_PLAINTEXT))) {
				$mailer->AltBody = $text;
			}
		} else {
			// plain text content
			$mailer->isHTML(false);
			$mailer->Body = $mail->getContent(PHPMailer::CONTENT_TYPE_PLAINTEXT);
		}

		// send it!
		return $mailer->send();
	}
}