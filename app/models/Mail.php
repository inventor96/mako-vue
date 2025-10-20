<?php
namespace app\models;

class Mail {
	protected EmailUser $from;
	protected string $subject = '';
	/** @var EmailUser[] */
	protected array $to = [];
	protected array $content = [];

	/**
	 * Sets the sender.
	 *
	 * @param EmailUser $from
	 * @return self
	 */
	public function setFrom(EmailUser $from): self {
		$this->from = $from;
		return $this;
	}

	/**
	 * Gets the sender.
	 *
	 * @return EmailUser
	 */
	public function getFrom(): EmailUser {
		return $this->from;
	}

	/**
	 * Sets the subject.
	 *
	 * @param string $subject
	 * @return self
	 */
	public function setSubject(string $subject): self {
		$this->subject = $subject;
		return $this;
	}

	/**
	 * Gets the subject.
	 *
	 * @return string
	 */
	public function getSubject(): string {
		return $this->subject;
	}

	/**
	 * Adds a recipient to the list.
	 *
	 * @param EmailUser $recipient
	 * @return self
	 */
	public function addTo(EmailUser $recipient): self {
		$this->to[] = $recipient;
		return $this;
	}

	/**
	 * Gets all of the recipients list.
	 *
	 * @return EmailUser[]
	 */
	public function getTos(): array {
		return $this->to;
	}

	/**
	 * Adds email body of the given content type. If a mime type already exists, it will be overwritten.
	 *
	 * @param string $mime
	 * @param string $content
	 * @return self
	 */
	public function addContent(string $mime, string $content): self {
		$this->content[$mime] = $content;
		return $this;
	}

	/**
	 * Gets an associative array of all content types, the key being the mime type and the value being the content.
	 *
	 * @return array
	 */
	public function getContents(): array {
		return $this->content;
	}

	/**
	 * Gets the content of the given mime type.
	 *
	 * @param string $mime
	 * @return string|null
	 */
	public function getContent(string $mime): ?string {
		return isset($this->content[$mime]) ? $this->content[$mime] : null;
	}
}