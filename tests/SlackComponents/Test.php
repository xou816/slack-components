<?php

use GuzzleHttp\Client; 
use SlackComponents\CallbackId;
use SlackComponents\SlackRouter;
use SlackComponents\Utils\TestUtils;

class Test {

	public static function createSimpleRouter(Client $client) {
		$options = ['token' => 'slack_token'];
		return new SlackRouter($client, $options);
	}

	public static function createSimpleMessage($text = 'Hello world') {
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

	public static function respondWith($message) {
		return function($payload) use ($message) {
			return $message;
		};
	}

	public static function triggerChannel($channel) {
		$payload = TestUtils::defaultPayload([]);
		$payload['callback_id'] = CallbackId::write($channel, []);
		return $payload;
	}

}