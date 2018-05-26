<?php

use GuzzleHttp\Client; 
use SlackComponents\CallbackId;
use SlackComponents\Utils\ApiClient;
use SlackComponents\Utils\TestUtils;
use SlackComponents\SlackRouter;
use SlackComponents\CompiledResource;

class Test {

	public static function createSimpleRouter(Client $client, ApiClient $api) {
		$options = ['token' => 'slack_token'];
		return new SlackRouter($client, $api, $options);
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
			return is_null($message) ? null : CompiledResource::compileResponse(null, null, $message);
		};
	}

	public static function triggerChannel($channel) {
		$payload = TestUtils::defaultPayload([]);
		$payload['callback_id'] = CallbackId::write($channel, []);
		return $payload;
	}

}