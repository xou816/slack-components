<?php

namespace SlackComponents\Tests;

use SlackComponents\Routing\SlackClient;
use SlackComponents\Routing\SlackRouterException;
use SlackComponents\Routing\SlackPayload;

class SlackRouterTest extends SlackTestCase {

	public function testHandlersCanBeRegistered() {
		$router = $this->createSimpleRouter();
		$message = $this->createSimpleMessage();
		$router->when('callback', $this->respondWith($message));
		$resp = $router->handle($this->trigger('callback'));
		$this->assertEquals($message, $resp->getPayload());
	}

	public function testFirstNonNullHandlerIsUsed() {
		$router = $this->createSimpleRouter();
		$one = null;
		$two = $this->createSimpleMessage('Message #2');
		$three = $this->createSimpleMessage('Message #3');
		$router
			->when('callback', $this->respondWith($one))
			->when('callback', $this->respondWith($two))
			->when('callback', $this->respondWith($three));
		$resp = $router->handle($this->trigger('callback'));
		$this->assertEquals('Message #2', $resp->getPayload()['text']);
	}

	public function testTokenIsVerified() {
		$router = $this->createSimpleRouter(true);
		$this->expectException(SlackRouterException::class);
		$router->hookBeforeResponse($this->trigger('any'));
	}

	public function testOnlyTheRelevantHandlerResponds() {
		$router = $this->createSimpleRouter();
		$callback_a = $this->createSimpleMessage('Message for callback_a');
		$callback_b = $this->createSimpleMessage('Message for callback_b');
		$router
			->when('callback_a', $this->respondWith($callback_a))
			->when('callback_b', $this->respondWith($callback_b));
		$resp = $router->handle($this->trigger('callback_b'));
		$this->assertEquals('Message for callback_b', $resp->getPayload()['text']);
	}

}