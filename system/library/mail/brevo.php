<?php
namespace Mail;
/**
 * Відправка через Brevo HTTP API (api.brevo.com) — обхід блокування SMTP-портів на Hetzner.
 * API-ключ береться з config_mail_parameter (Налаштування -> Пошта -> Додатковий параметр).
 */
class Brevo {
	// явні властивості: на PHP 8.2+ динамічні дають deprecation-нотиси, які псують тіло ajax-відповідей
	public $to;
	public $from;
	public $sender;
	public $subject;
	public $text;
	public $html;
	public $reply_to;
	public $attachments = array();
	public $parameter;
	public $verp;

	public function send() {
		$payload = array(
			'sender'  => array('name' => $this->sender, 'email' => $this->from),
			'to'      => array(array('email' => $this->to)),
			'subject' => $this->subject,
		);

		if ($this->reply_to) {
			$payload['replyTo'] = array('email' => $this->reply_to);
		}

		if ($this->html) {
			$payload['htmlContent'] = $this->html;
			$payload['textContent'] = $this->text ?: strip_tags(str_replace(array('<br>', '<br/>', '<br />'), "\n", $this->html));
		} else {
			$payload['textContent'] = $this->text;
		}

		if (!empty($this->attachments)) {
			$payload['attachment'] = array();
			foreach ($this->attachments as $attachment) {
				if (is_file($attachment)) {
					$payload['attachment'][] = array('name' => basename($attachment), 'content' => base64_encode(file_get_contents($attachment)));
				}
			}
			if (!$payload['attachment']) unset($payload['attachment']);
		}

		$ch = curl_init('https://api.brevo.com/v3/smtp/email');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('api-key: ' . $this->parameter, 'Content-Type: application/json'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		$response = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curl_error = curl_error($ch);
		curl_close($ch);

		if ($response === false) {
			throw new \Exception('Error: Brevo connection failed! ' . $curl_error);
		}

		if ($status < 200 || $status >= 300) {
			throw new \Exception('Error: Brevo API ' . $status . ': ' . substr($response, 0, 500));
		}
	}
}
