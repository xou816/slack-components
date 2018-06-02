<?php 

namespace SlackComponents\Routing;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;

class SlackClient {

	private $client;
	private $options;

    public function __construct(Client $client, $options) {
		$this->client = $client;
		$this->options = $options;
	}

	private function sendJson($uri, $body) {
		$body = Psr7\stream_for(json_encode($body));
		$req = new Psr7\Request('POST', Psr7\uri_for($uri));
		$req = $req
			->withHeader('Content-Type', 'application/json')
	        ->withBody($body);
	    $this->client->send($req);
	}

	private function dialogOpen($triggerId, $dialog) {
		$args = [
			'token' => $this->options['app_token'],
			'trigger_id' => $triggerId,
			'dialog' => json_encode($dialog)
		];
		$uri = Psr7\uri_for('https://slack.com/api/dialog.open');
		$body = Psr7\stream_for(http_build_query($args));
		$req = new Psr7\Request('POST', $uri);
		$req = $req
			->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($body);
       	$this->client->send($req);
	}

	public function send(SlackPayload $payload) {
		switch ($payload->getType()) {
			case SlackPayload::DIALOG:
				$this->dialogOpen($payload->getDetails(), $payload->getPayload());
				break;
			case SlackPayload::WEBHOOK:
				$this->sendJson($this->options['webhooks'][$payload->getDetails()], $payload->getPayload());
			case SlackPayload::RESPONSE_DEFER:
				$this->sendJson($payload->getDetails(), $payload->getPayload());
			default:
				break;
		}
	}
	
}