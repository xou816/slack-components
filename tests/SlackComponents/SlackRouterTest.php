<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client; 

use SlackComponents\SlackRouter;
use SlackComponents\SlackRouterException;
use SlackComponents\CompiledMessage;
use SlackComponents\CallbackId;
use SlackComponents\Utils\TestUtils;

class SlackRouterTest extends TestCase {

	private function createSimpleRouter() {
		$client = $this->createMock(Client::class);
		$options = ['token' => 'slack_token'];
		return new SlackRouter($client, $options);
	}

	private function createSimpleMessage($text = 'Hello world') {
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

	private function respondWith($message) {
		return function($payload) use ($message) {
			return $message;
		};
	}

	private function triggerChannel($channel) {
		$payload = TestUtils::defaultPayload([]);
		$payload['callback_id'] = CallbackId::write($channel, []);
		return $payload;
	}

	public function testHandlersCanBeRegistered() {
		$router = $this->createSimpleRouter();
		$message = $this->createSimpleMessage();
		$router->now('some_channel', $this->respondWith($message));
		$compiled = CompiledMessage::compile('some_channel', $message);
		$resp = $router->handleNow($this->triggerChannel('some_channel'), false);
		$this->assertEquals($compiled->getMessage(), $resp->getMessage());
		$this->assertEquals($compiled->getChannel(), $resp->getChannel());
	}

	public function testThereIsADefaultHandler() {
		$router = $this->createSimpleRouter();
		$resp = $router->handleNow(null, false);
		$this->assertTrue(!is_null($resp));
		$this->assertEquals($resp->getMessage()['response_type'], 'ephemeral');
	}

	public function testFirstNonNullHandlerIsUsed() {
		$router = $this->createSimpleRouter();
		$one = null;
		$two = $this->createSimpleMessage('Message #2');
		$three = $this->createSimpleMessage('Message #3');
		$router
			->now('some_channel', $this->respondWith($one))
			->now('some_channel', $this->respondWith($two))
			->now('some_channel', $this->respondWith($three));
		$resp = $router->handleNow($this->triggerChannel('some_channel'), false);
		$this->assertEquals('Message #2', $resp->getMessage()['text']);
	}

	public function testTokenIsVerified() {
		$router = $this->createSimpleRouter();
		$this->expectException(SlackRouterException::class);
		$router->handleNow($this->triggerChannel('any'));
	}

	public function testOnlyTheRelevantChannelResponds() {
		$router = $this->createSimpleRouter();
		$channel_a = $this->createSimpleMessage('Message for channel_a');
		$channel_b = $this->createSimpleMessage('Message for channel_b');
		$router
			->now('channel_a', $this->respondWith($channel_a))
			->now('channel_b', $this->respondWith($channel_b));
		$resp = $router->handleNow($this->triggerChannel('channel_a'), false);
		$this->assertEquals('Message for channel_a', $resp->getMessage()['text']);
	}

}