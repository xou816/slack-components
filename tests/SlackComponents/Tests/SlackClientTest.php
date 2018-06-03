<?php

namespace SlackComponents\Tests;

use Prophecy\Argument;
use GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface; 
use GuzzleHttp\Psr7;

use SlackComponents\Routing\SlackClient;
use SlackComponents\Routing\SlackPayload;

class SlackClientTest extends SlackTestCase {

	public function testClientCanSendDialogs() {
		$client = $this->prophesize(Client::class);
		$res = new Psr7\Response(200);
		$client
			->send(Argument::any())
			->willReturn($res->withBody(Psr7\stream_for(json_encode([
				'ok' => true
			]))));
		$slack = new SlackClient($client->reveal(), ['app_token' => 'INVALID_TOKEN']);
		$slack->send(SlackPayload::create(SlackPayload::DIALOG, 'trigger', ['key' => 'value']));
		$client
			->send(Argument::that(function(RequestInterface $req) {
				$body = [];
				mb_parse_str($req->getBody(), $body);
				$dialog = json_decode($body['dialog'], true);
				return $req->getHeaderLine('Content-Type') === 'application/x-www-form-urlencoded'
					&& $req->getMethod() === 'POST'
					&& isset($body['trigger_id'])
					&& isset($body['token'])
					&& $req->getUri()->getHost() === 'slack.com'
					&& $req->getUri()->getPath() === '/api/dialog.open';
			}))
			->shouldHaveBeenCalled();
	}

}