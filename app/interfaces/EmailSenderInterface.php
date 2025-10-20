<?php
namespace app\interfaces;

use app\models\Mail;

interface EmailSenderInterface {
	public function send(Mail $mail);
}