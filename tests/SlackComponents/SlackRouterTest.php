<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client; 

use SlackComponents\SlackRouter;
use SlackComponents\SlackRouterException;
use SlackComponents\CompiledMessage;

class SlackRouterTest extends TestCase {

	private function createSimpleRouter() {
		return Test::createSimpleRouter($this->createMock(Client::class));
	}

	public function testHandlersCanBeRegistered() {
		$router = $this->createSimpleRouter();
		$message = Test::createSimpleMessage();
		$router->now('some_channel', Test::respondWith($message));
		$compiled = CompiledMessage::compile('some_channel', $message);
		$resp = $router->handleNow(Test::triggerChannel('some_channel'), false);
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
		$two = Test::createSimpleMessage('Message #2');
		$three = Test::createSimpleMessage('Message #3');
		$router
			->now('some_channel', Test::respondWith($one))
			->now('some_channel', Test::respondWith($two))
			->now('some_channel', Test::respondWith($three));
		$resp = $router->handleNow(Test::triggerChannel('some_channel'), false);
		$this->assertEquals('Message #2', $resp->getMessage()['text']);
	}

	public function testTokenIsVerified() {
		$router = $this->createSimpleRouter();
		$this->expectException(SlackRouterException::class);
		$router->handleNow(Test::triggerChannel('any'));
	}

	public function testOnlyTheRelevantChannelResponds() {
		$router = $this->createSimpleRouter();
		$channel_a = Test::createSimpleMessage('Message for channel_a');
		$channel_b = Test::createSimpleMessage('Message for channel_b');
		$router
			->now('channel_a', Test::respondWith($channel_a))
			->now('channel_b', Test::respondWith($channel_b));
		$resp = $router->handleNow(Test::triggerChannel('channel_a'), false);
		$this->assertEquals('Message for channel_a', $resp->getMessage()['text']);
	}

}