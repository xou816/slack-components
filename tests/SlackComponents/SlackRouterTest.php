<?php

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface; 

use SlackComponents\Routing\SlackRouter;
use SlackComponents\Routing\SlackRouterException;
use SlackComponents\Routing\CompiledResource;
use SlackComponents\Utils\ApiClient;

class SlackRouterTest extends TestCase {

	private function createSimpleRouter() {
		return Test::createSimpleRouter($this->createMock(Client::class), $this->createMock(ApiClient::class));
	}

	public function testHandlersCanBeRegistered() {
		$router = $this->createSimpleRouter();
		$message = Test::createSimpleMessage();
		$router->now('some_channel', Test::respondWith($message));
		$compiled = CompiledResource::compileMessage(null, $message);
		$resp = $router->handleNow(Test::triggerChannel('some_channel'), false);
		$this->assertEquals($compiled->getResource(), $resp->getResource());
	}

	public function testThereIsADefaultHandler() {
		$router = $this->createSimpleRouter();
		$resp = $router->handleNow(null, false);
		$this->assertTrue(!is_null($resp));
		$this->assertEquals($resp->getResource()['response_type'], 'ephemeral');
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
		$this->assertEquals('Message #2', $resp->getResource()['text']);
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
		$this->assertEquals('Message for channel_a', $resp->getResource()['text']);
	}

	public function testRouterCanSendDialogs() {
		$client = $this->prophesize(Client::class);
		$client->send(Argument::any())->willReturn(new GuzzleHttp\Psr7\Response());
		$router = Test::createSimpleRouter(
			$client->reveal(), 
			new ApiClient($client->reveal(), ['app_token' => 'app_token']));;
		$router->send(CompiledResource::compileDialog('channel', 'trigger', ['key' => 'value']));
		$client->send(Argument::that(function(RequestInterface $req) {
			$body = [];
			mb_parse_str($req->getBody(), $body);
			$dialog = json_decode($body['dialog'], true);
			return $req->getHeaderLine('Content-Type') === 'application/x-www-form-urlencoded'
				&& $req->getMethod() === 'POST'
				&& isset($body['trigger_id'])
				&& isset($body['token'])
				&& isset($dialog['callback_id'])
				&& $req->getUri()->getHost() === 'slack.com'
				&& $req->getUri()->getPath() === '/api/dialog.open';
		}))->shouldHaveBeenCalled();
	}

}