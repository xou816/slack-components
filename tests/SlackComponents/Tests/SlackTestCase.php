<?php

namespace SlackComponents\Tests;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client; 
use SlackComponents\Components\CallbackId;
use SlackComponents\Utils\ApiClient;
use SlackComponents\Utils\TestUtils;
use SlackComponents\Routing\SlackRouter;
use SlackComponents\Routing\SlackPayload;

class SlackTestCase extends TestCase {

	public function createSimpleRouter($safe = false) {
		$options = ['token' => 'slack_token', 'safe' => $safe];
		return new SlackRouter($this->createMock(Client::class), $options);
	}

	public function createSimpleMessage($text = 'Hello world') {
		return [
			'text' => $text,
			'attachments' => [
				[
					'actions' => [
						[
							'name' => 'btn',
							'text' => 'Button',
							'type' => 'button',
							'value' => 'pressed'
						]
					]
				]
			]
		];
	}

	public function respondWith($message) {
		return function($payload) use ($message) {
			return is_null($message) ? null : 
			SlackPayload::create(SlackPayload::RESPONSE, null, $message);
		};
	}

	public function compile($message) {
		return SlackPayload::create(SlackPayload::WEBHOOK, null, $message);
	}

	public function trigger($key) {
		$payload = TestUtils::defaultPayload([]);
		$payload['callback_id'] = CallbackId::just($key);
		return $payload;
	}

}