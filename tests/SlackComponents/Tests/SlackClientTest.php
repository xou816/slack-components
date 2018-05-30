<?php

namespace SlackComponents\Tests;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface; 
use GuzzleHttp\Psr7\Response;

use SlackComponents\Routing\SlackClient;
use SlackComponents\Routing\SlackPayload;

class SlackClientTest extends SlackTestCase {

	public function testClientCanSendDialogs() {
		$client = $this->prophesize(Client::class);
		$client->send(Argument::any())->willReturn(new Response());
		$slack = new SlackClient($client->reveal(), ['app_token' => 'app_token']);
		$slack->send(SlackPayload::create(SlackPayload::DIALOG, 'trigger', ['key' => 'value']));
		$client->send(Argument::that(function(RequestInterface $req) {
			$body = [];
			mb_parse_str($req->getBody(), $body);
			$dialog = json_decode($body['dialog'], true);
			return $req->getHeaderLine('Content-Type') === 'application/x-www-form-urlencoded'
				&& $req->getMethod() === 'POST'
				&& isset($body['trigger_id'])
				&& isset($body['token'])
				&& $req->getUri()->getHost() === 'slack.com'
				&& $req->getUri()->getPath() === '/api/dialog.open';
		}))->shouldHaveBeenCalled();
	}

}